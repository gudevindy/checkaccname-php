<?php
declare(strict_types=1);

namespace GuDevIndy\CheckAccName\Exception;

use Exception;

/**
 * Base exception — caught this to catch all SDK errors.
 */
class CheckAccNameException extends Exception
{
    /** @var array<string,mixed>|null Raw response body parsed as array (if any) */
    public ?array $responseBody = null;

    /** @var int|null HTTP status code (if HTTP error) */
    public ?int $httpStatus = null;

    public static function fromHttp(int $status, ?array $body, string $fallback = 'API error'): self
    {
        $msg = $body['message'] ?? $body['errorMessage'] ?? $body['error'] ?? $fallback;
        $cls = match (true) {
            $status === 401, $status === 403 => AuthException::class,
            $status === 429                  => RateLimitException::class,
            $status >= 500                   => NetworkException::class,
            default                          => InquiryException::class,
        };
        $e = new $cls((string)$msg, $status);
        $e->httpStatus   = $status;
        $e->responseBody = $body;
        return $e;
    }
}
