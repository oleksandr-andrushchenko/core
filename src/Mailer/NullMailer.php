<?php

namespace SNOWGIRL_CORE\Mailer;

class NullMailer implements MailerInterface
{
    public function send(string $receiver, string $subject, string $body): bool
    {
        return false;
    }

    public function notify(string $subject, string $body): int
    {
        return 0;
    }
}