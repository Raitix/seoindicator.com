<?php

namespace App\Http\Requests\Api\V1\ContentDecay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for creating a refresh task.
 */
class CreateTaskRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'recommendation_type' => [
                'required',
                'string',
                Rule::in([
                    'content_refresh',
                    'title_rewrite',
                    'meta_description_rewrite',
                    'internal_links',
                    'external_links',
                    'image_optimization',
                    'schema_markup',
                    'content_expansion',
                    'content_consolidation',
                    'redirect_fix',
                    'canonical_fix',
                    'other',
                ]),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'owner_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date', 'after:today'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,done,skipped'],
            'source' => ['sometimes', 'string', 'in:manual,system,ai'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'A task title is required.',
            'title.max' => 'Task title cannot exceed 255 characters.',
            'recommendation_type.required' => 'A recommendation type is required.',
            'recommendation_type.in' => 'Invalid recommendation type.',
            'owner_user_id.exists' => 'The selected owner does not exist.',
            'due_date.after' => 'Due date must be in the future.',
        ];
    }
}
