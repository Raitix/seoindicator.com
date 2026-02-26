<?php

namespace App\Services\Fetchers\Exceptions;

use Exception;

/**
 * Exception thrown when a fetch request times out.
 *
 * @see TASK-5-4
 * @see SPEC-005
 */
class TimeoutException extends Exception
{
    //
}
