<?php

/*
Send Change Email Request

*/


namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendChangeEmailRequest extends Notification

{

    use Queueable;

    protected $token, $oldEmail, $newEmail, $first_name, $last_name;

    

    /**

     * Create a new notification instance.

     *

     * @return void

     */

    public function __construct($emailData)
    {

        $this->token = $emailData['unique_confirmation_key'];

		$this->oldEmail = isset($emailData['old_value']) ? $emailData['old_value'] : \Auth::User()->email;
		
        $this->newEmail = $emailData['new_value'];
		
		$this->first_name = isset($emailData['first_name'])?$emailData['first_name']:\Auth::User()->first_name;
		
		$this->last_name = isset($emailData['last_name'])?$emailData['last_name']:\Auth::User()->last_name;

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

    {

        $message = new MailMessage();

        $message->subject(trans('emails.changeEmailRequestSubject'))

                ->greeting(trans('emails.changeEmailGreeting', ['firstname' => $this->first_name, 'lastname' => $this->last_name]))

                ->line(trans('emails.changeEmailMessage', ['oldEmail' => $this->oldEmail, 'newEmail' => $this->newEmail]))

                ->action(trans('emails.changeEmailButton'), route('change.request', ['token' => $this->token]))

                ->line(trans('emails.changeEmailThanks'));

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

