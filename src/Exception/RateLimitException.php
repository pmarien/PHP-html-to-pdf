<?php

namespace PMA\HtmlToPdf\Exception;

use DateTimeImmutable;
use RuntimeException;

/**
 * @author Philipp Marien
 */
class RateLimitException extends RuntimeException
{
    public function __construct(?string $retryAfter)
    {
        $message = 'Too many requests: Rate limit reached';
        if ($retryAfter) {
            $retryAfterDate = DateTimeImmutable::createFromFormat(DATE_RFC7231, $retryAfter);
            $message .= '; retry after ' . $retryAfterDate->format(DATE_ATOM);
        }

        parent::__construct($message, 2024020716049);
    }
}
