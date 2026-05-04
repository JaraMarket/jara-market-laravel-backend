<?php

namespace App\Channels;

use App\Contracts\SmsGatewayInterface;
use Illuminate\Notifications\Notification;
use App\Services\SmsGateways\Termii;

class SmsChannel
{
    public function __construct(protected Termii $termii)
    {
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $phone = $notifiable->phone_number;
        if (!$phone) return;

        // Handle WhatsApp if provided
        if (method_exists($notification, 'toWhatsApp')) {
            try {
                $this->termii->sendWhatsApp($phone, $notification->toWhatsApp($notifiable));
            } catch (\Exception $e) {
                report($e);
            }
        }

        // Handle SMS if provided
        if (method_exists($notification, 'toSms')) {
            try {
                $this->termii->send($phone, $notification->toSms($notifiable));
            } catch (\Exception $e) {
                report($e);
            }
        }
    }
}
