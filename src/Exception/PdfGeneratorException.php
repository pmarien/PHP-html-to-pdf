<?php

namespace PMA\HtmlToPdf\Exception;

use RuntimeException;

/**
 * @author Philipp Marien
 */
class PdfGeneratorException extends RuntimeException
{
    public function __construct(int $status, string $reason)
    {
        parent::__construct(
            'The generator responded with http status ' . $status . ' (' . $reason . ')',
            20240207164231
        );
    }
}
