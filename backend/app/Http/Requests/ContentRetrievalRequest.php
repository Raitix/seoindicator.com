<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for content retrieval endpoints.
 *
 * Validates pagination, filtering, sorting, and field selection parameters.
 *
 * @see TASK-22-14
 * @see SPEC-022
 */
class ContentRetrievalRequest extends FormRequest
{
    /**
     * Valid fields for include/exclude parameters.
     */
    public const VALID_FIELDS = [
        'url',
        'depth',
        'title',
        'renderedHtml',
        'structuredText',
        'meta',
        'links',
        'size',
        'crawledAt',
    ];

    /**
     * Field aliases for include/exclude parameters.
     * Maps short names to full field names.
     */
    public const FIELD_ALIASES = [
        'text' => 'structuredText',
        'html' => 'renderedHtml',
    ];

    /**
     * Valid sort fields.
     */
    public const VALID_SORT_FIELDS = [
        'depth',
        'size',
        'crawledAt',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled at controller level
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Pagination
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Filtering
            'depth' => ['nullable', 'integer', 'in:0,1,2'],
            'search' => ['nullable', 'string', 'max:255'],

            // Sorting
            'sortBy' => ['nullable', 'string', Rule::in(self::VALID_SORT_FIELDS)],
            'sortOrder' => ['nullable', 'string', 'in:asc,desc'],

            // Field Selection
            'include' => ['nullable', 'string', 'regex:/^[a-zA-Z,]+$/'],
            'exclude' => ['nullable', 'string', 'regex:/^[a-zA-Z,]+$/'],

            // Content Transformation
            'minify' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'Page must be a valid number.',
            'page.min' => 'Page must be at least 1.',
            'perPage.integer' => 'PerPage must be a valid number.',
            'perPage.min' => 'PerPage must be at least 1.',
            'perPage.max' => 'PerPage cannot exceed 100.',
            'depth.in' => 'Depth must be 0, 1, or 2.',
            'search.max' => 'Search query cannot exceed 255 characters.',
            'sortBy.in' => 'Invalid sort field. Must be one of: depth, size, crawledAt.',
            'sortOrder.in' => 'Sort order must be "asc" or "desc".',
            'include.regex' => 'Include field contains invalid characters.',
            'exclude.regex' => 'Exclude field contains invalid characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'page' => $this->page ?? 1,
            'perPage' => $this->perPage ?? 50,
            'sortBy' => $this->sortBy ?? 'crawledAt',
            'sortOrder' => $this->sortOrder ?? 'desc',
        ]);
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate include fields
            if ($this->include) {
                $this->validateFieldList($validator, 'include', $this->include);
            }

            // Validate exclude fields
            if ($this->exclude) {
                $this->validateFieldList($validator, 'exclude', $this->exclude);
            }
        });
    }

    /**
     * Validate a comma-separated list of fields.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $paramName
     * @param string $fields
     */
    protected function validateFieldList($validator, string $paramName, string $fields): void
    {
        $requestedFields = explode(',', $fields);
        $validFieldsWithAliases = array_merge(self::VALID_FIELDS, array_keys(self::FIELD_ALIASES));
        $invalidFields = array_diff($requestedFields, $validFieldsWithAliases);

        if (!empty($invalidFields)) {
            $validator->errors()->add(
                $paramName,
                sprintf(
                    'Invalid field(s) in %s: %s. Valid fields: %s',
                    $paramName,
                    implode(', ', $invalidFields),
                    implode(', ', self::VALID_FIELDS)
                )
            );
        }
    }

    /**
     * Resolve field aliases to actual field names.
     *
     * @param array $fields
     * @return array
     */
    protected function resolveFieldAliases(array $fields): array
    {
        return array_map(function ($field) {
            return self::FIELD_ALIASES[$field] ?? $field;
        }, $fields);
    }

    /**
     * Get the include fields as an array.
     *
     * @return array|null
     */
    public function getIncludeFields(): ?array
    {
        if (!$this->include) {
            return null;
        }
        
        return $this->resolveFieldAliases(explode(',', $this->include));
    }

    /**
     * Get the exclude fields as an array.
     *
     * @return array|null
     */
    public function getExcludeFields(): ?array
    {
        if (!$this->exclude) {
            return null;
        }
        
        return $this->resolveFieldAliases(explode(',', $this->exclude));
    }

    /**
     * Get pagination parameters.
     *
     * @return array{page: int, perPage: int}
     */
    public function getPagination(): array
    {
        return [
            'page' => (int) $this->page,
            'perPage' => (int) $this->perPage,
        ];
    }

    /**
     * Get filter parameters.
     *
     * @return array{depth: int|null, search: string|null}
     */
    public function getFilters(): array
    {
        return [
            'depth' => $this->depth !== null ? (int) $this->depth : null,
            'search' => $this->search,
        ];
    }

    /**
     * Get sorting parameters.
     *
     * @return array{sortBy: string, sortOrder: string}
     */
    public function getSorting(): array
    {
        return [
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
