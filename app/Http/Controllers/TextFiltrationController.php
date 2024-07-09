<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterTextRequest;
use App\Services\TextFilterService;
use Illuminate\Http\Request;

class TextFiltrationController extends Controller
{
    public function textFilter(FilterTextRequest $request, TextFilterService $textFilterService)
    {
        $textFilterService->filterText($request->user()->id, $request->validated()['sentence']);
    }
}
