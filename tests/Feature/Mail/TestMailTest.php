<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Database\EntityHeader;
use App\Database\User;
use App\Mail\TestMail;
use App\Repository\UserRepository;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Symfony\Component\Mime\Address;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class TestMailTest extends TestCase implements DatabaseTransaction
{
    public function testHydrate(): void
    {
        $user = UserFactory::make();

        $mail = new TestMail($user->getEntityHeader());
        $mail->user = $user;

        $query = $this->getMockBuilder(Select::class)
                      ->onlyMethods(['fetchOne'])
                      ->addMethods(['where'])
                      ->disableOriginalConstructor()
                      ->getMock();
        $query->method('where')->with('id', $user->id)->willReturn($this->returnSelf());
        $query->expects($this->once())->method('fetchOne')->willReturn($user);

        $repo = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $repo->method('select')->willReturn($query);

        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();
        $orm->method('getRepository')->with(User::class)->willReturn($repo);

        $mail->hydrate($orm);

        $this->assertEquals($user, $mail->user);
    }

    public function testBuild(): void
    {
        $user = UserFactory::make();

        $mail = new TestMail($user->getEntityHeader());
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

    public function testBuildWithoutHydrate(): void
    {
        $user = UserFactory::make();

        $mail = new TestMail($user->getEntityHeader());

        $this->expectException(\RuntimeException::class);

        $mail->build();
    }

    public function testToPayload(): void
    {
        $user = UserFactory::make();

        $mail = new TestMail($user->getEntityHeader());

        $this->assertEquals([
            'class' => TestMail::class,
            'attrs' => [
                [
                    'class' => EntityHeader::class,
                    'attrs' => [User::class, ['id' => $user->id]]
                ],
            ],
        ], $mail->toPayload());
    }

    public function testFromPayload(): void
    {
        $user = UserFactory::make();

        $mail = TestMail::fromPayload([
            'class' => TestMail::class,
            'attrs' => [
                [
                    'class' => EntityHeader::class,
                    'attrs' => [User::class, ['id' => $user->id]]
                ],
            ],
        ]);

        $this->assertInstanceOf(TestMail::class, $mail);
        $this->assertInstanceOf(EntityHeader::class, $mail->userHeader);
        $this->assertEquals(User::class, $mail->userHeader->role);
        $this->assertEquals(['id' => $user->id], $mail->userHeader->params);
    }
}
