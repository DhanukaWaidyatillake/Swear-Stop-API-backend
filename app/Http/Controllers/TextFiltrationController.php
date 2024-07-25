<?php

namespace App\Http\Controllers;

use App\Events\SaveTextFilterResponseEvent;
use App\Http\Requests\FilterTextRequest;
use App\Services\TextFilterService;
use Illuminate\Http\Request;

class TextFiltrationController extends Controller
{
    public function textFilter(FilterTextRequest $request, TextFilterService $textFilterService)
    {
        $response =  $textFilterService->filterText($request->user()->id, $request->validated()['sentence']);

        SaveTextFilterResponseEvent::dispatch($request,$response);

        return $response;
    }
}
