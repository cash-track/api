<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\WalletShareMail;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class WalletShareMailTest extends TestCase
{
    public function testBuild(): void
    {
        $user = UserFactory::make();
        $sharer = UserFactory::make();
        $wallet = WalletFactory::make();
        $link = Fixtures::url();

        $mail = new WalletShareMail($user, $sharer, $wallet, $link);

        $mail = $mail->build();

        $this->assertArrayHasKey($user->email, $mail->getSwiftMessage()->getTo());
        $this->assertContains($user->fullName(), $mail->getSwiftMessage()->getTo());
        $this->assertNotEmpty($mail->getSwiftMessage()->getSubject());
    }
}
