<?php

namespace App\Http\Controllers;

use PerfectMoney;
use App\Models\User;
use App\Models\Transactions;
use App\Models\Configurations;
use App\Models\FileAttachments;
use App\Models\Phases;

use App\Helpers\LoggerHelper;
use App\Http\Controllers\Controller;
use Hiteshi\Coinpayments\Coinpayments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use jeremykenedy\LaravelRoles\Models\Role;

class WhiteListController extends Controller
{
	/**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth', ['except' => ['coinpaymentHook'] ]);
    }

    /**
     *
     */
    public function index($id,Request $request)
    {
		echo $id;die('...index here...');

    }
    
}
