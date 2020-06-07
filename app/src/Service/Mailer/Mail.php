<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use Spiral\Views\ViewsInterface;

abstract class Mail
{
    /**
     * @var \Swift_Message
     */
    private $message;

    /**
     * @var string
     */
    private $viewName;

    /**
     * Mail constructor.
     */
    public function __construct()
    {
        $this->message = new \Swift_Message();
    }

    /**
     * Set all mail parameters like fromAddress, toAddress, view, subject etc.
     *
     * @return \App\Service\Mailer\Mail
     */
    abstract public function build(): Mail;

    /**
     * @return \Swift_Message
     */
    public function getSwiftMessage(): \Swift_Message
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
        $this->message->setSubject($subject);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function from(string $address, string $fullName = null): Mail
    {
        $this->message->addFrom($address, $fullName);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function to(string $address, string $fullName = null): Mail
    {
        $this->message->addTo($address, $fullName);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function replyTo(string $address, string $fullName = null): Mail
    {
        $this->message->addReplyTo($address, $fullName);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function cc(string $address, string $fullName = null): Mail
    {
        $this->message->addCc($address, $fullName);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $fullName
     * @return \App\Service\Mailer\Mail
     */
    public function bcc(string $address, string $fullName = null): Mail
    {
        $this->message->addBcc($address, $fullName);

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
        $this->message->setBody(
            $views->render($this->viewName, $this->buildRenderData()),
            'text/html',
            'utf-8',
        );

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
