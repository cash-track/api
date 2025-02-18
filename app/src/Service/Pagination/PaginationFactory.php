<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace App\Service\Pagination;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Exception\ScopeException;
use Spiral\Core\FactoryInterface;

/**
 * Pagination factory bind to active request scope in order to select page number.
 */
final class PaginationFactory implements PaginationProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly FactoryInterface $factory,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @param string $parameter
     * @param int $limit
     * @return \App\Service\Pagination\PaginatorInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[\Override]
    public function createPaginator(string $parameter = 'page', int $limit = 25): PaginatorInterface
    {
        if (!$this->container->has(ServerRequestInterface::class)) {
            throw new ScopeException('Unable to create paginator, no request scope found');
        }

        /**
         * @var array $query
         */
        $query = $this->container->get(ServerRequestInterface::class)->getQueryParams();

        // Getting page number
        $page = 0;
        if (!empty($query[$parameter]) && is_scalar($query[$parameter])) {
            $page = (int) $query[$parameter];
        }

        $paginator = $this->factory->make(Paginator::class, compact('limit', 'parameter'));

        if (! $paginator instanceof Paginator) {
            throw new \RuntimeException('Unable to resolve paginator');
        }

        return $paginator->withPage($page);
    }
}
