<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Notification;

use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Notifications\Messages\MailMessage;



class UserLoginNotify extends Notification

{

    use Queueable;

    protected $platform;
	
	protected $ip_address;
	
	protected $date_time;



    /**

     * Create a new notification instance.

     *

     * SendActivationEmail constructor.

     *

     * @param $token

     */

    public function __construct($platform='website', $ip_address, $date_time)
    {
		$this->platform = $platform;
		
        $this->ip_address = $ip_address;
		
		$this->date_time = $date_time;

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
		$first_name = isset($notifiable->first_name)?$notifiable->first_name:' User';		
		
		$last_name = isset($notifiable->last_name)?$notifiable->last_name:'';	
				
		$message = new MailMessage();

        $message->subject(trans('emails.loginNotificationSubject'))
				
				->greeting(trans('emails.changeNotificationGreeting', ['firstname' => $first_name, 'lastname' => $last_name]))
				
                ->line(trans('emails.UserLoginNotifyMessage', [ 'platform' => $this->platform, 'ip_address' => $this->ip_address, 'date_time' => $this->date_time ]))

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

