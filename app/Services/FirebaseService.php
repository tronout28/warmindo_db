<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(__DIR__ . '/../../firebase-auth.json');
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, $imageUrl)
    {
        $notification = Notification::create($title, $body, $imageUrl);

        // $message = CloudMessage::withTarget('token', $deviceToken)
        //     ->withNotification($notification);

        // $notify = $this->messaging->send($message);
        // return $notify;
    }
    public function sendNotificationToall($title, $body, $imageUrl)
    {
        $notification = Notification::create($title, $body, $imageUrl);

        $message = CloudMessage::new()
            ->withNotification($notification);

        $notify = $this->messaging->send($message);
        return $notify;
    }
}