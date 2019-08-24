<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoggerHelper;
use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\User;
use App\Quotation;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use jeremykenedy\LaravelRoles\Models\Role;
use Mail;
use Socialite;

class SocialController extends Controller
{
    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless()
    {
        $this->stateless = true;
        return $this;
    }

    /**
     *
     * @param  NULL
     * @return string Ip address
     */
    public function get_user_ip()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    /**
     * @param min
     * @param max
     * @return string
     */
    protected function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int)($log / 8) + 1; // length in bytes
        $bits = (int)$log + 1; // length in bits
        $filter = (int)(1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    protected function getUniqueKey($length)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max)];
        }
        return $token;
    }

    public function getSocialRedirect($provider, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'termsConditionRegister' => 'required',
        ], [
            'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
        ]);
        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        } else {
            $providerKey = Config::get('services.' . $provider);
            if (empty($providerKey)) {
                return redirect()
                    ->back()
                    ->with('error', trans('lendo.somethingWentWrong'));
            }
            return Socialite::driver($provider)->redirect();
        }
    }

    public function getSocialHandle($provider)
    {
        $success = '';
        $socialUser = null;
        if (Input::get('error') == 'access_denied') {
            return redirect()->to('login')
                ->with('error', trans('socials.denied'));
        }
        try {
            $socialUserObject = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->to('login')
                ->with('error', trans('socials.denied'));
        }
        //
        if (empty($socialUserObject->id)) {
            return redirect()->to('login')
                ->with('error', trans('socials.denied'));
        }
        //
        if (empty($socialUserObject->email)) {
            return redirect()->to('login')
                ->with('error', trans('socials.denied'));
        }
        //
        if (empty($socialUserObject->name)) {
            return redirect()->to('login')
                ->with('error', trans('socials.denied'));
        }
        // Check if email is already registered
        $userCheck = User::where('email', '=', $socialUserObject->email)->first();
        
        if (empty($userCheck)) {
            $role = Role::where('slug', '=', 'user')->first();
            $social_id = $socialUserObject->id;
            $email = $socialUserObject->email;
            $fullname = explode(' ', $socialUserObject->name);
            
            //check referrer key and genrate new
            referrer_key:
            $referrer_key = $this->getUniqueKey(5);
            $uniqueKeyData = DB::table('users')->where('referrer_key', $referrer_key)->value('referrer_key');
            if (count($uniqueKeyData) > 0) {
                GOTO referrer_key;
            }
            
            // Check if it is referenced by any user
            $default_referrer = DB::table('configurations')->where('name', 'Default-referrer-user-id')->pluck('defined_value');
            if (isset($_COOKIE["lendo_ref"])) {
                $refkey = $_COOKIE["lendo_ref"];
                $userRef = User::where('referrer_key', '=', $refkey)->first();
                if (isset($userRef) && $userRef->id != 0) {
                    $referrer_user_id = $userRef->id;
                }
            } else {
                $referrer_user_id = $default_referrer[0];
            }
            $userCheck = User::create([
                'user_name' => $this->genrateUniqueUsername($email),
                'first_name' => @$fullname[0],
                'last_name' => @$fullname[1],
                'email' => $email,
                'social_id' => $social_id,
                'role' => 2,
                'join_via' => (isset($provider) && !empty($provider) && $provider == 'google') ? 2 : ((isset($provider) && !empty($provider) && $provider == 'facebook') ? "3" : "4"),
                'password' => bcrypt(str_random(10)),
                'referrer_key' => $referrer_key,
                'referrer_user_id' => $referrer_user_id,
                'registration_ip' => \Request::getClientIp(true),
                'BTC_wallet_address' => NULL,
                'ETH_wallet_address' => NULL,
                'status' => 1,
            ]);
            $userCheck->attachRole($role);
            
            //For testing
            $userCheck = User::find($userCheck->id);
            if ($provider == 'facebook') {
                $userCheck->addValue('ELT_balance', '50');
                Transactions::createTransaction($userCheck->id, 'ELT', '50', 'Got Free 50 ELT on signup via facebook', 1, uniqid(), NULL);
            }
            $userCheck->save();
            // update referral count
            DB::statement("UPDATE users SET referrer_count=referrer_count+1 WHERE id=" . $referrer_user_id);
            $success = trans("message.socialRegiterSuccess");
            $record = [
                'message' => $email,
                'level' => 'INFO',
                'context' => 'Register social ' . $provider
            ];
            LoggerHelper::writeDB($record);
        }
        $socialUser = $userCheck;
        auth()->login($socialUser, true);
        $record = [
            'message' => $socialUserObject->email,
            'level' => 'INFO',
            'context' => 'Login'
        ];
        LoggerHelper::writeDB($record);
        return redirect('home')->with('success', $success);
    }

    /**
     * Generate Unique Username
     * This function call if we want to generate unique username
     * @var $username string (email address through create username)
     * @return $username string (unique username)
     *
     * user this function :
     * Auth_controller : userJoin(), userSocialJoinLogin()
     */
    public function genrateUniqueUsername($username)
    {
        $username = substr($username, 0, strpos($username, '@'));             //
        $username = str_replace(' ', '_', $username);                         // Replaces all spaces with hyphens.
        $username = preg_replace('/-+/', '_', $username);                     // Replaces multiple hyphens with single one.
        $name = $username = preg_replace('/[^A-Za-z0-9\_]/', '', $username);    // Removes special chars.
        $result = User::where('user_name', 'like', '%' . $username . '%')->pluck('user_name')->toArray();
        if (isset($result) && !empty($result)) {
            for ($i = 1; $i < COUNT($result) * 2; $i++) {
                if (in_array($username, $result)) {
                    $username = $name . $i;
                } else {
                    break;
                }
            }
        }
        return $username;
    }
}