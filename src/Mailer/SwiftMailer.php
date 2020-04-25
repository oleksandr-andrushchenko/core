<?php

namespace SNOWGIRL_CORE\Mailer;

use Psr\Log\LoggerInterface;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

class SwiftMailer implements MailerInterface
{
    private $sender;
    private $host;
    private $port;
    private $encryption;
    private $username;
    private $password;
    private $notifiers;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    public function __construct(string $sender, string $host, int $port, string $encryption, string $username, string $password, array $notifiers, LoggerInterface $logger)
    {
        $this->sender = $sender;
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->notifiers = $notifiers;
        $this->logger = $logger;
    }

    public function getClient(): Swift_Mailer
    {
        if (null === $this->mailer) {
            $transport = new Swift_SmtpTransport($this->host, $this->port, $this->encryption);

            $transport->setUsername($this->username);
            $transport->setPassword($this->password);

            $this->mailer = new SwiftMailerClient($transport);
        }

        return $this->mailer;
    }

    public function createMessage($receiver, string $subject, string $body = null): Swift_Message
    {
        $message = new Swift_Message($subject);
        $message->setFrom($this->sender);
        $message->setTo($receiver);
        $message->setBody($body);
        $message->setContentType('text/html');
        $message->setCharset('utf-8');

        return $message;
    }

    public function createNotifyMessage(string $subject, string $body): Swift_Message
    {
        return $this->createMessage($this->notifiers, $subject, $body);
    }

    public function send(string $receiver, string $subject, string $body): bool
    {
        $message = $this->createMessage($receiver, $subject, $body);

        $context = [
            'sender' => $this->sender,
            'receiver' => $receiver,
            'subject' => $subject,
            'body' => $body,
        ];

        if ($this->getClient()->send($message)) {
            $this->logger->debug('email sent', $context);
        }

        $this->logger->warning('email not sent', $context);

        return false;
    }

    public function notify(string $subject, string $body): int
    {
        $output = 0;

        foreach ($this->notifiers as $notifier) {
            if ($this->send($notifier, $subject, $body)) {
                $output += 1;
            }
        }

        return $output;
    }
}