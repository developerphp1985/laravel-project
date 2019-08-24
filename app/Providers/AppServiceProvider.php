<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);

        //Add this custom validation rule.
        Validator::extend('alpha_spaces', function ($attribute, $value) {
            if(isset($value) && !empty($value)){
                // This will only accept alpha and spaces. 
                // If you want to accept hyphens use: /^[\pL\s-]+$/u.
                return preg_match('/^[\pL\s]+$/u', $value); 
            } else {
                return true;
            }
        });

        Validator::extend('alpha_spaces_hypen', function ($attribute, $value) {
            if(isset($value) && !empty($value)){
                return preg_match('/^[\pL\s-]+$/u', $value); 
            } else {
                return true;
            }
        });

        Validator::extend('valid_mobile_number', function ($attribute, $value) {
            if(isset($value) && !empty($value))
            {
                if (substr(trim($value), 0, 1) == '+' || substr(trim($value), 0, 2) == '00') 
                {
                    if(preg_match('/^[0-9 \- +)(]+$/i', $value))
                    {
                        return true;
                    }
                }
            }
            return false;
        });

        Validator::extend('valid_postal_code', function ($attribute, $value) {

            if(isset($value) && !empty($value))
            {
                if(preg_match('/^[a-zA-Z0-9 \-]+$/i', $value))
                {
                    return true;
                }
            }
            return false;
        });
		
		
		Validator::extend('alpha_spaces_addrr', function ($attribute, $value) {

            if(isset($value) && !empty($value))
            {
				if(preg_match('/^[a-zA-Z0-9 ,.#_-]+$/i', $value))
				{				  
					return true;
				}
				else{
					return false;
				}
            }
            return true;
        });
		
		Validator::extend('alpha_spaces_city', function ($attribute, $value) {

            if(isset($value) && !empty($value))
            {
				if(preg_match('/^[a-zA-Z0-9 -]+$/i', $value))
				{				  
					return true;
				}
            }
            return false;
        });
		
		
        //
		$this->app['request']->server->set("HTTPS", true);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}