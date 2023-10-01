<?php

declare(strict_types=1);

namespace Feature\Mail;

use App\Mail\Newsletter\DeletionNoticeMail;
use App\Mail\Newsletter\TelegramChannelMail;
use Symfony\Component\Mime\Address;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class NewsletterMailsTest extends TestCase
{
    public function buildDataProvider(): array
    {
        return [
            [TelegramChannelMail::class],
            [DeletionNoticeMail::class],
        ];
    }

    /**
     * @dataProvider buildDataProvider
     * @param string $class
     * @return void
     */
    public function testBuild(string $class): void
    {
        $user = UserFactory::make();

        $mail = new $class($user->getEntityHeader());
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
