<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendChangeIBanNumber extends Notification

{

    use Queueable;

    protected $token, $new_iban_number;



    /**

     * Create a new notification instance.

     *

     * @return void

     */

    public function __construct($emailData)

    {

        //		
        $this->token = $emailData['unique_confirmation_key'];

        $this->new_value = $emailData['new_value'];
		
		$this->old_value = $emailData['old_value'];
				

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

        $message->subject(trans('emails.changeIBanRequestSubject'));

        $message->greeting(trans('emails.changeIBanGreeting', ['firstname' => \Auth::User()->first_name, 'lastname' => \Auth::User()->last_name]));
				
		$message->line(trans('emails.changeIBanMessage'));
								
        $message->line(trans('emails.changeIBanNumber', ['oldibannumber' => $this->old_value['iban_number'], 'newibannumber' => $this->new_value['iban_number'] ]));
				
		$message->line(trans('emails.ChangeSwiftCode', ['oldswiftcode' => $this->old_value['Swift_code'], 'newswiftcode' => $this->new_value['Swift_code'] ]));
		
		if(isset($this->new_value['Beneficiary_name']))
		{
			$message->line(trans('emails.Beneficiary_name_change', ['old_beneficiary_name' => $this->old_value['Beneficiary_name'], 'new_beneficiary_name' => $this->new_value['Beneficiary_name'] ]));
		}
		
		if(isset($this->new_value['Bank_name']))
		{
			$message->line(trans('emails.Bank_name_change', ['old_bank_name' => $this->old_value['Bank_name'], 'new_bank_name' => $this->new_value['Bank_name'] ]));
		}
		
		if(isset($this->new_value['Bank_address']))
		{
			$message->line(trans('emails.Bank_address_change', ['old_bank_address' => $this->old_value['Bank_address'], 'new_bank_address' => $this->new_value['Bank_address'] ]));
		}
		
		if(isset($this->new_value['Bank_street_name']))
		{
			$message->line(trans('emails.Bank_street_change', ['old_bank_street' => $this->old_value['Bank_street_name'], 'new_bank_street' => $this->new_value['Bank_street_name'] ]));
		}
		
		if(isset($this->new_value['Bank_city_name']))
		{
			$message->line(trans('emails.Bank_city_change', ['old_bank_city' => $this->old_value['Bank_city_name'], 'new_bank_city' => $this->new_value['Bank_city_name'] ]));
		}
		
		if(isset($this->new_value['Bank_postal_code']))
		{
			$message->line(trans('emails.Bank_postal_change', ['old_bank_postal' => $this->old_value['Bank_postal_code'], 'new_bank_postal' => $this->new_value['Bank_postal_code'] ]));
		}
		
		if(isset($this->new_value['Bank_country']))
		{
			$message->line(trans('emails.Bank_country_change', ['old_bank_country' => $this->old_value['Bank_country'], 'new_bank_country' => $this->new_value['Bank_country'] ]));
		}

        $message->action(trans('emails.changeIBanNumberButton'), route('change.request', ['token' => $this->token]));

        $message->line(trans('emails.changeIBanNumberThanks'));

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

