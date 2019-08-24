<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Helpers\CommonHelper;

use App\Models\Activation;

use App\Models\User;

use App\Models\Transactions;

use App\Models\Configurations;

use App\Models\FileAttachments;

use App\Notifications\SendActivationEmail;

use App\Traits\ActivationTrait;

use Auth;

use Carbon\Carbon;

use Illuminate\Support\Facades\Route;

use jeremykenedy\LaravelRoles\Models\Role;



class ActivateController extends Controller

{

    use ActivationTrait;

    private static $userHomeRoute = 'home';

    private static $adminHomeRoute = 'manager/dashboard';

    private static $activationView = 'auth.activation';

	private static $activationRoute = 'activate';
	
	



    public static function getUserHomeRoute()

    {

        return self::$userHomeRoute;

    }



    public static function getAdminHomeRoute()

    {

        return self::$adminHomeRoute;

    }



    public static function getActivationView()

    {

        return self::$activationView;

    }



    public static function getActivationRoute()

    {

        return self::$activationRoute;

    }



    /**     

     * Create a new controller instance.     *     

     * @return void 

     */

    public function __construct()

    {

        $this->middleware('auth');

    }



    public static function activeRedirect($user, $currentRoute)

    {

        if ($user->status) {

            if ($user->isAdmin()) {

                return redirect()->route(self::getAdminHomeRoute())->with('success', trans('auth.alreadyActivated'));

            }

            return redirect()->route(self::getUserHomeRoute())->with('success', trans('auth.alreadyActivated'));

        }

        return false;

    }



     public function initial()

    {

        $user = Auth::user();

        $lastActivation = Activation::where('user_id', $user->id)->get()->last();

        $currentRoute = Route::currentRouteName();

        $rCheck = $this->activeRedirect($user, $currentRoute);

        if ($rCheck) {

            return $rCheck;

        }

        $data = ['email' => $user->email, 'date' => $user->created_at->format('m/d/Y'),];

        return view($this->getActivationView())->with($data);

    }



    public function activate($token)

    {

        $user = Auth::user();

        $currentRoute = Route::currentRouteName();

        $role = Role::where('slug', '=', 'user')->first();

        $rCheck = $this->activeRedirect($user, $currentRoute);

        if ($rCheck) {

            return $rCheck;

        }

        $activation = Activation::where('token', $token)->get()->where('user_id', $user->id)->first();

        if (empty($activation)) {

            return redirect()->route(self::getActivationRoute())->with('error', trans('auth.invalidToken'));

        }

        $user->status = true;

        $user->detachAllRoles();

        $user->attachRole($role);

        $user->last_update_ip = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);

        $user->save();

        $allActivations = Activation::where('user_id', $user->id)->get();

        foreach ($allActivations as $anActivation) {

            $anActivation->delete();

        }

        if ($user->isAdmin()) {

            return redirect()->route(self::getAdminHomeRoute())->with('success', trans('auth.successActivated'));

        }

