<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use Cycle\ORM\ORMInterface;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

abstract class Mail
{
    /**
     * @use \App\Service\Mailer\PayloadSerializer<\App\Service\Mailer\Mail>
     */
    use PayloadSerializer;

    /**
     * @var \Symfony\Component\Mime\Email
     */
    private $message;

    /**
     * @var string
     */
    private $viewName = '';

    /**
     * Mail constructor.
     */
    public function __construct()
    {
        $this->message = new Email();
    }

    /**
     * @param \Cycle\ORM\ORMInterface $orm
     * @return void
     */
    public function hydrate(ORMInterface $orm)
    {
    }

    /**
     * Set all mail parameters like fromAddress, toAddress, view, subject etc.
     *
     * @return \App\Service\Mailer\Mail
     */
    abstract public function build(): Mail;

    /**
     * @return \Symfony\Component\Mime\Email
     */
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

    /**
     * @param string $address
     * @param string $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function from(string $address, string $fullName = ''): Mail
    {
        $this->message->from(new Address($address, $fullName));

        return $this;
    }

    /**
     * @param string $address
     * @param string $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function to(string $address, string $fullName = ''): Mail
    {
        $this->message->to(new Address($address, $fullName));

        return $this;
    }

    /**
     * @param string $address
     * @param string $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function replyTo(string $address, string $fullName = ''): Mail
    {
        $this->message->replyTo(new Address($address, $fullName));

        return $this;
    }

    /**
     * @param string $address
     * @param string $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function cc(string $address, string $fullName = ''): Mail
    {
        $this->message->cc(new Address($address, $fullName));

        return $this;
    }

    /**
     * @param string $address
     * @param string $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function bcc(string $address, string $fullName = ''): Mail
    {
        $this->message->bcc(new Address($address, $fullName));

        return $this;
    }

    /**
     * @param string $name
     * @return \App\Service\Mailer\Mail
     */
    public function view(string $name): Mail
    {
        $this->viewName = $name;

        return $this;
    }

    /**
     * @param \Spiral\Views\ViewsInterface $views
     * @return $this
     */
    public function render(ViewsInterface $views): Mail
    {
        $this->message->html($views->render($this->viewName, $this->buildRenderData()));

        return $this;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    protected function buildRenderData(): array
    {
        $data = [];

        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
            if (! $property->isPublic()) {
                continue;
            }

            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }
}
