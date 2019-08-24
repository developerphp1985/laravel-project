<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendChangeNotification extends Notification
{
    use Queueable;
    protected $ChangeEntity;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($emailData)
    {
        $this->ChangeEntity = $emailData['ChangeEntity'];
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
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {				$first_name = isset($notifiable->first_name)?$notifiable->first_name:' User';		$last_name = isset($notifiable->last_name)?$notifiable->last_name:'';		
        $message = new MailMessage();
        $message->subject(trans('emails.changeNotificationSubject', [ 'ChangeEntity' =>  $this->ChangeEntity]))
				->greeting(trans('emails.changeNotificationGreeting', ['firstname' => $first_name, 'lastname' => $last_name]))
                ->line(trans('emails.changeNotificationMessage', [ 'ChangeEntity' =>  $this->ChangeEntity]))
                ->line(trans('emails.changeNotificationThanks'));
        return $message;
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
