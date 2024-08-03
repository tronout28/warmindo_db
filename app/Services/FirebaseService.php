<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use App\Models\User;   
use App\Models\Notification as NotificationModel;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(__DIR__ . '/../../firebase-auth.json');
        $this->messaging = $factory->createMessaging();
    }

    public function writeNotification($notification_token, $title, $body, $imageUrl)
    {
        $user = User::where('notification_token', $notification_token)->first();
        $attributes = [
            'title' => $title,
            'body' => $body,
            'image' => $imageUrl,
            'user_id' => $user ? $user->id : null,
        ];
        $notification = NotificationModel::create($attributes);
        return $notification;
    }

    public function sendNotification($deviceToken, $title, $body, $imageUrl)
    {
        $notification = Notification::create($title, $body, $imageUrl);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        $notify = $this->messaging->send($message);
        return $notify;
    }
    public function sendNotificationToall($title, $body, $imageUrl)
    {
        $token = User::whereNotNull('notification_token')->pluck('notification_token')->toArray();
        $notification = Notification::create($title, $body, $imageUrl);

        $message = CloudMessage::new()
            ->withNotification($notification);

            $messages[] = $message;
            $notify = $this->messaging->sendMulticast($messages, $token);
            $this->writeNotification("", $title, $body, $imageUrl);
             return $notify;
    }
}