<?php



namespace App\Notifications;



use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class SendWhiteUserWelcomeEmail extends Notification

{

    use Queueable;

    protected $token;



    /**

     * Create a new notification instance.

     *

     * SendActivationEmail constructor.

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

        $message = new MailMessage();

        $message->subject(trans('emails.activationSubject_W'))

            ->greeting(trans('emails.activationGreeting_W'))

            ->line(trans('emails.activationMessage_W'))

            ->action(trans('emails.activationButton_W'), route('user.verifywhitelist', ['token' => $this->token]))

            ->line(trans('emails.activationThanks'));

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

