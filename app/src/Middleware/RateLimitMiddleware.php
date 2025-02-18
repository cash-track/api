<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthMiddleware;
use App\Service\RateLimit\RateLimitHitInterface;
use App\Service\RateLimit\RateLimitInterface;
use App\Service\RateLimit\RateLimitReachedException;
use App\Service\RateLimit\RuleFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Translator\Traits\TranslatorTrait;

class RateLimitMiddleware implements MiddlewareInterface
{
    use TranslatorTrait;

    const array IP_HEADERS = [
        'Cf-Original-Connecting-IP',
        'X-Real-IP',
        'X-Forwarded-For',
    ];

    public function __construct(
        protected RateLimitInterface $rateLimit,
        protected RuleFactory $ruleFactory,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $request->getHeaderLine(AuthMiddleware::HEADER_USER_ID);
        $clientIp = $this->fetchIp($request);

        $rule = $this->ruleFactory->getRule($userId, $clientIp);

        try {
            $hit = $this->rateLimit->hit($rule);
        } catch (RateLimitReachedException $exception) {
            return $this->tooManyRequests($exception->getHit());
        }

        $response = $handler->handle($request);

        return $response->withAddedHeader('X-RateLimit-Limit', (string) $hit->getLimit())
                        ->withAddedHeader('X-RateLimit-Remaining', (string) $hit->getRemaining());
    }

    protected function fetchIp(ServerRequestInterface $request): string
    {
        foreach (self::IP_HEADERS as $header) {
            $ip = $request->getHeader($header)[0] ?? '';

            if ($ip !== '') {
                return $ip;
            }
        }

        return '';
    }

    private function tooManyRequests(RateLimitHitInterface $hit): ResponseInterface
    {
        return new JsonResponse([
            'message' => $this->say('error_rate_limit_reached'),
        ], 429)
            ->withAddedHeader('X-RateLimit-Limit', (string) $hit->getLimit())
            ->withAddedHeader('X-RateLimit-Remaining', (string) $hit->getRemaining())
            ->withAddedHeader('Retry-After', (string) $hit->getRetryAfter());
    }
}
