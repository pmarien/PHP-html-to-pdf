<?php

namespace PMA\HtmlToPdf\Enum;

/**
 * @author Philipp Marien
 */
enum ContentDisposition: string
{
    case INLINE = 'inline';
    case ATTACHMENT = 'attachment';

    public function getHeaderValue(?string $filename = null): string
    {
        $headerValue = $this->value;
        if ($filename) {
            $headerValue .= '; filename="' . $filename . '"';
        }

        return $headerValue;
    }
}
