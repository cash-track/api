<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\EmailConfirmation;
use App\Database\User;
use App\Mail\EmailConfirmationMail;
use App\Repository\EmailConfirmationRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use App\Service\UriService;
use Cycle\ORM\EntityManagerInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="emailConfirmationService")
 */
class EmailConfirmationService extends HelperService
{
    /**
     * @var \App\Repository\EmailConfirmationRepository
     */
    protected $repository;

    /**
     * EmailConfirmationService constructor.
     *
     * @param \Cycle\ORM\EntityManagerInterface $tr
     * @param \App\Repository\UserRepository $userRepository
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @param \App\Service\UriService $uri
     * @param \App\Repository\EmailConfirmationRepository $repository
     */
    public function __construct(
        EntityManagerInterface $tr,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UriService $uri,
        EmailConfirmationRepository $repository
    ) {
        parent::__construct($tr, $userRepository, $mailer, $uri);

        $this->repository = $repository;
    }

    /**
     * @param \App\Database\User $user
     * @return void
     * @throws \Throwable
     */
    public function create(User $user): void
    {
        if ($user->isEmailConfirmed) {
            throw new \RuntimeException('You already confirmed your account email');
        }

        $confirmation            = new EmailConfirmation();
        $confirmation->email     = $user->email;
        $confirmation->token     = $this->generateToken();
        $confirmation->createdAt = new \DateTimeImmutable();

        $this->store($confirmation);

        $this->mailer->send(new EmailConfirmationMail($user, $this->uri->emailConfirmation($confirmation->token)));
    }

    /**
     * @param \App\Database\EmailConfirmation $confirmation
     * @return \App\Database\EmailConfirmation
     * @throws \Throwable
     */
    public function store(EmailConfirmation $confirmation): EmailConfirmation
    {
        $this->tr->persist($confirmation);
        $this->tr->run();

        return $confirmation;
    }

    /**
     * @param \App\Database\User $user
     * @return void
     * @throws \Throwable
     */
    public function reSend(User $user): void
    {
        /** @var \App\Database\EmailConfirmation|null $confirmation */
        $confirmation = $this->repository->findByPK($user->email);
        if ($confirmation instanceof EmailConfirmation) {
            if ($this->isThrottled($confirmation->createdAt)) {
                throw new \RuntimeException('Previous confirmation is already sent less than ' . self::RESEND_TIME_LIMIT . ' seconds ago');
            }

            $this->tr->delete($confirmation);
            $this->tr->run();
        }

        $this->create($user);
    }

    /**
     * @param string $token
     * @return void
     * @throws \Throwable
     */
    public function confirm(string $token): void
    {
        $confirmation = $this->repository->findByToken($token);

        if (! $confirmation instanceof EmailConfirmation) {
            throw new \RuntimeException('Wrong confirmation token');
        }

        if ($this->isExpired($confirmation->createdAt)) {
            throw new \RuntimeException('Confirmation link are expired');
        }

        $user = $this->userRepository->findByEmail((string) $confirmation->email);

        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to find user linked to confirmation link');
        }

        $user->isEmailConfirmed = true;

        $this->tr->persist($user);
        $this->tr->delete($confirmation);
        $this->tr->run();
    }
}
