<?php

namespace SNOWGIRL_CORE\Mailer\Decorator;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\DebuggerTrait;
use SNOWGIRL_CORE\Mailer\MailerInterface;

class DebuggerMailerDecorator extends AbstractMailerDecorator
{
    use DebuggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        parent::__construct($mailer);

        $this->logger = $logger;
    }

    public function send(string $receiver, string $subject, string $body): bool
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function notify(string $subject, string $body): int
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }
}