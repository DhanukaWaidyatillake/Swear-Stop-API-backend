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
        try {
            $filter_result = $textFilterService->filterText($request->user()->id, $request->validated()['sentence']);
            $response = [
                'status' => 'success',
                'profanity' => $filter_result['profanity'],
                'whitelisted_words_in_text' => $filter_result['whitelist_hits'],
                'timestamp' => Carbon::now()->timestamp,
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => 'failed',
                'timestamp' => Carbon::now()->timestamp,
                'error_message' => 'Server Error'
            ];
        }

        SaveTextFilterResponseEvent::dispatch($request->all(), $response);

        return $response;

    }
}
