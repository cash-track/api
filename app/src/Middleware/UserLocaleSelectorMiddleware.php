<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Translator\Translator;

final class UserLocaleSelectorMiddleware implements MiddlewareInterface
{
    private array $availableLocales;

    public function __construct(
        private readonly Translator $translator
    ) {
        $this->availableLocales = $this->translator->getCatalogueManager()->getLocales();
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userLocale = $request->getAttribute(AuthMiddleware::USER_LOCALE);

        if (!empty($userLocale) && in_array($userLocale, $this->availableLocales)) {
            $this->translator->setLocale($userLocale);
        }

        return $handler->handle($request);
    }
}
