<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiTestFormRequest;
use App\Http\Requests\FilterTextRequest;
use App\Models\ProfanityCategory;
use App\Models\SiteConfig;
use App\Models\TestSentence;
use App\Models\TextFilterAudit;
use App\Services\TextFilterService;
use Carbon\Carbon;

class TextFiltrationController extends Controller
{
    public function textFilter(FilterTextRequest $request, TextFilterService $textFilterService): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $request->validated();
            $filter_result = $textFilterService->filterText($data['sentence'], $data['moderation_categories'], $request->user()->id);

            $response = [
                'status' => 'success',
                'profanity' => $filter_result['profanity'],
                'whitelisted_words_in_text' => $filter_result['whitelist_hits'],
                'grawlix' => $filter_result['grawlix'],
                'timestamp' => Carbon::now()->timestamp,
            ];

            //This will be executed after the response is sent to the browser
            defer(function () use ($request, $response) {
                $user = $request->user();
                $free_request_count = $user->free_request_count;

                $profanity_caught = [];
                $profanity_categories = [];

                foreach ($response['profanity']['words'] as $category_key => $category) {
                    foreach ($category as $item) {
                        $profanity_categories[] = $category_key;
                        $profanity_caught[] = $item['flagged_word'];
                    }
                }

                $profanity_caught = implode(',', $profanity_caught);
                $profanity_categories = implode(',', array_unique($profanity_categories));

                //Creating the text filter audit
                TextFilterAudit::query()->create([
                    'user_id' => $user->id,
                    'request_body' => json_encode($request->all(), JSON_PRETTY_PRINT),
                    'response_body' => json_encode($response, JSON_PRETTY_PRINT),
                    'is_successful' => true,
                    'is_free_request' => ($free_request_count != 0),
                    'profanity_caught' => empty($profanity_caught) ? null : $profanity_caught,
                    'profanity_categories_caught' => empty($profanity_categories) ? null : $profanity_categories,
                ]);

                //Decrementing the free requests count if not 0
                if ($free_request_count != 0) {
                    $user->decrement('free_request_count');
                }

                //If user has no free requests left and the user has no payment method on file, we deactivate the user (if he is active)
                if ($free_request_count <= 1 && $user->card_expiry_date == null && $user->is_active) {
                    $user->update([
                        'is_active' => false,
                        'user_inactivity_message' => SiteConfig::query()->firstWhere('key', 'user_inactivity_message_for_no_card')?->value
                    ]);
                }
            });

            $status = 200;

        } catch (\Exception $e) {
            $response = [
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => 'Server Error'
            ];

            $exception_trace = $e->getTraceAsString();

            //This will be executed after the response is sent to the browser
            defer(function () use ($request, $exception_trace) {
                $user = $request->user();

                //Creating the text filter audit with the exception trace
                TextFilterAudit::query()->create([
                    'user_id' => $user->id,
                    'request_body' => json_encode($request->all(), JSON_PRETTY_PRINT),
                    'response_body' => json_encode($exception_trace, JSON_PRETTY_PRINT),
                    'is_successful' => false,
                    'is_free_request' => ($user->free_request_count != 0),
                ]);
            }, always: true);

            $status = 500;
        }

        return response()->json($response, $status);
    }

    public function textFilterTester(ApiTestFormRequest $apiTestFormRequest, TextFilterService $textFilterService): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $apiTestFormRequest->validated();
            $filter_result = $textFilterService->filterText(
                TestSentence::query()->findOrFail($data['sentenceId'])->sentence,
                ProfanityCategory::query()->whereIn('id', $data['categories'])->get()->pluck('profanity_category_code')->toArray()
            );

            $response = [
                'status' => 'success',
                'profanity' => $filter_result['profanity'],
                'whitelisted_words_in_text' => $filter_result['whitelist_hits'],
                'grawlix' => $filter_result['grawlix'],
                'timestamp' => Carbon::now()->timestamp,
            ];
            $status = 200;
        } catch (\Exception $e) {
            $response = [
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => 'Server Error'
            ];
            $status = 500;
        }
        return response()->json($response, $status);
    }
}
