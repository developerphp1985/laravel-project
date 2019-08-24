<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


    Route::get('/', function () {
    //
    if (isset($_GET) && !empty($_GET)) {

        return redirect('/social/handle/google?' . http_build_query(Request::query()));
    } else {
        return Redirect::to('/login');
    }
})->middleware('revalidate');

//Socialite Register Routes
    Route::get('/social/redirect/{provider}', ['as' => 'social.redirect', 'uses' => 'Auth\SocialController@getSocialRedirect'])->middleware('revalidate');
    Route::get('/social/handle/{provider}', ['as' => 'social.handle', 'uses' => 'Auth\SocialController@getSocialHandle'])->middleware('revalidate');

//Referral Cookies
   //Route::get('/r/{refkey}', 'Auth\RegisterController@userReferral')->middleware('revalidate');
    Route::get('/referkey/{telegramkey}', 'Auth\RegisterController@userTelegramReferral')->middleware('revalidate');
    Route::get('/r/{refkey}', 'Auth\RegisterController@airdrop_userReferral')->middleware('revalidate');
	
//coinpayment-hook
    Route::match(["get", "post"], '/coinpayment-hook', ['as' => 'coinpayment.hook', 'uses' => 'UserController@coinpaymentHook']);
    Auth::routes();

    Route::group(['middleware' => ['auth', 'revalidate', 'role:unverified']], function () {
        // Activation Routes
        Route::get('/email-verification', ['as' => 'activate', 'uses' => 'Auth\ActivateController@initial']);
        //Route::get('/email-verification/{token}', ['as' => 'authenticated.activate', 'uses' => 'Auth\ActivateController@activate']);
        Route::get('/email-verification/{token}', ['as' => 'authenticated.airdrop_activate', 'uses' => 'Auth\ActivateController@airdrop_activate']);
	
        // Airdrop Activation email
        Route::get('/email-verification-airdrop/{token}', ['as' => 'airdrop.activate', 'uses' => 'Auth\ActivateController@airdropactivate']);
        Route::get('/activation-required', ['uses' => 'Auth\ActivateController@activationRequired'])->name('activation-required');
        Route::get('/activation', ['as' => 'authenticated.activation-resend', 'uses' => 'Auth\ActivateController@resend']);
    
    });

    Route::group(['middleware' => ['web', 'guest', 'auth', 'revalidate', 'role:user']], function () {
        Route::get('/home', 'HomeController@index')->name('home');
		
		Route::get('/home/eltwebwallet', 'HomeController@eltwebwallet')->name('eltwebwallet');
		
		Route::get('/serverdatetime', 'HomeController@serverdatetime')->name('serverdatetime');
		
		Route::get('/home/eltwebwalletAjax', 'HomeController@eltwebwalletAjax')->name('eltwebwalletAjax');
		
		Route::match(["get","post"], '/home/ajaxhomebctransactions', ['as' => 'home.ajaxhomebctransactions', 'uses' => 'HomeController@ajaxhomebctransactions']);
		
		Route::match(["get","post"], '/home/setwalletpin.html', ['as' => 'wallet.setwalletpin', 'uses' => 'HomeController@setwalletpin']);
		
		Route::match(["get","post"], '/ajaxgenerateToken', ['as' => 'home.ajaxgenerateToken', 'uses' => 'HomeController@ajaxgenerateToken']);
	
        Route::get('/home/icowallet', ['as' => 'home.icowallet', 'uses' => 'HomeController@icowallet']);
        Route::match(["get", "post"], '/home/preferences', ['as' => 'home.preferences', 'uses' => 'HomeController@preferences']);
        
		Route::match(["get", "post"], '/home/transactions', ['as' => 'home.transactions', 'uses' => 'HomeController@transactions']);
        Route::match(["get", "post"], '/home/referrals', ['as' => 'home.referrals', 'uses' => 'HomeController@referrals']);
		Route::match(["get", "post"], '/home/referralsnew', ['as' => 'home.referralsnew', 'uses' => 'HomeController@referralsnew']);
		
		Route::match(["get", "post"], '/home/referralstc', ['as' => 'home.referralstc', 'uses' => 'HomeController@referralstc']);
		
		Route::match(["get", "post"], '/home/salesleaderboard', ['as' => 'home.salesleaderboard', 'uses' => 'HomeController@salesleaderboard']);
		
		Route::match(["get", "post"], '/home/referralmember', ['as' => 'home.referralmember', 'uses' => 'HomeController@referralmember']);
		
		Route::match(["get", "post"], '/home/invoices', ['as' => 'home.invoices', 'uses' => 'HomeController@invoices']);
		
		Route::get('/home/invoice-detail/{id}', ['as' => 'home.invoice-detail', 'uses' => 'HomeController@invoice_detail']);
		 
        Route::match(["get", "post"], '/buy-token', ['as' => 'buy.token', 'uses' => 'UserController@buyToken']);
		
		Route::match(["get", "post"], '/cryptomania', ['as' => 'cryptomania', 'uses' => 'UserController@cryptomania']);
		
		Route::match(["get", "post"], '/bank-transfer', ['as' => 'buy.bank-transfer', 'uses' => 'UserController@bankTransfer']);
		
		Route::match(["get", "post"], '/credit-card-payment', ['as' => 'buy.creditcard', 'uses' => 'UserController@creditcardpayment']);
		
		Route::match(["get", "post"], '/payment-success', ['as' => 'buy.paymentsuccess', 'uses' => 'UserController@creditcardpaymentsuccess']);
		
		Route::match(["get", "post"], '/proforma-detail/{number?}', ['as' => 'buy.proforma_detail', 'uses' => 'UserController@proforma_detail', function ($number = 0) {
        }]);
		
		Route::match(["get", "post"], '/buy-token-deal', ['as' => 'buy.token.deal', 'uses' => 'UserController@buyTokenDeal']);
		
		Route::match(["get", "post"], '/buy-token-deal-ajax', ['as' => 'buy.token.deal.ajax', 'uses' => 'UserController@buyTokenDealAjax']);
		
		
		//Pay from account
        Route::post('/buy-token-confirm-deal', ['as' => 'buytoken.confirm.deal', 'uses' => 'UserController@buyTokenConfirmDeal']);
		
		Route::match(["get", "post"], '/buy-token-deal-thanks', ['as' => 'buy.token.deal.thanks', 'uses' => 'UserController@buyTokenDealThanks']);
		
		Route::match(["get", "post"], '/buy-token-deal-coin-thanks', ['as' => 'buy.token.deal.coin.thanks', 'uses' => 'UserController@buyTokenDealCoinThanks']);
		
		Route::match(["get", "post"], '/buy-token-ajax', ['as' => 'buy.token.ajax', 'uses' => 'UserController@buyTokenAjax']);
		
		Route::match(["get", "post"], '/buy-currencyinfo', ['as' => 'buy.token.currencyinfo', 'uses' => 'UserController@getUserBuyCurrencyInfo']);
		
        Route::match(["get", "post"], '/home/getVerified', ['as' => 'kyc.getVerified', 'uses' => 'UserController@getVerified']);

        //Pay from account
        Route::post('/buy-token-confirm', ['as' => 'buytoken.confirm', 'uses' => 'UserController@buyTokenConfirm']);
		
		//Pay with coin payment
        Route::post('/buy-token-confirm-coinpayment', ['as' => 'buytoken.confirmcoinpayment', 'uses' => 'UserController@buyTokenConfirmCoinpayment']);
		
		//Pay with coin payment
        Route::post('/buy-token-confirm-coinpayment-deal', ['as' => 'buytoken.confirmcoinpayment.deal', 'uses' => 'UserController@buyTokenConfirmCoinpaymentDeal']);
		
        // Email, change confirmation link
        Route::get('/confirmChange/{token}', ['as' => 'change.request', 'uses' => 'HomeController@confirmChange']);
        //perfect money success
        Route::match(["get", "post"], '/topupEUR/success', 'UserController@topupEURSuccess');
        //perfect money failure
        Route::match(["get", "post"], '/topupEUR/fail', 'UserController@topupEURFail');
        
        Route::match(["get", "post"], '/home/verification', ['as' => 'home.verification', 'uses' => 'HomeController@verification']);
        Route::match(["get", "post"], '/home/whitepaper', ['as' => 'home.whitepaper', 'uses' => 'HomeController@whitepaper']);
        Route::match(["get", "post"], '/home/faq', ['as' => 'home.faq', 'uses' => 'HomeController@faq']);
		Route::match(["get", "post"], '/home/videos', ['as' => 'home.videos', 'uses' => 'HomeController@videos']);
		Route::match(["get", "post"], '/home/tutorialvideos', ['as' => 'home.videostutorial', 'uses' => 'HomeController@videostutorial']);
		Route::match(["get", "post"], '/home/presentations', ['as' => 'home.presentations', 'uses' => 'HomeController@presentations']);
        Route::match(["get", "post"], '/home/mediakit', ['as' => 'home.mediakit', 'uses' => 'HomeController@mediakit']);
		Route::match(["get", "post"], '/home/rewards', ['as' => 'home.referrals_detail', 'uses' => 'HomeController@referrals_detail']);
		Route::match(["get","post"], '/withdrawbalance', ['as' => 'withdrawbalance', 'uses' => 'UserController@withdrawbalance']);
		Route::match(["get","post"], '/withdrawamount', ['as' => 'withdrawamount', 'uses' => 'HomeController@withdrawamount']);
    
        Route::match(["get","post"], '/withdrawBTC', ['as' => 'withdraw.btc', 'uses' => 'UserController@withdrawBTC']);
        Route::match(["get","post"], '/withdrawETH', ['as' => 'withdraw.eth', 'uses' => 'UserController@withdrawETH']);
        Route::match(["get","post"], '/withdrawEUR', ['as' => 'withdraw.eur', 'uses' => 'UserController@withdrawEUR']);

        Route::post('/transaction-load-date-ajax/{data}', 'HomeController@transactionLoadDateAjax');
		
	
		Route::post('/update_app_pin', ['as' => 'app.setpin', 'uses' => 'HomeController@setpin']);
		
		Route::post('/savepin', ['as' => 'app.savepin', 'uses' => 'HomeController@savepin']);
		
		Route::post('/setpinrequest', ['as' => 'app.setpinrequest', 'uses' => 'HomeController@setpinrequest']);
				
		Route::post('/transfer_elt', ['as' => 'app.transfer_elt', 'uses' => 'HomeController@transfer_elt']);
		 
		Route::post('/execute_transfer_elt', ['as' => 'app.execute_transfer_elt', 'uses' => 'HomeController@execute_transfer_elt']);
		
		Route::get('/home/downline/{id}', ['as' => 'home.downline', 'uses' => 'HomeController@downline']);
		
		Route::post('/update_bonus_opt', ['as' => 'user.update_bonus_opt', 'uses' => 'HomeController@update_bonus_opt']);
		
		Route::match(["get","post"], '/home/ajaxwithdrawalrequest', ['as' => 'home.ajaxwithdrawalrequest', 'uses' => 'HomeController@ajaxwithdrawalrequest']);
		
		Route::match(["get","post"], '/ajaxupdatepricephase', ['as' => 'home.ajaxupdatepricephase', 'uses' => 'HomeController@ajaxupdatepricephase']);
		
		
    });

