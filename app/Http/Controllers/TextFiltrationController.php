<?php

namespace App\Http\Controllers;

use App\Events\SaveTextFilterResponseEvent;
use App\Http\Requests\ApiTestFormRequest;
use App\Http\Requests\FilterTextRequest;
use App\Models\ProfanityCategory;
use App\Models\TestSentence;
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

            $status = 200;
        } catch (\Exception $e) {
            $response = [
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => 'Server Error'
            ];
            $status = 500;
        }

        SaveTextFilterResponseEvent::dispatch($request, $response);

        return response()->json($response, $status);
    }

    public function textFilterTester(ApiTestFormRequest $apiTestFormRequest, TextFilterService $textFilterService)
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
