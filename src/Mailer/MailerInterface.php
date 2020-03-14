<?php

namespace SNOWGIRL_CORE\Mailer;

interface MailerInterface
{
    public function send(string $receiver, string $subject, string $body): bool;

    public function notify(string $subject, string $body): int;
}