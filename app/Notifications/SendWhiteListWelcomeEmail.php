<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendWhiteListWelcomeEmail extends Notification
{
    use Queueable;
    protected $token;

    /**
     * Create a new notification instance.
     *
     * SendNewActivationEmail constructor.
     *
     * @param $token
     */
    public function __construct($token)
    {
        $this->token = $token;
        $this->onQueue('social');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

     /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
		return (new MailMessage)
            ->subject(trans('emails.whiteListWelcomeEmail_Subject'))
            ->view
			('custom_email', 
				[
				'greetingMsg' => trans('emails.whiteListWelcomeEmail_Greeting'), 
				'userToken'=> $notifiable->token,
				'userName'=> $notifiable->user_name
				]
			);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
