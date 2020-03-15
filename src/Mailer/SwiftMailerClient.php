<?php

namespace SNOWGIRL_CORE\Mailer;

use Swift_Mailer;
use Swift_Mime_SimpleMessage;
use Throwable;

class SwiftMailerClient extends Swift_Mailer
{
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $failedRecipients = (array)$failedRecipients;

        try {
            $sent = parent::send($message, $failedRecipients);
        } catch (Throwable $e) {
            foreach ($message->getTo() as $address => $name) {
                $failedRecipients[] = $address;
            }

            $sent = 0;
        }

        return $sent;
    }
}