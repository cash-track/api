<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class HealthcheckController
{
    #[Route(route: '/healthcheck', name: 'healthcheck', methods: 'GET')]
    public function healthcheck(ResponseWrapper $response): ResponseInterface
    {
        return $response->create(200);
    }
}
