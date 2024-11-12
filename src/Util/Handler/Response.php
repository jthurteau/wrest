<?php

/**
* Utility traits for handling middleware responses
*/

declare(strict_types=1);

namespace Saf\Util\Handler;

trait Response {

    public static function isSuccess(mixed $response): bool
    {
        return is_array($response) && key_exists('success', $response) && $response['success'];
    }

}

