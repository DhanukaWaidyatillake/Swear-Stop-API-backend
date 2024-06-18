<?php

namespace App\Http\Requests;

use App\Models\SiteConfig;
use Illuminate\Foundation\Http\FormRequest;

class GenerateTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //Checking is signup token is valid
        return ($this->request->has('signup_secret') && SiteConfig::firstWhere('key', 'signup_secret')?->value == $this->request->get('signup_secret'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'signup_secret' => 'required'
        ];
    }
}
