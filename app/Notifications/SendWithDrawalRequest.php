<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class SendWithDrawalRequest extends Notification
{

    use Queueable;

    protected $token, $currency_name, $amount, $wallet_address;



    /**

     * Create a new notification instance.

     *

     * @return void

     */

    public function __construct($emailData)
    {
        $this->token = $emailData['unique_confirmation_key'];

		$this->currency_name = $emailData['currency_name'];
		
		$this->amount = $emailData['amount'];
		
        $this->wallet_address = $emailData['wallet_address'];
		
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

        $message->subject(trans('emails.confirmWithDrawalSubject'))

                ->greeting(trans('emails.changeCryptoGreeting', ['firstname' => \Auth::User()->first_name, 'lastname' => \Auth::User()->last_name]))

                ->line(trans('emails.withdrawalFirstParagraph', ['unit'=>$this->currency_name, 'amount' => $this->amount]))
				
				->line(trans('emails.wallet_address', ['address'=>$this->wallet_address]))

				->line(trans('emails.withdrawalThreeParagraph'))
				
				 ->action(trans('emails.withdrawalButton'), route('change.request', ['token' => $this->token]))
				 
				->line(trans('emails.withdrawalFourParagraph'))
				
                ->line(trans('emails.changeCrytpohanks'));


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

