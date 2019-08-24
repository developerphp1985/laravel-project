<?php

namespace App\Logic\Activation;

use App\Models\Activation;
use App\Models\User;
use App\Notifications\SendActivationEmail;
use App\Traits\CaptureIpTrait;
use Carbon\Carbon;

class ActivationRepository
{
    public function createTokenAndSendEmail(User $user)
    {
        $activations = Activation::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subHours(config('settings.timePeriod')))
            ->count();

        if ($activations >= config('settings.maxAttempts')) {
            return true;
        }

        //if user changed activated email to new one
        if ($user->activated) {
            $user->update([
                'status' => 0,
            ]);
        }

        // Create new Activation record for this user
        $activation = self::createNewActivationToken($user);
        

        // Send activation email notification
        self::sendNewActivationEmail($user, $activation->token);
    }

    public function createNewActivationToken(User $user)
    {
        $activation->user_id = $user->id;
        $activation->token = str_random(64);
        $activation->ip_address = \Request::getClientIp(true);
        $activation->save();

        return $activation;
    }

    public function sendNewActivationEmail(User $user, $token)
    {
        $user->notify(new SendActivationEmail($token));
    }

    public function deleteExpiredActivations()
    {
        Activation::where('created_at', '<=', Carbon::now()->subHours(72))->delete();
    }
}
