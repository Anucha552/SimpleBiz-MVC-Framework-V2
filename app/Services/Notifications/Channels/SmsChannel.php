<?php

namespace App\Services\Notifications\Channels;

use App\Core\Logger;
use App\Services\Notifications\Notification;

class SmsChannel implements NotificationChannelInterface
{
    private ?Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }

    public function send(Notification $notification, array $recipients): bool
    {
        if ($this->logger) {
            $this->logger->warning('notification.sms.not_implemented', [
                'subject' => $notification->subject,
                'recipients' => count($recipients),
            ]);
        }

        return false;
    }
}
