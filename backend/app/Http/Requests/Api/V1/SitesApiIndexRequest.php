<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for validating Sites API index query parameters.
 *
 * TASK-6-10: Sites API Endpoints
 */
class SitesApiIndexRequest extends FormRequest
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
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Get validated page parameter with default.
     */
    public function getPage(): int
    {
        $page = $this->input('page', 1);
        return max(1, (int) $page);
    }

    /**
     * Get validated per_page parameter with default and max cap.
     */
    public function getPerPage(): int
    {
        $perPage = $this->input('per_page', 20);
        return min(100, max(1, (int) $perPage));
    }
}
