<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Database\Currency;
use App\Repository\CurrencyRepository;
use App\Service\Mailer\MailerInterface;
use App\Service\UriService;
use App\Service\WalletService;
use Cycle\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $service = $this->getMockBuilder(WalletService::class)
                        ->onlyMethods(['store'])
                        ->setConstructorArgs([
                            $this->getMockBuilder(EntityManagerInterface::class)->getMock(),
                            $this->getContainer()->get(CurrencyRepository::class),
                            $this->getContainer()->get(UriService::class),
                            $this->getContainer()->get(MailerInterface::class),
                            $this->getContainer()->get(SluggerInterface::class),
                        ])
                        ->getMock();

        $user = UserFactory::make();
        $wallet = WalletFactory::make();
        $currencyCode = $wallet->defaultCurrencyCode;

        $service->expects($this->once())->method('store')->with($wallet)->willReturn($wallet);

        $wallet = $service->create($wallet, $user);

        $this->assertEquals($user, $wallet->users->first());
        $this->assertInstanceOf(Currency::class, $wallet->getDefaultCurrency());
        $this->assertEquals($currencyCode, $wallet->getDefaultCurrency()->code);
    }

    public function testCreateWithoutSlug(): void
    {
        $service = $this->getMockBuilder(WalletService::class)
                        ->onlyMethods(['setDefaultCurrency', 'store'])
                        ->setConstructorArgs([
                            $this->getMockBuilder(EntityManagerInterface::class)->getMock(),
                            $this->getContainer()->get(CurrencyRepository::class),
                            $this->getContainer()->get(UriService::class),
                            $this->getContainer()->get(MailerInterface::class),
                            $this->getContainer()->get(SluggerInterface::class),
                        ])
                        ->getMock();

        $user = UserFactory::make();
        $wallet = WalletFactory::make();
        $wallet->slug = '';

        $service->expects($this->once())->method('setDefaultCurrency')->with($wallet, $user->defaultCurrencyCode);
        $service->expects($this->once())->method('store')->with($wallet)->willReturn($wallet);

        $wallet = $service->create($wallet, $user);

        $this->assertEquals($user, $wallet->users->first());
        $this->assertNotEmpty($wallet->slug);
    }

    public function testCreateWithoutDefaultCurrency(): void
    {
        $currencyCode = strtoupper(Fixtures::string(3));

        $currencyRepository = $this->getMockBuilder(CurrencyRepository::class)
                                   ->disableOriginalConstructor()
                                   ->onlyMethods(['findByPK'])
                                   ->getMock();

        $currencyRepository->expects($this->once())
                           ->method('findByPK')
                           ->with($currencyCode)
                           ->willReturn(null);

        $service = $this->getMockBuilder(WalletService::class)
                        ->onlyMethods(['setSlugByName', 'store'])
                        ->setConstructorArgs([
                            $this->getMockBuilder(EntityManagerInterface::class)->getMock(),
                            $currencyRepository,
                            $this->getContainer()->get(UriService::class),
                            $this->getContainer()->get(MailerInterface::class),
                            $this->getContainer()->get(SluggerInterface::class),
                        ])
                        ->getMock();

        $user = UserFactory::make();
        $user->defaultCurrencyCode = $currencyCode;
        $wallet = WalletFactory::make();
        $wallet->defaultCurrencyCode = null;

        $service->expects($this->never())->method('setSlugByName')->with($wallet);
        $service->expects($this->never())->method('store')->with($wallet)->willReturn($wallet);

        $this->expectException(\RuntimeException::class);

        $service->create($wallet, $user);
    }

    public function testShareAlreadyShared(): void
    {
        $service = $this->getMockBuilder(WalletService::class)
                        ->onlyMethods(['store'])
                        ->setConstructorArgs([
                            $this->getMockBuilder(EntityManagerInterface::class)->getMock(),
                            $this->getContainer()->get(CurrencyRepository::class),
                            $this->getContainer()->get(UriService::class),
                            $this->getContainer()->get(MailerInterface::class),
                            $this->getContainer()->get(SluggerInterface::class),
                        ])
                        ->getMock();

        $user = UserFactory::make();
        $sharer = UserFactory::make();
        $wallet = WalletFactory::make();
        $wallet->users->add($user);

        $service->expects($this->never())->method('store')->with($wallet)->willReturn($wallet);

        $service->share($wallet, $user, $sharer);
    }

    public function testRevokeAlreadyRevoked(): void
    {
        $service = $this->getMockBuilder(WalletService::class)
                        ->onlyMethods(['store'])
                        ->setConstructorArgs([
                            $this->getMockBuilder(EntityManagerInterface::class)->getMock(),
                            $this->getContainer()->get(CurrencyRepository::class),
                            $this->getContainer()->get(UriService::class),
                            $this->getContainer()->get(MailerInterface::class),
                            $this->getContainer()->get(SluggerInterface::class),
                        ])
                        ->getMock();

        $user = UserFactory::make();
        $wallet = WalletFactory::make();

        $service->expects($this->never())->method('store')->with($wallet)->willReturn($wallet);

        $service->revoke($wallet, $user);
    }
}