// Admin urls without login
    Route::group(['prefix' => Config::get('constants.admin_url'), 'middleware' => ['web', 'revalidate']], function () {
        Route::get('login', ['as' => 'admin.login', 'uses' => 'Admin\AdminLoginController@index']);
        Route::post('login', ['as' => 'admin.login', 'uses' => 'Admin\AdminLoginController@login']);
        Route::get('testemail/{email}', ['as' => 'admin.testemail', 'uses' => 'Admin\AdminLoginController@testemail']);
    
    });
    
// Admin urls with login
    Route::group(['prefix' => Config::get('constants.admin_url'), 'middleware' => ['web', 'guest', 'auth', 'revalidate', 'role:admin']], function () {
    
        //2FA
        Route::match(["get", "post"], '/2-step-verification', ['as' => 'admin.twoSetpVarification', 'uses' => 'Admin\AdminHomeController@twoSetpVarification']);
    
        // Dashboard
        Route::get('dashboard', ['as' => 'admin.dashboard', 'uses' => 'Admin\AdminHomeController@index']);
		
        // User listing
        Route::get('users', ['as' => 'admin.users', 'uses' => 'Admin\AdminUserController@index']);
		
		// Matches The "/admin99/id/add" URL
        Route::match(["get", "post"], 'users/transferfund/{id}', ['as' => 'admin.users.transferfund', 'uses' => 'Admin\AdminUserController@transferfund', function ($id = 0) {
        }]);
		
		
		 
		
		//showing user Detail
        Route::match(["get", "post"], 'users/detail/{id}', ['as' => 'admin.usersdetail', 'uses' => 'Admin\AdminUserController@showDetails', function ($id = 0, $status = 0) {
        }]);
		
		
		
		//showing user Detail
        Route::match(["get", "post"], 'users/invoice/{id}', ['as' => 'admin.usersinvoice', 'uses' => 'Admin\AdminUserController@showInvoiceDetails', function ($id = 0, $status = 0) {
        }]);
		
		// KYC listing
		Route::get('kyclist', ['as' => 'admin.testing', 'uses' => 'Admin\AdminUserController@adminkyc']);
		
		// Transaction listing
        Route::get('transaction', ['as' => 'admin.transaction', 'uses' => 'Admin\AdminTransactionController@index']);
		
		// Coin Transaction listing
        Route::get('cointransactions', ['as' => 'admin.cointransactions', 'uses' => 'Admin\AdminTransactionController@cointransactions']);
		
		// Payments listing
        Route::get('allpayments', ['as' => 'admin.allpayments', 'uses' => 'Admin\AdminTransactionController@allpayments']);
		
		//ELT listing for token
        Route::get('tokenusers', ['as' => 'admin.tokenusers', 'uses' => 'Admin\AdminUserController@tokenusers']);
    
		// Block chain transaction list
        Route::get('bctransactions', ['as' => 'admin.bctransactions', 'uses' => 'Admin\AdminUserController@bctransactions']);
		
		// Loan Priority
        Route::get('loanpriority', ['as' => 'admin.loanpriority', 'uses' => 'Admin\AdminUserController@loanpriority']);

		// Card Priority
        Route::get('cardpriority', ['as' => 'admin.cardpriority', 'uses' => 'Admin\AdminUserController@cardpriority']);
		
		// Top referrals leader board
        Route::get('leaderboard', ['as' => 'admin.leaderboard', 'uses' => 'Admin\AdminUserController@leaderboard']);
		
		// Leader board top sales in downline
        Route::get('salesleaderboard', ['as' => 'admin.salesleaderboard', 'uses' => 'Admin\AdminUserController@salesleaderboard']);
		
		// Configuration setting
        Route::match(["get", "post"], '/configuration', ['as' => 'admin.configuration', 'uses' => 'Admin\AdminConfigurationController@index']);
    
		// Config setting
        Route::match(["get", "post"], '/configsetting', ['as' => 'admin.configsetting', 'uses' => 'Admin\AdminConfigurationController@configsetting']);
		
		// Proforma Request
        Route::match(["get", "post"], '/proforma', ['as' => 'admin.proforma', 'uses' => 'Admin\AdminUserController@proforma']);
		
		// Marked User
        Route::match(["get", "post"], '/marked_users', ['as' => 'admin.marked_users', 'uses' => 'Admin\AdminUserController@marked_users']); 
		
		// Loan setting
        Route::match(["get", "post"], '/loansetting', ['as' => 'admin.loansetting', 'uses' => 'Admin\AdminConfigurationController@loansetting']);
		
		// Translation
        Route::match(["get", "post"], '/translation', ['as' => 'admin.translation', 'uses' => 'Admin\AdminTranslationController@translation']);
		
		// Translation New
        Route::match(["get", "post"], '/translationnew', ['as' => 'admin.translationnew', 'uses' => 'Admin\AdminTranslationController@translationnew']);
	
		// Withdrawal setting
        Route::match(["get", "post"], '/withdrawsetting', ['as' => 'admin.withdrawsetting', 'uses' => 'Admin\AdminUserController@withdrawsetting']);
		
		// Admin Phases configuration
        Route::match(["get", "post"], 'configuration/phases', ['as' => 'admin.phases', 'uses' => 'Admin\AdminConfigurationController@showUpdatePhases']);

		// Admin Accounting
		Route::get('accounting', ['as' => 'admin.accounting', 'uses' => 'Admin\AdminTransactionController@accounting']);
		
		// Admin Finance Setting
		Route::get('finance', ['as' => 'admin.finance', 'uses' => 'Admin\AdminUserController@finance']);
		
		// Admin referrals
		Route::get('adminreferrals', ['as' => 'admin.adminreferrals', 'uses' => 'Admin\AdminUserController@adminreferrals']);
		
		// Admin demographics
		Route::get('demographics', ['as' => 'admin.demographics', 'uses' => 'Admin\AdminUserController@demographics']);
		
		// Admin Sales Revenue
		Route::get('salesrevenue', ['as' => 'admin.salesrevenue', 'uses' => 'Admin\AdminUserController@salesrevenue']);
		
		// Admin Stats Users
		Route::get('userstats', ['as' => 'admin.userstats', 'uses' => 'Admin\AdminUserController@userstats']);
		
		// Admin Whitelist users
		Route::get('whitelistusers', ['as' => 'admin.whitelistusers', 'uses' => 'Admin\AdminUserController@whitelistusers']);
		
		// Admin Withdrawal request
        Route::get('withdrawal', ['as' => 'admin.withdrawal', 'uses' => 'Admin\AdminUserController@withdrawal']);
    
		// All Admin roles
        Route::match(["get", "post"],'adminroles', ['as' => 'admin.adminroles', 'uses' => 'Admin\AdminUserController@adminroles']);
		
		// Assign Admin roles
        Route::get('assignroles', ['as' => 'admin.assignroles', 'uses' => 'Admin\AdminUserController@assignroles']);
		
		// Admin access
        Route::get('pageaccess', ['as' => 'admin.pageaccess', 'uses' => 'Admin\AdminUserController@pageaccess']);
		
		//ajax blockchain transaction
        Route::match(["get", "post"], 'ajaxbctransactions', ['as' => 'admin.ajaxbctransactions', 'uses' => 'Admin\AdminAjaxController@ajaxbctransactions']);
		
        // export users
        Route::match(["get", "post"], 'exportusers', ['as' => 'admin.exportusers', 'uses' => 'Admin\AdminUserController@exportusers']);
        
        // export card priority
        Route::match(["get", "post"], 'exportcardpriority', ['as' => 'admin.exportcardpriority', 'uses' => 'Admin\AdminUserController@exportcardpriority']);

		// export transactions
        Route::match(["get", "post"], 'exporttransactions', ['as' => 'admin.exporttransactions', 'uses' => 'Admin\AdminUserController@exporttransactions']);
		
		// export bc transaction
        Route::match(["get", "post"], 'exportbctransactions', ['as' => 'admin.exportbctransactions', 'uses' => 'Admin\AdminUserController@exportbctransactions']);
    
		//send token to users 
        Route::match(["post"], 'sendtokentousers', ['as' => 'admin.sendtokentousers', 'uses' => 'Admin\AdminUserController@sendtokentousers']);
    
        //showing user listing via ajax call
        Route::match(["get", "post"], 'ajaxwithdrawal', ['as' => 'admin.ajaxwithdrawal', 'uses' => 'Admin\AdminAjaxController@ajaxwithdrawal']);
    
		// ajax transaction
        Route::match(["get", "post"], 'ajaxtransaction', ['as' => 'admin.ajaxtransaction', 'uses' => 'Admin\AdminAjaxController@ajaxtransaction']);
		
		// ajax downline level transaction
        Route::match(["get", "post"], 'ajaxdownlinetransaction', ['as' => 'admin.ajaxdownlinetransaction', 'uses' => 'Admin\AdminAjaxController@ajaxdownlinetransaction']);
		
		// ajax admin comments
        Route::match(["get", "post"], 'ajaxadmincomments', ['as' => 'admin.ajaxadmincomments', 'uses' => 'Admin\AdminAjaxController@ajaxadmincomments']);
		
		// ajax coin transaction
        Route::match(["get", "post"], 'ajaxcointransaction', ['as' => 'admin.ajaxcointransaction', 'uses' => 'Admin\AdminAjaxController@ajaxcointransaction']);
		
		// ajaxController ajaxpayments - action
        Route::match(["get", "post"], 'ajaxpayments', ['as' => 'admin.ajax.payments', 'uses' => 'Admin\AdminAjaxController@ajaxpayments']);
    
		// ajaxController child transaction - action
        Route::match(["get", "post"], 'child_transactions', ['as' => 'admin.ajax.child_transactions', 'uses' => 'Admin\AdminAjaxController@ajaxchild_transactions']);
		
		// ajaxController child transaction - action
        Route::match(["get", "post"], 'child_payments', ['as' => 'admin.ajax.child_payments', 'uses' => 'Admin\AdminAjaxController@ajaxchild_payments']);
		
        // showing Pending KYC listing	- testing routes
        Route::get('allkyc', ['as' => 'admin.allkyc', 'uses' => 'Admin\AdminUserController@allkyc']);
    
        
        //update user kyc
        Route::match(["get", "post"], 'users/update_kyc/{id}/{status}', ['as' => 'admin.update_kyc', 'uses' => 'Admin\AdminUserController@update_kyc', function ($id = 0) {
        }]);
    
        //update user wallet
        Route::match(["post"], 'users/update_wallet_balance', ['as' => 'admin.update_wallet_balance', 'uses' => 'Admin\AdminUserController@update_wallet_balance', function ($id = 0) {
        }]);
		
		//update user wallet
        Route::match(["post"], 'users/update_elt_balance', ['as' => 'admin.update_elt_balance', 'uses' => 'Admin\AdminAjaxController@update_elt_balance', function ($id = 0) {
        }]);
		
		 //update user wallet
        Route::match(["post"], 'users/cancel_coin_transaction', ['as' => 'admin.cancel_coin_transaction', 'uses' => 'Admin\AdminAjaxController@cancel_coin_transaction', function ($id = 0) {
        }]);
		
        //update user bank info
        Route::match(["post"], 'users/update_bank_info', ['as' => 'admin.update_bank_info', 'uses' => 'Admin\AdminUserController@update_bank_info', function ($id = 0) {
        }]);
    
        //update sponsor
        Route::match(["post"], 'users/update_sponsor', ['as' => 'admin.update_sponsor', 'uses' => 'Admin\AdminAjaxController@update_sponsor', function ($id = 0) {
        }]);
    
        Route::match(["post"], 'users/update_amount', ['as' => 'admin.update_amount', 'uses' => 'Admin\AdminAjaxController@update_amount', function ($id = 0) {
        }]);
    
        //update kyc ajax
        Route::match(["post"], 'users/update_kyc_ajax', ['as' => 'admin.update_kyc_ajax', 'uses' => 'Admin\AdminAjaxController@update_kyc_ajax', function ($id = 0) {
        }]);
		
		//update role access
        Route::match(["post"], 'users/update_role_access', ['as' => 'admin.update_role_access', 'uses' => 'Admin\AdminUserController@update_role_access', function ($id = 0) {
        }]);
		
		//update admin role
        Route::match(["post"], 'users/update_admin_role', ['as' => 'admin.update_admin_role', 'uses' => 'Admin\AdminUserController@update_admin_role', function ($id = 0) {
        }]);
		
        //filter User stats
        Route::match(["get","post"], 'users/filterUserStats', ['as' => 'admin.filterUserStats', 'uses' => 'Admin\AdminUserController@filterUserStats', function ($id = 0) {
        }]);
		
		 //check username
        Route::match(["post"], 'users/check_username', ['as' => 'admin.check_username', 'uses' => 'Admin\AdminAjaxController@check_username', function ($id = 0) {
        }]);
    
        //change password
        Route::match(["get", "post"], 'users/change_password', ['as' => 'admin.change_password', 'uses' => 'Admin\AdminUserController@change_password', function ($id = 0) {
        }]);
    
        //update KYC stats
        Route::match(["post"], 'users/update_kyc_stats', ['as' => 'admin.update_kyc_stats', 'uses' => 'Admin\AdminUserController@update_kyc_stats', function ($id = 0) {
        }]);
    
        //
        //Route::prefix(Config::get('constants.admin_url'))->post('logout', ['as' => 'admin.logout', 'uses' => 'Admin\AdminLoginController@logout']);
		
		Route::post('logout', ['as' => 'admin.logout', 'uses' => 'Admin\AdminLoginController@logout']);
        
		//admin re-send activation email
        Route::get('/resend-activation-email/{id}', ['as' => 'admin.resend-activation-email', 'uses' => 'Admin\AdminUserController@resend']);
		
		//admin re-structure parent child table
        Route::get('/re-structure/{id}', ['as' => 'admin.re-structure', 'uses' => 'Admin\AdminUserController@restructure']);
    
        //admin re-send whitelist welcome email
        Route::get('/send-whitelist-email/{id}', ['as' => 'admin.send-whitelist-email', 'uses' => 'Admin\AdminUserController@sendWhitelistEmail']);
		
		//showing all logs
        Route::get('logs', ['as' => 'admin.logs', 'uses' => 'Admin\AdminUserController@logs']);
		
		 Route::match(["get", "post"], 'ajaxlogs', ['as' => 'admin.ajaxlogs', 'uses' => 'Admin\AdminUserController@ajaxlogs']);
		
		//showing user listing via ajax call
        Route::match(["get", "post"], 'ajaxusers', ['as' => 'admin.ajaxusers', 'uses' => 'Admin\AdminAjaxController@ajaxusers']);
    
        //showing user listing via ajax call
        Route::match(["get", "post"], 'ajaxtokenusers', ['as' => 'admin.ajaxtokenusers', 'uses' => 'Admin\AdminUserController@ajaxtokenusers']);
		
		//get block chain balance 
        Route::match(["get", "post"], 'ajax_bc_balance', ['as' => 'admin.ajax_bc_balance', 'uses' => 'Admin\AdminUserController@ajax_bc_balance']);
		
		//Ajax call for loan listing
        Route::match(["get", "post"], 'ajaxloanpriority', ['as' => 'admin.ajaxloanpriority', 'uses' => 'Admin\AdminAjaxController@ajaxloanpriority']);
		
		//Ajax call for card listing
        Route::match(["get", "post"], 'ajaxcardpriority', ['as' => 'admin.ajaxcardpriority', 'uses' => 'Admin\AdminAjaxController@ajaxcardpriority']);
		
        Route::match(["get", "post"], 'ajaxkyc', ['as' => 'admin.ajaxkyc', 'uses' => 'Admin\AdminUserController@ajaxkyc']);
    
		
        //showing whitelist user listing via ajax call
        Route::match(["get", "post"], 'ajax_whitelistusers', ['as' => 'admin.ajax_whitelistusers', 'uses' => 'Admin\AdminAjaxController@ajax_whitelistusers']);
		
		//get block chain balance 
        Route::match(["get", "post"], 'ajax_logs_details', ['as' => 'admin.ajax_logs_details', 'uses' => 'Admin\AdminUserController@ajax_logs_details']);
		
		//admin re-send activation email
        Route::get('/view-log-detail/{id}', ['as' => 'admin.view-log-detail', 'uses' => 'Admin\AdminUserController@view_log_detail']);
		
		//apply for invoice
        Route::match(["get", "post"], 'apply_for_invoice', ['as' => 'admin.apply_for_invoice', 'uses' => 'Admin\AdminUserController@apply_for_invoice']);
		
		//admin update user config
        Route::match(["get", "post"], 'updateuserconfig', ['as' => 'admin.updateuserconfig', 'uses' => 'Admin\AdminAjaxController@updateuserconfig']);
		
		//admin update user config
        Route::match(["get", "post"], 'removeuserconfig', ['as' => 'admin.removeuserconfig', 'uses' => 'Admin\AdminAjaxController@removeuserconfig']);
		
		//admin update user config
        Route::match(["get", "post"], 'updateuserfakesales', ['as' => 'admin.updateuserfakesales', 'uses' => 'Admin\AdminAjaxController@updateuserfakesales']);
		
		//admin add general comment for user
        Route::match(["get", "post"], 'addusercomment', ['as' => 'admin.addusercomment', 'uses' => 'Admin\AdminAjaxController@addusercomment']);
		
		//showing demographics via ajax call
        Route::match(["get", "post"], 'ajaxdemographics', ['as' => 'admin.ajaxdemographics', 'uses' => 'Admin\AdminAjaxController@ajaxdemographics']);
		
		//showing adminreferrals listing via ajax call
        Route::match(["get", "post"], 'ajaxadminreferrals', ['as' => 'admin.ajaxadminreferrals', 'uses' => 'Admin\AdminAjaxController@ajaxadminreferrals']);
		
		//showing finance page listing via ajax call
        Route::match(["get", "post"], 'ajaxfinance', ['as' => 'admin.ajaxfinance', 'uses' => 'Admin\AdminAjaxController@ajaxfinance']);
		
		//ajax finance bonus and unqualified
        Route::match(["get", "post"], 'ajaxbonuslist', ['as' => 'admin.ajaxbonuslist', 'uses' => 'Admin\AdminAjaxController@ajaxbonuslist']);
		
		//showing salesrevenue page listing via ajax call
        Route::match(["get", "post"], 'ajaxsalesrevenue', ['as' => 'admin.ajaxsalesrevenue', 'uses' => 'Admin\AdminAjaxController@ajaxsalesrevenue']);
		
		// Admin Ajax Accounting
		Route::match(["get", "post"], 'ajaxaccounting', ['as' => 'admin.ajaxaccounting', 'uses' => 'Admin\AdminAjaxController@ajaxaccounting']);
		
		// Admin Ajax Stored data
		Route::match(["get", "post"], 'ajaxstoreddata', ['as' => 'admin.ajaxstoreddata', 'uses' => 'Admin\AdminAjaxController@ajaxstoreddata']);
		
		// Admin Ajax table data for currency
		Route::match(["get", "post"], 'ajaxcurrencyinfo', ['as' => 'admin.ajaxcurrencyinfo', 'uses' => 'Admin\AdminAjaxController@ajaxcurrencyinfo']);
		
		// Admin Ajax table data for withdraw statistics
		Route::match(["get", "post"], 'ajaxwithdrawstats', ['as' => 'admin.ajaxwithdrawstats', 'uses' => 'Admin\AdminAjaxController@ajaxwithdrawstats']);
		
		// Admin Ajax approve withdraw
		Route::match(["get", "post"], 'ajaxapprovewithdraw', ['as' => 'admin.ajaxapprovewithdraw', 'uses' => 'Admin\AdminAjaxController@ajaxapprovewithdraw']);
		
		// Admin Ajax approve withdraw
		Route::match(["get", "post"], 'ajaxrejectwithdraw', ['as' => 'admin.ajaxrejectwithdraw', 'uses' => 'Admin\AdminAjaxController@ajaxrejectwithdraw']);
		
		// Admin Ajax Proforma Invoice
		Route::match(["get", "post"], 'ajaxproforma', ['as' => 'admin.ajaxproforma', 'uses' => 'Admin\AdminAjaxController@ajaxproforma']);
		
		//showing marked users setting listing
        Route::match(["get", "post"], 'ajaxmarkedusers', ['as' => 'admin.ajaxmarkedusers', 'uses' => 'Admin\AdminAjaxController@ajaxmarkedusers']);
		
		//admin logs
        Route::match(["get", "post"], 'adminlogs', ['as' => 'admin.adminlogs', 'uses' => 'Admin\AdminUserController@adminlogs']);
		
		//admin ajax logs request
        Route::match(["get", "post"], 'ajaxadminlogs', ['as' => 'admin.ajaxadminlogs', 'uses' => 'Admin\AdminAjaxController@ajaxadminlogs']);
		
    });

    Route::match(["get", "post"], '/signup', ['as' => 'signup', 'uses' => 'Auth\RegisterController@signup']);
    Route::match(["get", "post"], '/newsignup', ['as' => 'newsignup', 'uses' => 'Auth\RegisterController@newsignup']);
	Route::match(["post", "get"], '/register', ['as' => 'airdrop_signup', 'uses' => 'Auth\RegisterController@airdrop_signup']);
	Route::match(["get", "post"], '/refreshcapcha', ['as' => 'refresh_capcha', 'uses' => 'Auth\RegisterController@refresh_capcha']);
	
	
	Route::match(["post", "get"], '/50elt', ['as' => '50elt', 'uses' => 'Auth\RegisterController@register_demo']);
	//Route::match(["post", "get"], '/register', ['as' => 'airdrop_signup', 'uses' => 'Auth\RegisterController@airdrop_signup']);
    Route::match(["get", "post"], '/airdrop', ['as' => 'airdrop', 'uses' => 'Auth\RegisterController@airdrop']);
    Route::get('activateuser/{token}', ['as' => 'user.activateuser', 'uses' => 'Auth\LoginController@activateuser']);
	Route::post('whitelist', ['as' => 'user.whitelist', 'uses' => 'Auth\LoginController@whitelist']);
    Route::match(["get", "post"], '/whitelistsignup', ['as' => 'whitelistsignup', 'uses' => 'Auth\LoginController@whitelistsignup']);
    Route::get('verifywhitelist/{token}', ['as' => 'user.verifywhitelist', 'uses' => 'Auth\LoginController@verifywhitelist']);
    Route::get('whitelistpackage/{token}/{amount}', ['as' => 'user.whitelistpackage', 'uses' => 'Auth\LoginController@whitelistpackage']);
    Route::match(["get", "post"],'/check_email_exist', ['as' => 'check_email_exist', 'uses' => 'Auth\RegisterController@check_email_exist']);
	Route::get('check_telegram_id_exist', ['as' => 'check_telegram_id_exist', 'uses' => 'Auth\RegisterController@check_telegram_id_exist']);
	
    // get eth raised
    Route::get('/getraisedeth/{token}', ['as' => 'cron.getraisedeth', 'uses' => 'CronController@getRaisedETH']);
	// card and loan detailed info
    Route::get('/cronloancardinfo/{token}', ['as' => 'cron.getLoanCardApplicationInfo', 'uses' => 'CronController@getLoanCardApplicationInfo']);
    Route::get('/updatetermcurrency/{token}', ['as' => 'cron.updatetermcurrency', 'uses' => 'CronController@updatetermcurrency']);
    // automated email to whitelist members
    Route::get('/cronWhitelistAutomation/{token}', ['as' => 'user.whitelistautomatedemails', 'uses' => 'CronController@whitelistautomatedemails']);
	Route::match(["get", "post"],'/update_file', ['as' => 'file.update_file', 'uses' => 'WhiteListController@update_file']);
	
	Route::match(["get", "post"],'/pdf', ['as' => 'pdf', 'uses' => 'WhiteListController@pdf']);
	
	
	/* expire passed change request */
	Route::get('expirepasttoken/{token}', ['as' => 'expirepasttoken', 'uses' => 'CronController@expirepasttoken']);
    
    /* update ELT wallet address */
    Route::get('updateWalletELTAddress/{token}', ['as' => 'updateWalletELTAddress', 'uses' => 'CronController@updateWalletELTAddress']);
    
	/* reversebonuselt */
	Route::get('reversebonuselt/{token}', ['as' => 'reversebonuselt', 'uses' => 'CronController@reversebonuselt']);
	
	/* Live exchange rates */
	Route::get('cronExchageRate', ['as' => 'cronExchageRate', 'uses' => 'CronController@cronExchageRate']);
	Route::get('/invoice/view/{id}', ['as' => 'invoice.view', 'uses' => 'CronController@invoice_detail']);
	Route::get('/proforma/view/{id}', ['as' => 'proforma.view', 'uses' => 'CronController@proforma_detail']);
	
	// set parent upline of users
    Route::get('/setupline/{token}', ['as' => 'cron.setupline', 'uses' => 'CronController@setupline']);
    Route::get('/testaction/', ['as' => 'cron.testaction', 'uses' => 'CronController@testaction']);
    Route::impersonate();
    Route::get('/runtestcase/{case_name}', 'CommandController@runTestCase');
	Route::get('/home/logout', ['as' => 'home.logout', 'uses' => 'Auth\LoginController@logout']);
	Route::get('/logoutlendo', ['as' => 'logoutlendo', 'uses' => 'Auth\LoginController@logoutlendo']);
		
