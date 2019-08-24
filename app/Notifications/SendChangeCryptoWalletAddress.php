<?php



namespace App\Notifications;



use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class SendChangeCryptoWalletAddress extends Notification

{

    use Queueable;

    protected $token, $currency_name, $old_wallet_address, $new_wallet_address;



    /**

     * Create a new notification instance.

     *

     * @return void

     */

    public function __construct($emailData)
    {
        $this->token = $emailData['unique_confirmation_key'];

		$this->currency_name = $emailData['currency_name'];
		
		$this->old_wallet_address = $emailData['old_value'];
		
        $this->new_wallet_address = $emailData['new_value'];
		
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

        $message->subject(trans('emails.changeCryptoRequestSubject',['unit'=>$this->currency_name]))

                ->greeting(trans('emails.changeCryptoGreeting', ['firstname' => \Auth::User()->first_name, 'lastname' => \Auth::User()->last_name]))

                ->line(trans('emails.changeCryptoMessage', ['unit'=>$this->currency_name]))

				->line(trans('emails.wallet_address',['address'=>$this->new_wallet_address]))
				
				->line(trans('emails.changeCryptoMessage2'))
				
				 ->action(trans('emails.changeCryptoButton',['unit'=>$this->currency_name]), route('change.request', ['token' => $this->token]))
				
				->line(trans('emails.changeCryptoMessage3'))
				
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

