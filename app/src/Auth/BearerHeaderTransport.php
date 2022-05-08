<?php

declare(strict_types=1);

namespace App\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Auth\HttpTransportInterface;

/**
 * Reads and writes auth tokens via headers.
 */
final class BearerHeaderTransport implements HttpTransportInterface
{
    const TRANSPORT = 'bearer-header';

    /** @var string */
    private $header;

    /**
     * @param string $header
     */
    public function __construct(string $header = 'Authorization')
    {
        $this->header = $header;
    }

    /**
     * @inheritDoc
     */
    public function fetchToken(Request $request): ?string
    {
        if ($request->hasHeader($this->header)) {
            return $this->parseToken($request->getHeaderLine($this->header));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function commitToken(
        Request $request,
        Response $response,
        string $tokenID,
        \DateTimeInterface $expiresAt = null
    ): Response {
        $headerLine = $this->buildHeaderLine($tokenID);

        if ($request->hasHeader($this->header) && $request->getHeaderLine($this->header) === $headerLine) {
            return $response;
        }

        return $response->withAddedHeader($this->header, $headerLine);
    }

    /**
     * @inheritDoc
     */
    public function removeToken(Request $request, Response $response, string $tokenID): Response
    {
        return $response;
    }

    /**
     * @param string $tokenID
     * @return string
     */
    private function buildHeaderLine(string $tokenID): string
    {
        return sprintf('Bearer %s', $tokenID);
    }

    /**
     * @param string $headerLine
     * @return string|null
     */
    private function parseToken(string $headerLine): ?string
    {
        if ($headerLine && preg_match('/Bearer\s*(\S+)\b/i', $headerLine, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }
}
