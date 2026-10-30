<?php

namespace App\Services\Fetchers\Exceptions;

use Exception;

/**
 * Exception thrown when rate limited by the suggestion API.
 *
 * @see TASK-5-4
 * @see SPEC-005
 */
class RateLimitException extends Exception
{
    //
}
