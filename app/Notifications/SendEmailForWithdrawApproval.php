<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendEmailForWithdrawApproval extends Notification
{

    use Queueable;

    protected $amount, $status, $transaction_id, $currency_name,  $wallet_address, $first_name, $last_name;


    /**

     * Create a new notification instance.

     *

     * @return void

     */

    public function __construct($emailData)
    {
		$this->amount = $emailData['amount'];
		
		$this->status = strtolower($emailData['status']);
		
		$this->transaction_id = $emailData['transaction_id'];
		
		$this->currency_name = $emailData['currency_name'];
	
        $this->wallet_address = $emailData['wallet_address'];
		
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

        $message->subject(trans('emails.withdrawalApproveRejectSubject',['status'=>$this->status]))

                ->greeting(trans('emails.changeCryptoGreeting', ['firstname' =>$this->first_name, 'lastname' => $this->last_name]))

                ->line(trans('emails.withdrawalConfirmedFirstParagraph', ['unit'=>$this->currency_name, 'amount' => $this->amount]))
								
				->line(trans('emails.withdrawalStatusMessage', ['address'=>$this->wallet_address,'status'=>$this->status]))

				->line(trans('emails.withdrawalApproveRejectContent',['transaction_id'=>$this->transaction_id]))
				
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

