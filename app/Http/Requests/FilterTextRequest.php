<?php

namespace App\Http\Requests;

use App\Models\ProfanityCategory;
use Illuminate\Foundation\Http\FormRequest;

class FilterTextRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sentence' => 'required',
            'moderation_categories' => 'required|array',
            'moderation_categories.*' => 'required|string|in:' . implode(',', ProfanityCategory::all()->pluck('profanity_category_code')->toArray()) . ',*,all',
        ];
    }

    public function messages()
    {
        return [
            'moderation_categories.*.in' => 'Invalid moderation category specified'
        ];
    }
}
