<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('ยืนยันอีเมลของคุณ - ' . config('app.name'))
            ->greeting('สวัสดี ' . $notifiable->name . '!')
            ->line('ขอบคุณที่ลงทะเบียนกับเรา กรุณาคลิกปุ่มด้านล่างเพื่อยืนยันอีเมลของคุณ')
            ->action('ยืนยันอีเมล', $verificationUrl)
            ->line('ลิงค์นี้จะหมดอายุใน 60 นาที')
            ->line('หากคุณไม่ได้ลงทะเบียนบัญชีนี้ กรุณาเพิกเฉยอีเมลนี้')
            ->salutation('ขอแสดงความนับถือ, ' . config('app.name'));
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        
        // Create a signed URL that expires in 60 minutes
        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
        
        // Extract token parameters from the signed URL
        $urlParts = parse_url($signedUrl);
        parse_str($urlParts['query'] ?? '', $queryParams);
        
        // Build frontend URL with verification parameters
        return $frontendUrl . '/email/verify/' . $notifiable->getKey() . '/' . sha1($notifiable->getEmailForVerification()) 
            . '?expires=' . ($queryParams['expires'] ?? '')
            . '&signature=' . ($queryParams['signature'] ?? '');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
