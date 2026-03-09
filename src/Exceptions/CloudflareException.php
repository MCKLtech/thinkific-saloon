<?php

declare(strict_types=1);

namespace WooNinja\ThinkificSaloon\Exceptions;

use Throwable;
use Saloon\Http\Response;
use Saloon\Exceptions\Request\ServerException;

/**
 * Thrown when Cloudflare returns a non-standard 5xx status code (520–527)
 * instead of a response from the origin server.
 *
 * The PHP exception $code is set to the actual HTTP status so that
 * callers can reliably inspect it (e.g. to decide whether to retry).
 */
class CloudflareException extends ServerException
{
    /**
     * Cloudflare error status codes that sit outside the standard HTTP range.
     *
     * @see https://developers.cloudflare.com/support/troubleshooting/cloudflare-errors/troubleshooting-cloudflare-5xx-errors/
     */
    public const CLOUDFLARE_STATUS_CODES = [520, 521, 522, 523, 524, 525, 526, 527, 530];

    public function __construct(Response $response, ?string $message = null, ?Throwable $previous = null)
    {
        // Pass the actual HTTP status as the PHP exception code so that
        // getCode() returns 520 (or whichever CF code was received) rather
        // than the default 0 that Saloon's RequestExceptionHelper uses.
        parent::__construct($response, $message, $response->status(), $previous);
    }

    /**
     * Returns true if the given HTTP status is a known Cloudflare error code.
     */
    public static function isCloudflareStatus(int $status): bool
    {
        return in_array($status, self::CLOUDFLARE_STATUS_CODES, true);
    }
}
