<?php

namespace App\Services;
use App\Models\Admin;
use App\Models\User;   
use App\Models\notification as NotificationModel;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

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

    public function sendNotification($deviceToken, $title, $body, $imageUrl, $data = [])
    {
        $notification = Notification::create($title, $body, $imageUrl);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)->withData($data);

        $notify = $this->messaging->send($message);
        $this->writeNotification($deviceToken, $title, $body, $imageUrl);
        return $notify;
    }
    public function sendNotificationToall($title, $body, $imageUrl, $data = [])
    {
        $token = User::whereNotNull('notification_token')->get();
        $notification = Notification::create($title, $body, $imageUrl);

        foreach ($token as $deviceToken) {
            $message = CloudMessage::withTarget('token', $deviceToken->notification_token)
                ->withNotification($notification)->withData($data);
            $notify = $this->messaging->send($message);
        }
        $this->writeNotification("", $title, $body, $imageUrl);
        return $notify;
    }

    public function sendToAdmin($title, $body, $imageUrl, $data = [])
    {
        $token = Admin::whereNotNull('notification_token')->where('role', 'admin')->get();
        $notification = Notification::create($title, $body, $imageUrl);

        foreach ($token as $deviceToken) {
            $message = CloudMessage::withTarget('token', $deviceToken->notification_token)
                ->withNotification($notification)->withData($data);
            $notify = $this->messaging->send($message);
        }
        return $notify;
    }
}