<?php

namespace SNOWGIRL_CORE\Mailer\Decorator;

use SNOWGIRL_CORE\Mailer\MailerInterface;

class AbstractMailerDecorator implements MailerInterface
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(string $receiver, string $subject, string $body): bool
    {
        return $this->mailer->send($receiver, $subject, $body);
    }

    public function notify(string $subject, string $body): int
    {
        return $this->mailer->notify($subject, $body);
    }
}