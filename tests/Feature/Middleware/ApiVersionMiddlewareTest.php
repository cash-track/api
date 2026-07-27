<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Spiral\Testing\Attribute\Env;
use Tests\TestCase;

class ApiVersionMiddlewareTest extends TestCase
{
    #[Env('GIT_TAG', 'v1.4.2')]
    #[Env('GIT_COMMIT', 'a1b2c3d')]
    public function testHeadersPresentOnSuccessResponse(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk()
            ->assertHasHeader('X-Ct-Api-Version', 'v1.4.2')
            ->assertHasHeader('X-Ct-Api-Sha', 'a1b2c3d');
    }

    /**
     * Pins the middleware ordering: registered after ErrorHandlerMiddleware it would never
     * see the response built from the unmatched-route exception.
     */
    #[Env('GIT_TAG', 'v1.4.2')]
    #[Env('GIT_COMMIT', 'a1b2c3d')]
    public function testHeadersPresentOnErrorResponse(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404)
            ->assertHasHeader('X-Ct-Api-Version', 'v1.4.2')
            ->assertHasHeader('X-Ct-Api-Sha', 'a1b2c3d');
    }

    #[Env('GIT_TAG', null)]
    #[Env('GIT_COMMIT', null)]
    public function testHeadersOmittedWhenBothUnset(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk()
            ->assertHeaderMissing('X-Ct-Api-Version')
            ->assertHeaderMissing('X-Ct-Api-Sha');
    }

    #[Env('GIT_TAG', 'v9.9.9')]
    #[Env('GIT_COMMIT', '')]
    public function testOnlyVersionHeaderPresentWhenShaIsEmpty(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk()
            ->assertHasHeader('X-Ct-Api-Version', 'v9.9.9')
            ->assertHeaderMissing('X-Ct-Api-Sha');
    }

    #[Env('GIT_TAG', '')]
    #[Env('GIT_COMMIT', 'deadbeef')]
    public function testOnlyShaHeaderPresentWhenVersionIsEmpty(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk()
            ->assertHeaderMissing('X-Ct-Api-Version')
            ->assertHasHeader('X-Ct-Api-Sha', 'deadbeef');
    }
}
