<?php

namespace App\Http\Requests;

use App\Models\ProfanityCategory;
use App\Models\TestSentence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiTestFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category_ids = ProfanityCategory::query()->select('id')->get()->pluck('id')->toArray();
        $category_ids[] = 0;

        return [
            'sentenceId' => ['required', Rule::in(TestSentence::query()->select('id')->get()->pluck('id')->toArray())],
            'categories' => 'required',
            'categories.*' => ['required', Rule::in($category_ids)]
        ];
    }
}
