<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use Cycle\ORM\ORMInterface;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

abstract class Mail
{
    use PayloadSerializer;

    private Email $message;

    private string $viewName = '';

    public function __construct()
    {
        $this->message = new Email();
    }

    public function hydrate(ORMInterface $orm): void
    {
    }

    /**
     * Set all mail parameters like fromAddress, toAddress, view, subject etc.
     *
     * @return \App\Service\Mailer\Mail
     */
    abstract public function build(): Mail;

    public function getEmailMessage(): Email
    {
        return $this->message;
    }

    /**
     * Set the subject of a message
     *
     * @param string $subject
     * @return \App\Service\Mailer\Mail
     */
    public function subject(string $subject): Mail
    {
        $this->message->subject($subject);

        return $this;
    }

    public function from(string $address, string $fullName = ''): Mail
    {
        $this->message->from(new Address($address, $fullName));

        return $this;
    }

    public function to(string $address, string $fullName = ''): Mail
    {
        $this->message->to(new Address($address, $fullName));

        return $this;
    }

    public function replyTo(string $address, string $fullName = ''): Mail
    {
        $this->message->replyTo(new Address($address, $fullName));

        return $this;
    }

    public function cc(string $address, string $fullName = ''): Mail
    {
        $this->message->cc(new Address($address, $fullName));

        return $this;
    }

    public function bcc(string $address, string $fullName = ''): Mail
    {
        $this->message->bcc(new Address($address, $fullName));

        return $this;
    }

    public function view(string $name): Mail
    {
        $this->viewName = $name;

        return $this;
    }

    public function render(ViewsInterface $views): Mail
    {
        $this->message->html($views->render($this->viewName, $this->buildRenderData()));

        return $this;
    }

    protected function buildRenderData(): array
    {
        $data = [];

        foreach (new \ReflectionClass($this)->getProperties() as $property) {
            if (! $property->isPublic()) {
                continue;
            }

            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }
}
