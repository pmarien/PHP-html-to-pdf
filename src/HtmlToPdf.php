<?php

namespace PMA\HtmlToPdf;

use PMA\HtmlToPdf\Enum\ContentDisposition;
use PMA\HtmlToPdf\Exception\PdfGeneratorException;
use PMA\HtmlToPdf\Exception\RateLimitException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Philipp Marien
 */
class HtmlToPdf
{
    private string $generatorUri = 'https://pdf.philipp-marien.de/generate';

    private array $options = [
        'encoding' => 'utf-8'
    ];

    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ClientInterface $client,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ?string $apiKey = null
    ) {
    }

    protected function minifyHtml(string $html): string
    {
        return preg_replace(
            [
                '/(\n|^)(\x20+|\t)/',
                '/(\n|^)\/\/(.*?)(\n|$)/',
                '/\n/',
                '/<!--.*?-->/',
                '/(\x20+|\t)/', # Delete multi space (Without \n)
                '/>\s+</', # strip whitespaces between tags
                '/(["\'])\s+>/', # strip whitespaces between quotation ("') and end tags
                '/=\s+(["\'])/' # strip whitespaces between = "'
            ],
            ["\n", "\n", " ", "", " ", "><", "$1>", "=$1"],
            $html
        );
    }

    final protected function generate(string $html): ResponseInterface
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->getGeneratorUri() . '?' . http_build_query($this->getOptions()))
            ->withBody(
                $this->streamFactory->createStream(
                    $this->minifyHtml($html)
                )
            )
            ->withHeader('Content-Type', 'text/html');

        if ($this->apiKey) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->apiKey);
        }

        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() === 429) {
            throw new RateLimitException($response->getHeader('Retry-After')[0] ?? null);
        }

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
                return $response;
            case 429:
                throw new RateLimitException($response->getHeader('Retry-After')[0] ?? null);
            default:
                throw new PdfGeneratorException($response->getStatusCode(), $response->getReasonPhrase());
        }
    }

    final protected function filename(string $filename): string
    {
        if (!str_ends_with($filename, '.pdf')) {
            $filename .= '.pdf';
        }

        return preg_replace("/[^A-Za-z0-9.\-]/", '_', $filename);
    }

    protected function createResponseFromGenerate(
        string $filename,
        string $html,
        ContentDisposition $mode
    ): ResponseInterface {
        return $this->responseFactory->createResponse()
            ->withBody($this->generate($html)->getBody())
            ->withHeader('Content-Disposition', $mode->getHeaderValue($this->filename($filename)))
            ->withHeader('Content-Type', 'application/pdf');
    }

    final public function getGeneratorUri(): string
    {
        return $this->generatorUri;
    }

    final  public function setGeneratorUri(string $generatorUri): void
    {
        $this->generatorUri = $generatorUri;
    }

    final public function getOptions(): array
    {
        return $this->options;
    }

    final public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function createFile(string $filename, string $html): void
    {
        file_put_contents(
            $this->filename($filename),
            $this->generate($html)->getBody()->getContents()
        );
    }

    public function inlineResponse(string $filename, string $html): ResponseInterface
    {
        return $this->createResponseFromGenerate($filename, $html, ContentDisposition::INLINE);
    }

    public function attachmentResponse(string $filename, string $html): ResponseInterface
    {
        return $this->createResponseFromGenerate($filename, $html, ContentDisposition::ATTACHMENT);
    }
}
