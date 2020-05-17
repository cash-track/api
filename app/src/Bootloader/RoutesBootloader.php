<?php

declare(strict_types=1);

namespace App\Bootloader;

use Spiral\Annotations\AnnotationLocator;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Router\Route;
use Spiral\Router\RouterInterface;
use Spiral\Router\Target\Action;

class RoutesBootloader extends Bootloader
{
    /**
     * @param \Spiral\Annotations\AnnotationLocator $annotationLocator
     * @param RouterInterface $router
     */
    public function boot(AnnotationLocator $annotationLocator, RouterInterface $router): void
    {
        $methods = $annotationLocator->findMethods(\App\Annotation\Route::class);

        foreach ($methods as $method) {
            $name = sprintf(
                "%s.%s",
                $method->getClass()->getShortName(),
                $method->getMethod()->getShortName()
            );

            $route = new Route(
                $method->getAnnotation()->action,
                new Action(
                    $method->getClass()->getName(),
                    $method->getMethod()->getName()
                )
            );

            $route = $route->withVerbs(...(array)$method->getAnnotation()->verbs);

            $router->setRoute($name, $route);
        }
    }
}
