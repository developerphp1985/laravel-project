<?php



namespace App\Notifications;



use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Notification;

use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Notifications\Messages\MailMessage;



class AuthenticateUserViaOTP extends Notification

{

    use Queueable;

    protected $otp;



    /**

     * Create a new notification instance.

     *

     * @return void

     */

    public function __construct($otp)

    {

        //

        $this->otp = $otp;

        $this->email = 'rupesh.choudhary+admin@hiteshi.com';

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

        $message->subject(trans('emails.UserOTPRequestSubject'))

                    ->greeting(trans('emails.UserOTPRequestGreeting'))

                        ->line(trans('emails.UserOTPRequestMessage', [ 'otp' => $this->otp ]))

                            ->line(trans('emails.ThankYouText'));

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

