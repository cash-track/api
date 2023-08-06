<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Database\EntityHeader;
use App\Mail\WalletShareMail;
use Cycle\ORM\ORMInterface;
use Symfony\Component\Mime\Address;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class WalletShareMailTest extends TestCase
{
    public function testHydrate(): void
    {
        $user = UserFactory::make();
        $sharer = UserFactory::make();
        $wallet = WalletFactory::make();
        $link = Fixtures::url();

        $userHeader = $this->getMockBuilder(EntityHeader::class)->disableOriginalConstructor()->getMock();
        $userHeader->expects($this->once())->method('hydrate')->willReturn($user);

        $sharerHeader = $this->getMockBuilder(EntityHeader::class)->disableOriginalConstructor()->getMock();
        $sharerHeader->expects($this->once())->method('hydrate')->willReturn($sharer);

        $walletHeader = $this->getMockBuilder(EntityHeader::class)->disableOriginalConstructor()->getMock();
        $walletHeader->expects($this->once())->method('hydrate')->willReturn($wallet);

        $mail = new WalletShareMail($userHeader, $sharerHeader, $walletHeader, $link);

        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();

        $mail->hydrate($orm);
    }

    public function testBuild(): void
    {
        $user = UserFactory::make();
        $sharer = UserFactory::make();
        $wallet = WalletFactory::make();
        $link = Fixtures::url();

        $mail = new WalletShareMail($user->getEntityHeader(), $sharer->getEntityHeader(), $wallet->getEntityHeader(), $link);
        $mail->user = $user;

        $mail = $mail->build();

        $to = $mail->getEmailMessage()->getTo();
        $this->assertIsArray($to);
        $this->assertCount(1, $to);
        $this->assertInstanceOf(Address::class, $to[0]);
        $this->assertEquals($user->email, $to[0]->getAddress());
        $this->assertEquals($user->fullName(), $to[0]->getName());
        $this->assertNotEmpty($mail->getEmailMessage()->getSubject());
    }
}
