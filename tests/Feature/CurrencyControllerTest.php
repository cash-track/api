<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repository\CurrencyRepository;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected CurrencyRepository $currencyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->currencyRepository = $this->getContainer()->get(CurrencyRepository::class);
    }

    public function testListRequireAuth(): void
    {
        $response = $this->get('/currencies');

        $response->assertUnauthorized();
    }

    public function testListReturnCurrencies(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get('/currencies');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $currencies = $this->currencyRepository->findAll();

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(count($currencies), $body['data']);

        foreach ($currencies as $currency) {
            /** @var \App\Database\Currency $currency */
            $this->assertArrayContains($currency->code, $body, 'data.*.code');
            $this->assertArrayContains($currency->name, $body, 'data.*.name');
            $this->assertArrayContains($currency->char, $body, 'data.*.char');
        }
    }

    public function testFeaturedRequireAuth(): void
    {
        $response = $this->get('/currencies/featured');

        $response->assertUnauthorized();
    }

    public function testFeaturedReturnFeaturedCurrencies(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get('/currencies/featured');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $currencies = $this->currencyRepository->getFeatured();

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(count($currencies), $body['data']);

        foreach ($currencies as $currency) {
            /** @var \App\Database\Currency $currency */
            $this->assertArrayContains($currency->code, $body, 'data.*.code');
            $this->assertArrayContains($currency->name, $body, 'data.*.name');
            $this->assertArrayContains($currency->char, $body, 'data.*.char');
        }
    }
}
