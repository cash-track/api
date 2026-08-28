<?php

declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Service\Metrics\AppMetricsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * Wiring check: TagService::create must feed app_tags_created_total.
 */
final class TagMetricsTest extends TestCase implements DatabaseTransaction
{
    private UserFactory $userFactory;
    private TagFactory $tagFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->tagFactory = $this->getContainer()->get(TagFactory::class);
    }

    public function testTagCreateRecordsCreated(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $tag = TagFactory::make();

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementTagCreated');

        $this->withAuth($auth)->post('/v1/tags', [
            'name' => $tag->name,
            'icon' => $tag->icon,
            'color' => $tag->color,
        ])->assertOk();
    }

    public function testTagValidationFailureIsNotCounted(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $mock = $this->mockMetrics();
        $mock->expects($this->never())->method('incrementTagCreated');

        $this->withAuth($auth)->post('/v1/tags', [])->assertUnprocessable();
    }

    /**
     * @return AppMetricsInterface&MockObject
     */
    private function mockMetrics(): AppMetricsInterface&MockObject
    {
        $mock = $this->getMockBuilder(AppMetricsInterface::class)->getMock();

        $this->getContainer()->bind(AppMetricsInterface::class, static fn () => $mock);

        return $mock;
    }
}