        return redirect()->route(self::getUserHomeRoute())->with('success', trans('auth.successActivated'));

    }

   public function airdrop_activate($token)

    {

        $user = Auth::user();

        $currentRoute = Route::currentRouteName();

        $role = Role::where('slug', '=', 'user')->first();

        $rCheck = $this->activeRedirect($user, $currentRoute);

        if ($rCheck) {

            return $rCheck;

        }

        $activation = Activation::where('token', $token)->get()->where('user_id', $user->id)->first();

        if (empty($activation)) {

            return redirect()->route(self::getActivationRoute())->with('error', trans('auth.invalidToken'));

        }

        $user->status = true;

        $user->detachAllRoles();

        $user->attachRole($role);

        $user->last_update_ip = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);

        $user->save();
        
		// distribute bonus
		// give 5 ELT bonus to referral user
		  
		
			$referrer_user_id = $user->referrer_user_id;
			
				$user_e = $user->email;
				$value = '5';
				$type  = '4';
				$description = "Got referral bonus of ".$value.'ELT on community registration of email :'.$user_e;
				$this->give_bonus($referrer_user_id,$value,$description,$type);
				
	    

		// give 50 ELT bonus to new user - 25 for loan + 25 for card
		
		if($user->reserve_card > 0)
		{
		$value = '25';
		$type  = '3';
		$description = "Got community registration bonus for card priority of ".$value.'ELT';
		$this->give_bonus($user->id,$value,$description,$type);		
		}
		if($user->loan_amount_requested > 0)
		{
		$value = '25';
		$type  = '2';
		$description = "Got community registration bonus for loan priority of ".$value.'ELT';
		$this->give_bonus($user->id,$value,$description,$type);		
		}
		
		
		
        
		$allActivations = Activation::where('user_id', $user->id)->get();

        foreach ($allActivations as $anActivation) {

            $anActivation->delete();

        }

        if ($user->isAdmin()) {

            return redirect()->route(self::getAdminHomeRoute())->with('success', trans('auth.successActivated'));

        }

        return redirect()->route(self::getUserHomeRoute())->with('success', trans('auth.successActivated'));

    }

	
	public function give_bonus($user_id,$value,$description,$type)
	{
		
		$user = User::where('id', '=', $user_id)->first();
		// get user kyc status
		//$kyc_status = FileAttachments::fetch_kyc_status($user_id);
		
		$status = 2;	
		$show_to_user = 0;
		
		/*if($kyc_status == 1)
		{
		$status = 1;
		$show_to_user = 1; 		
		}*/
		
		$ledger = 'ELT';
        $transaction_id = uniqid();
		$phaseId = NULL;
        $description = $description .' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
        $Transaction = Transactions::createTransaction($user_id, $ledger, $value, $description, $status, $transaction_id, $phaseId, NULL, $type,NULL,$type_name='ico-wallet',0, $show_to_user);
		
		if($status == 1)
		{
		$user->addValue('ELT_balance', $value);
		$userUpdate = $user->save();
		}
		
		
	}
	
    public function activationRequired()

    {

        $user = Auth::user();

        $lastActivation = Activation::where('user_id', $user->id)->get()->last();

        $currentRoute = Route::currentRouteName();

        $rCheck = $this->activeRedirect($user, $currentRoute);

        if ($rCheck) {

            return $rCheck;

        }

        if ($user->status == 0) {

            $activationsCount = Activation::where('user_id', $user->id)->where('created_at', '>=', Carbon::now()->subHours(config('settings.timePeriod')))->count();

            if ($activationsCount > config('settings.timePeriod')) {

                $data = ['email' => $user->email, 'hours' => config('settings.timePeriod'),];

                return view('auth.exceeded')->with($data);

            }

        }

        $data = ['email' => $user->email, 'date' => $lastActivation->created_at->format('m/d/Y'),];

        return view($this->getActivationView())->with($data);

    }



    public function sendNewActivationEmail(User $user, $token)

    {

        $user->notify(new SendActivationEmail($token));

    }



    public function resend()

    {

        $user = Auth::user();

        Activation::where('user_id', $user->id)->get()->last();

        $currentRoute = Route::currentRouteName();

        if ($user->status == 0) {

            $activationsCount = Activation::where('user_id', $user->id)->where('created_at', '>=', Carbon::now()->subHours(config('settings.timePeriod')))->count();

            if ($activationsCount >= config('settings.maxAttempts')) {

                $data = ['email' => $user->email, 'hours' => config('settings.timePeriod'),];

                return view('auth.exceeded')->with($data);

            }

            $activation = new Activation();

            $activation->user_id = $user->id;

            $activation->token = str_random(64);

            $activation->ip_address = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);

            $activation->save();

            self::sendNewActivationEmail($user, $activation->token);

            return redirect()->route(self::getActivationRoute())->with('success', trans('auth.activationSent'));

        }

        return $this->activeRedirect($user, $currentRoute)->with('success', trans('auth.alreadyActivated'));

    }



    public function airdropactivate($token)

    {

        $user = Auth::user();

        $currentRoute = Route::currentRouteName();

        $role = Role::where('slug', '=', 'user')->first();

        $rCheck = $this->activeRedirect($user, $currentRoute);

        if ($rCheck) {

            return $rCheck;

        }

        $activation = Activation::where('token', $token)->get()->where('user_id', $user->id)->first();

        if (empty($activation)) {

            return redirect()->route(self::getActivationRoute())->with('error', trans('auth.invalidToken'));

        }

        $user->status = true;

        $user->detachAllRoles();

        $user->attachRole($role);

        $user->last_update_ip = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);

        $user->save();

        $allActivations = Activation::where('user_id', $user->id)->get();

        foreach ($allActivations as $anActivation) {

            $anActivation->delete();

        }

        if ($user->isAdmin()) {

            return redirect()->route(getAdminHomeRoute())->with('success', trans('auth.successActivated'));

        }

        return redirect()->route(self::getUserHomeRoute())->with('success', trans('auth.successActivated'));

    }

}