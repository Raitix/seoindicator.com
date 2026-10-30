<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditRequest extends FormRequest
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
        return [
            'max_pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_depth' => ['nullable', 'integer', 'min:1', 'max:10'],
            'user_agent' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validated audit options with defaults.
     */
    public function getAuditOptions(): array
    {
        return [
            'max_pages' => $this->input('max_pages', config('audit.crawl.max_pages', 500)),
            'max_depth' => $this->input('max_depth', config('audit.crawl.max_depth', 3)),
            'user_agent' => $this->input('user_agent', config('audit.crawl.user_agent', 'SEOIndicator-Bot/1.0')),
        ];
    }
}
