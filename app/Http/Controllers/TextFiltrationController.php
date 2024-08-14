<?php

namespace App\Http\Controllers;

use App\Events\SaveTextFilterResponseEvent;
use App\Http\Requests\FilterTextRequest;
use App\Services\TextFilterService;
use Carbon\Carbon;

class TextFiltrationController extends Controller
{
    public function textFilter(FilterTextRequest $request, TextFilterService $textFilterService)
    {
//        try {
            $data = $request->validated();
            $filter_result = $textFilterService->filterText($request->user()->id, $data['sentence'], $data['moderation_categories']);
            $response = [
                'status' => 'success',
                'profanity' => $filter_result['profanity'],
                'whitelisted_words_in_text' => $filter_result['whitelist_hits'],
                'grawlix' => $filter_result['grawlix'],
                'timestamp' => Carbon::now()->timestamp,
            ];
            $status = 200;
//        } catch (\Exception $e) {
//            dd($e->getTraceAsString());
//            $response = [
//                'status' => 'failed',
//                'timestamp' => Carbon::now()->timestamp,
//                'error_message' => 'Server Error'
//            ];
//            $status = 500;
//        }

        SaveTextFilterResponseEvent::dispatch($request->all(), $response);

        return response($response, $status);

    }
}
