<?php

declare(strict_types=1);

return [
    'http' => [
        401 => 'Please log in to access this page.',
        402 => 'Payment is required to access this page. Contact the application administrator for help.',
        403 => 'You do not have permission to access this page. Contact the application administrator if you need access.',
        404 => 'We could not find this page. Check the address or return to the home page.',
        419 => 'Your session has expired. Please log in again before retrying your action.',
        429 => 'You have made too many requests. Please wait a moment before trying again.',
        500 => 'Something went wrong on our end. Please try again later or return to the home page.',
        503 => 'The service is temporarily unavailable. Please try again later.',
    ],

    'generic_action_failed' => 'Something went wrong on our end (reference code: :ref). Please try again. If the problem persists, include this reference code when contacting an admin.',
];
