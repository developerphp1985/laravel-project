<?php

namespace App\Models;
use App\Helpers\CommonHelper;
use App\Helpers\LoggerHelper;
use DB;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class Transactions extends Model
{
    //
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	 
    protected $fillable = ['transaction_id','ref_transaction_id','user_id','ledger','description',
    'value','status','type','type_name','unpaid_bonus','show_to_user','exclude_in_sales','exclude_in_payments','term_currency','term_amount','status_message','value_in_euro','phase_id','address','ipaddress'];

    public function user() { 
        return $this->belongsTo('App\Models\User', 'user_id');
    }
	
	public static function update_whitelistusers($where,$data)	
	{		
		return DB::table('whitelist_users')->where($where)->update($data);	
	}

    public static function get_datatables_join($column_order, $postarray, $send_count = 0, $export_csv=0)
    {
        $query = Transactions::query();
        $query->leftJoin('users', 'transactions.user_id', '=', 'users.id');
        $query->select('transactions.*', 'users.user_name', 'users.email');
		
		/* condition for payment transaction only */
		if(isset($postarray['paymentOnly']) && $postarray['paymentOnly'] == 1){
			$query->where('transactions.exclude_in_payments', 0);
			$query->whereIn('transactions.type', [1]);			
			$query->whereNotIn('transactions.type_name', ['bonus']);
		}
		
		if(isset($postarray['status']) && $postarray['status'] == 1){
			$query->where('transactions.status', 1);
		}
		
		/* downline child transaction */
		if(isset($postarray['userid']) && isset($postarray['level']) && count($postarray['inUserIds']) > 0){
			$query->whereIn('transactions.user_id', $postarray['inUserIds']);
		}
		
		/*$query->whereIn('transactions.type', [1]);*/
		
		/* condition for campaign offer transaction only */
		if(isset($postarray['CampaignOnly']) && $postarray['CampaignOnly'] == 1){
			$query->whereIn('transactions.type', [2, 3, 4]);
		}
		else
		{
			$query->whereNotIn('transactions.type', [2, 3, 4]);
		}
		
		if($export_csv == 1){
			$query->where('users.make_user_invisible', '0');
		}
		else if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
		}
		
		$query->whereNull('transactions.ref_transaction_id');
		
		if(isset($postarray['coinsOnly']) && $postarray['coinsOnly'] == 1)
		{		
			$query->whereIn('transactions.term_currency', ['NEXO','SALT','ORME']);
		}
		
        if(isset($postarray['search_text']) && !empty($postarray['search_text'])){
            $search_text = $postarray['search_text'];
            $search_text1 = explode(' ', $search_text);
            $search_text3 = $search_text1[0];
            if (isset($search_text1[1])){
                $search_text2 = $search_text1[1];
            } 
			else{
                $search_text2 = $search_text1[0];
            }
            $query->where(function ($query) use ($search_text3, $search_text, $search_text2) {
                $query->where('transactions.transaction_id', 'LIKE', "%$search_text%")
                    ->orWhere('transactions.ledger', 'LIKE', "%$search_text%")
                    ->orWhere('users.user_name', 'LIKE', "%$search_text3%")
                    ->orWhere('users.email', 'LIKE', "%$search_text%")
                    ->orWhere('users.last_name', 'LIKE', "%$search_text2%");
            });
        }
		
        if(!empty($postarray['start_date']) && !empty($postarray['end_date'])) {
            $start_date_array = explode("/", $postarray['start_date']);
            $end_date_array = explode("/", $postarray['end_date']);
            $start_date = $start_date_array[2] . '-' . $start_date_array[0] . '-' . $start_date_array[1];
            $end_date = $end_date_array[2] . '-' . $end_date_array[0] . '-' . $end_date_array[1];
            $start_date = $start_date . ' 00:00:00';
            $end_date = $end_date . ' 23:59:59';
            $query->where('transactions.created_at', '>=', $start_date);
            $query->where('transactions.created_at', '<=', $end_date);
        }
        
		if(isset($postarray['currency_filter']) && !empty($postarray['currency_filter'])) {
            $currency_filter = $postarray['currency_filter'];
            $query->where(function ($query) use ($currency_filter) {
                $query->where('transactions.ledger', '=', $currency_filter)
                    ->orWhere('transactions.term_currency', '=', $currency_filter);
            });
        }
		
		if(isset($postarray['type_name']) && !empty($postarray['type_name'])) {						
            $type_name = $postarray['type_name'];
            $query->where('transactions.type_name', '=', $type_name);
        }
		
        if(isset($postarray['status_filter']) && in_array($postarray['status_filter'], array(0, 1, 2, 3))){
            $status_filter = $postarray['status_filter'];
            if ($postarray['status_filter'] == 3)
                $query->where('transactions.status', '=', 0);
            else
                $query->where('transactions.status', '=', $status_filter);
        }

        if($send_count == 0){
            if (isset($postarray['start'])) {
                $query->offset($postarray['start']);
            }
            if (isset($postarray['length'])) {
                $query->limit($postarray['length']);
            }
        }

        if (isset($postarray['order'])) {
            $direction = 'desc';
            if ($postarray['order']['0']['dir'] == 'asc') {
                $direction = 'desc';
            } else if ($postarray['order']['0']['dir'] == 'desc') {
                $direction = 'asc';
            }
            $query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
        } 
		else{
            $query->orderBy('transactions.created_at', 'desc');
        }

		//echo $query->toSql();die;
		
        if ($send_count == 1) {
            return $query->count();
        }
		else{
            return $query->get();
        }
    }
	
	
	
	public static function get_datatables_join_cointrans($column_order, $postarray, $send_count = 0)
    {
        $query = Transactions::query();
        $query->leftJoin('users', 'transactions.user_id', '=', 'users.id');
        $query->select('transactions.*', 'users.user_name', 'users.email');
		if(isset($postarray['status']) && $postarray['status'] == 1){
			$query->where('transactions.status', 1);
		}		
		$query->whereIn('transactions.term_currency', ['NEXO','SALT','ORME']);
		
		if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
		}
		
        if(isset($postarray['search_text']) && !empty($postarray['search_text'])){
            $search_text = $postarray['search_text'];
            $search_text1 = explode(' ', $search_text);
            $search_text3 = $search_text1[0];
            if (isset($search_text1[1])){
                $search_text2 = $search_text1[1];
            }
			else{
                $search_text2 = $search_text1[0];
            }
            $query->where(function ($query) use ($search_text3, $search_text, $search_text2) {
                $query->where('transactions.transaction_id', 'LIKE', "%$search_text%")
                ->orWhere('transactions.ledger', 'LIKE', "%$search_text%")
                ->orWhere('users.user_name', 'LIKE', "%$search_text3%")
                ->orWhere('users.email', 'LIKE', "%$search_text%")
                ->orWhere('users.last_name', 'LIKE', "%$search_text2%");
            });
        }
		
        if(!empty($postarray['start_date']) && !empty($postarray['end_date'])) {
            $start_date_array = explode("/", $postarray['start_date']);
            $end_date_array = explode("/", $postarray['end_date']);
            $start_date = $start_date_array[2] . '-' . $start_date_array[0] . '-' . $start_date_array[1];
            $end_date = $end_date_array[2] . '-' . $end_date_array[0] . '-' . $end_date_array[1];
            $start_date = $start_date . ' 00:00:00';
            $end_date = $end_date . ' 23:59:59';
            $query->where('transactions.created_at', '>=', $start_date);
            $query->where('transactions.created_at', '<=', $end_date);
        }
        
		if(isset($postarray['currency_filter']) && !empty($postarray['currency_filter'])) {
            $currency_filter = $postarray['currency_filter'];
            $query->where(function ($query) use ($currency_filter) {
                $query->where('transactions.ledger', '=', $currency_filter)
                ->orWhere('transactions.term_currency', '=', $currency_filter);
            });
        }
		
		if(isset($postarray['type_name']) && !empty($postarray['type_name'])) {						
            $type_name = $postarray['type_name'];
            $query->where('transactions.type_name', '=', $type_name);
        }
		
        if(isset($postarray['status_filter']) && in_array($postarray['status_filter'], array(0, 1, 2, 3))){
            $status_filter = $postarray['status_filter'];
            if ($postarray['status_filter'] == 3)
                $query->where('transactions.status', '=', 0);
            else
                $query->where('transactions.status', '=', $status_filter);
        }

        if($send_count == 0){
            if (isset($postarray['start'])) {
                $query->offset($postarray['start']);
            }
            if (isset($postarray['length'])) {
                $query->limit($postarray['length']);
            }
        }

        if (isset($postarray['order'])) {
            $direction = 'desc';
            if ($postarray['order']['0']['dir'] == 'asc') {
                $direction = 'desc';
            } else if ($postarray['order']['0']['dir'] == 'desc') {
                $direction = 'asc';
            }
            $query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
        } 
		else{
            $query->orderBy('transactions.created_at', 'desc');
        }
		
        if ($send_count == 1) {
            return $query->count();
        }
		else{
            return $query->get();
        }
    }
	
	
	public static function get_datatables_bonus($column_order, $postarray, $send_count = 0)
    {
        $query = Transactions::query();		
        $query->leftJoin('users', 'transactions.user_id', '=', 'users.id');
		$query->leftJoin('countries', 'countries.code', '=', 'users.country_code');
		if($postarray['selectedCurrency'] = 'bonus'){
			$query->selectRaw('users.*,countries.name AS country_name,SUM(transactions.value * transactions.value_in_euro) as bonus_euro_worth');
		}
		else{
			$query->selectRaw('users.*,countries.name AS country_name,SUM(transactions.unpaid_bonus * transactions.value_in_euro) as bonus_euro_worth');
		}
		$query->where('transactions.status', '=', 1);
		$query->where('transactions.type_name', '=', 'bonus');
		$query->where('transactions.ledger', '!=', 'ELT');			
		$query->where('users.role', '=', 2);	
		$query->groupBy('transactions.user_id');		
        if($send_count == 0){
            if (isset($postarray['start'])) {
                $query->offset($postarray['start']);
            }
            if (isset($postarray['length'])) {
                $query->limit($postarray['length']);
            }
        }
        if($send_count == 0 && isset($postarray['order'])){
            $direction = 'desc';
            if ($postarray['order']['0']['dir'] == 'asc') {
                $direction = 'desc';
            } else if ($postarray['order']['0']['dir'] == 'desc') {
                $direction = 'asc';
            }
            $query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
        } 
		else{
            $query->orderBy('transactions.created_at', 'desc');
        }
				
        if ($send_count == 1) {
            return $query->count();
        }
		else{
            return $query->get();
        }
    }
	
    /**
     * The method to add provided value to existing balance.
     *
     * @var key - column name to be updated 
     * @var value - value to be subtracted
     */
    public static function createTransaction($userId, $balanceType, $amount, $description, $status, $transaction_id, $phaseId = 0, $address = NULL, $type = 1, $ref_transaction_id = NULL, $type_name='ico-wallet', $unpaid_bonus=0, $show_to_user=1)
    {
		
		$exclude_in_sales = 0;
		$exclude_in_payments = 0;
			
        if( (isset($amount) && ($amount!=0)) && ($balanceType != '') ) {

			$phaseId = self::get_current_phase_id();
			$userConfigRow = self::get_user_config_setting($userId);
			if($userConfigRow)
			{
				$exclude_in_sales = $userConfigRow->exclude_saleslist;
				$exclude_in_payments = $userConfigRow->exclude_payment_transaction;
			}
			
			$value_in_euro = 1;
			if($balanceType != 'ELT')
			{
				$value_in_euro = self::get_currency_to_euro_rate($balanceType);
			}
			
            $transactionCreate = self::create([
                    'user_id'         => $userId,
                    'ledger'          => $balanceType,
                    'value'           => $amount,
                    'description'     => $description,
                    'status'          => $status,//1
                    'transaction_id'  => $transaction_id,
					'ref_transaction_id'  => $ref_transaction_id, 
                    'phase_id'        => $phaseId,
                    'address'         => $address,
                    'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
					'type'            => $type,
					'type_name'		  => $type_name,
					'unpaid_bonus'	  => $unpaid_bonus,
					'show_to_user'	  => $show_to_user,
					'exclude_in_sales'  => $exclude_in_sales,
					'exclude_in_payments'  => $exclude_in_payments,
					'value_in_euro'	  => $value_in_euro
				]);
        }
        $record = [
            'message'   => 'UserId '.$userId.' Transaction Tabel update',
            'level'     => 'INFO',
             'userId'     => $userId,
            'context'   => 'TransactionTableUpdate',
            'extra'     => [
                'coinpayment_response' => json_encode(
                    array(
                        'user_id'         => $userId,
                        'ledger'          => $balanceType,
                        'value'           => $amount,
                        'description'     => $description,
                        'status'          => $status,//1
                        'transaction_id'  => $transaction_id, 
                        'phase_id'        => $phaseId,
                        'address'         => $address,
                        'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
						'type'            => $type,
						'value_in_euro'	  => $value_in_euro
                    )
                )
            ]
        ];
        LoggerHelper::writeDB($record);
    }
	
	public static function createEmptyTransaction($userId, $currency, $description, $transaction_id, $ref_transaction_id = NULL, $type_name='ico-wallet', $unpaid_bonus=0)
    {
		$phaseId = self::get_current_phase_id();
		$value_in_euro = 1;
		$value_in_euro = self::get_currency_to_euro_rate($currency);		
		$exclude_in_sales = 0;
		$exclude_in_payments = 0;		
		$userConfigRow = self::get_user_config_setting($userId);
		if($userConfigRow){
			$exclude_in_sales = $userConfigRow->exclude_saleslist;
			$exclude_in_payments = $userConfigRow->exclude_payment_transaction;
		}
			
		$transactionCreate = self::create([
				'user_id'         => $userId,
				'ledger'          => $currency,
				'value'           => 0,
				'description'     => $description,
				'status'          => 1,
				'transaction_id'  => $transaction_id,
				'ref_transaction_id'  => $ref_transaction_id, 
				'phase_id'        => $phaseId,
				'address'         => NULL,
				'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
				'type'            => 1,
				'type_name'		  => $type_name,
				'unpaid_bonus'	  => $unpaid_bonus,
				'show_to_user'	  => 0,
				'exclude_in_sales'  => $exclude_in_sales,
				'exclude_in_payments'  => $exclude_in_payments,
				'value_in_euro'	  => $value_in_euro
			]);
        $record = [
            'message'   => 'UserId '.$userId.' Transaction Tabel update with no bonus',
            'level'     => 'INFO',
             'userId'     => $userId,
            'context'   => 'TransactionTableUpdate with nobonus',
            'extra'     => [
                'icowallet_response' => json_encode(
                    array(
                        'user_id'         => $userId,
                        'ledger'          => $currency,
                        'value'           => 0,
                        'description'     => $description,
                        'status'          => 1,
                        'transaction_id'  => $transaction_id, 
                        'phase_id'        => $phaseId,
                        'address'         => NULL,
                        'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
						'type'            => 1,
						'type_name'		  => $type_name,
						'value_in_euro'	  => $value_in_euro
                    )
                )
            ]
        ];
        LoggerHelper::writeDB($record);
    }
	
	
	public static function createTransactionRow($userId, $balanceType, $amount, $description, $status, $transaction_id, $phaseId = 0, $address = NULL, $type_name = 'ico-wallet')
    {
		$phaseId = self::get_current_phase_id();
		
		$exclude_in_sales = 0;
		$exclude_in_payments = 0;
			
        if( (isset($amount) && ($amount!=0)) && ($balanceType != '') ) 
		{ 
			$value_in_euro = 1;
			
			if($balanceType == 'ELT' && $term_currency!='ELT' && $term_currency!=NULL)
			{
				$value_in_euro = self::get_currency_to_euro_rate($term_currency);
			}
			elseif($balanceType != 'ELT' && $term_currency == NULL)
			{
				$value_in_euro = self::get_currency_to_euro_rate($balanceType);
			}
			
			$userConfigRow = self::get_user_config_setting($userId);
			if($userConfigRow)
			{
				$exclude_in_sales = $userConfigRow->exclude_saleslist;
				$exclude_in_payments = $userConfigRow->exclude_payment_transaction;
			}
			
            $transactionCreate = self::create([
                    'user_id'         => $userId,
                    'ledger'          => $balanceType,
                    'value'           => $amount,
                    'description'     => $description,
                    'status'          => $status,//1
                    'transaction_id'  => $transaction_id, 
                    'phase_id'        => $phaseId,
                    'address'         => $address,
					'type_name'		  => $type_name,
                    'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
					'value_in_euro'	  => $value_in_euro
            ]);
        }
    }

	/**
     * The method to add provided value to existing balance.
     *
     * @var key - column name to be updated 
     * @var value - value to be subtracted
     */
    public static function createTransactionWithTermCurrency($userId, $balanceType, $amount, $description, $status, $transaction_id, $phaseId = 0, $address = NULL, $term_currency, $term_amount, $type_name = 'ico-wallet', $unpaid_bonus=0, $show_to_user=1)
    {		
		$phaseId = self::get_current_phase_id();
		
		$exclude_in_sales = 0;
		$exclude_in_payments = 0;
			
        if( (isset($amount) && ($amount!=0)) && ($balanceType != '') ) { 
		
			$value_in_euro = 1;
			
			if($balanceType == 'ELT' && $term_currency!='ELT' && $term_currency!=NULL)
			{
				$value_in_euro = self::get_currency_to_euro_rate($term_currency);
			}
			elseif($balanceType != 'ELT' && $term_currency == NULL)
			{
				$value_in_euro = self::get_currency_to_euro_rate($balanceType);
			}
			
			
			$userConfigRow = self::get_user_config_setting($userId);
			if($userConfigRow)
			{
				$exclude_in_sales = $userConfigRow->exclude_saleslist;
				$exclude_in_payments = $userConfigRow->exclude_payment_transaction;
			}
			
            $transactionCreate = self::create([
                    'user_id'         => $userId,
                    'ledger'          => $balanceType,
                    'value'           => $amount,
                    'description'     => $description,
                    'status'          => $status,//1
                    'transaction_id'  => $transaction_id, 
                    'phase_id'        => $phaseId,
                    'address'         => $address,
					'term_currency'	  => $term_currency,
					'term_amount'	  => $term_amount,
					'type_name'		  => $type_name,
                    'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
					'unpaid_bonus'	  => $unpaid_bonus,
					'show_to_user'	  => $show_to_user,
					'exclude_in_sales'  => $exclude_in_sales,
					'exclude_in_payments'  => $exclude_in_payments,
					'value_in_euro'	  => $value_in_euro
            ]);
        }
		
        $record = [
            'message'   => 'UserId '.$userId.' Transaction Tabel update',
            'level'     => 'INFO',
             'userId'     => $userId,
            'context'   => 'TransactionTableUpdate',
            'extra'     => [
                'coinpayment_response' => json_encode(
                    array(
                        'user_id'         => $userId,
                        'ledger'          => $balanceType,
                        'value'           => $amount,
                        'description'     => $description,
                        'status'          => $status,//1
                        'transaction_id'  => $transaction_id, 
                        'phase_id'        => $phaseId,
                        'address'         => $address,
						'term_currency'	  => $term_currency,
						'term_amount'	  => $term_amount,
                        'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
						'value_in_euro'	  => $value_in_euro,
                    )
                )
            ]
        ];
        LoggerHelper::writeDB($record);
    }
	
	public static function createTransactionWithReference($userId, $balanceType, $amount, $description, $status, $transaction_id, $phaseId = 0, $address = NULL, $term_currency, $term_amount, $ref_transaction_id, $type_name='ico-wallet')
    {		
		$phaseId = self::get_current_phase_id();
		
		$exclude_in_sales = 0;
		$exclude_in_payments = 0;
			
        if( (isset($amount) && ($amount!=0)) && ($balanceType != '') ) { 
			
			$value_in_euro = 1;
			
			if($balanceType == 'ELT' && $term_currency!='ELT' && $term_currency!=NULL)
			{
				$value_in_euro = self::get_currency_to_euro_rate($term_currency);
			}
			elseif($balanceType != 'ELT' && $term_currency == NULL)
			{
				$value_in_euro = self::get_currency_to_euro_rate($balanceType);
			}
		
			
			$userConfigRow = self::get_user_config_setting($userId);
			if($userConfigRow)
			{
				$exclude_in_sales = $userConfigRow->exclude_saleslist;
				$exclude_in_payments = $userConfigRow->exclude_payment_transaction;
			}
			
            $transactionCreate = self::create([
                    'user_id'         => $userId,
                    'ledger'          => $balanceType,
                    'value'           => $amount,
                    'description'     => $description,
                    'status'          => $status,//1
                    'transaction_id'  => $transaction_id, 
					'ref_transaction_id'  => $ref_transaction_id, 
                    'phase_id'        => $phaseId,
                    'address'         => $address,
					'term_currency'	  => $term_currency,
					'term_amount'	  => $term_amount,
					'type_name'		  => $type_name,
					'exclude_in_sales'  => $exclude_in_sales,
					'exclude_in_payments'  => $exclude_in_payments,
                    'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
					'value_in_euro'	  => $value_in_euro
            ]);
        }
	
        $record = [
            'message'   => 'UserId '.$userId.' Transaction Tabel update',
            'level'     => 'INFO',
             'userId'     => $userId,
            'context'   => 'TransactionTableUpdate',
            'extra'     => [
                'coinpayment_response' => json_encode(
                    array(
                        'user_id'         => $userId,
                        'ledger'          => $balanceType,
                        'value'           => $amount,
                        'description'     => $description,
                        'status'          => $status,//1
                        'transaction_id'  => $transaction_id, 
                        'phase_id'        => $phaseId,
                        'address'         => $address,
						'term_currency'	  => $term_currency,
						'term_amount'	  => $term_amount,
                        'ipaddress'       => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true)
                    )
                )
            ]
        ];
        LoggerHelper::writeDB($record);
    }
		
	public static function get_child_transaction($transaction_id)
	{
		$query = Transactions::query();
		$query->select('transactions.*','users.email');
		$query->leftJoin('users', 'transactions.user_id', '=', 'users.id');
		$query->where('ref_transaction_id', '=', $transaction_id);
		$query->orderBy('transactions.id', 'asc');
		return $query->get();
	}
	
	public static function count_child_transaction($transaction_id)
	{
		$query = Transactions::query();
		$query->select('transactions.id');
		$query->where('ref_transaction_id', '=', $transaction_id);		
		if($query->count() > 0)
		{
			return $query->count();		
		}
		return 0;
	}
	
	public static function transaction_bonus_percentage($ref_transaction_id)
	{
		$query = Transactions::query();		
		$query->selectRaw('SUM(value) as total_bonus, SUM(unpaid_bonus) as total_unpaid_bonus');		
		$query->where('transactions.ledger', '!=', 'ELT');
		$query->where('transactions.ref_transaction_id', '=', $ref_transaction_id);
		$result = $query->get();
		return array("total_bonus"=>$result[0]->total_bonus,"total_unpaid_bonus"=>$result[0]->total_unpaid_bonus);
	}
	
	public static function total_bonus_percentage_sum()
	{
		$result = DB::table('configurations')->selectRaw('SUM(defined_value) as total_bonus_percent')->whereIn('name', ['Referral-%-Level-1','Referral-%-Level-2','Referral-%-Level-3','Referral-%-Level-4','Referral-%-Level-5'])->where('valid_to', '=', "9999-12-31")->first();
		return $result->total_bonus_percent;
	}
	
	
	public static function payment_child_transaction($transaction_id)
	{
		$transaction_id = '5b6d6408c4634';
		$resposne = DB::table('transactions')->select('transaction_id','unpaid_bonus','value')->where('ref_transaction_id', $transaction_id)->get();
		return $query->get();		
	}
	
	public static function get_user_elt_worth_in_euro($user_id,$type='')
	{
		if(!empty($type) && $type == 'list'){
			$InSql = "transactions.user_id IN (".implode(',',$user_id).")";
		}
		elseif($type=='all'){
			$InSql = "1 ";
		}
		else{
			if($user_id == 114774){
				$InSql = "transactions.user_id = ".$user_id." AND transactions.value NOT LIKE '-%' ";
			}
			else{
				$InSql = "transactions.user_id = ".$user_id;
			}
		}
		
		$Sql = "
		SELECT 
		SUM(transactions.value * phases.token_price) as euro_worth_total,
		SUM(transactions.value) as elt_worth_total
		FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND transactions.exclude_in_sales=0";
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	public static function get_user_elt_worth_in_euro_date($user_id=array(), $start_date, $end_date)
	{
		$InSql = $DateSql='';
		if(count($user_id) > 0){
			$InSql = " AND transactions.user_id IN (".implode(',',$user_id).")";
		}
		if(isset($start_date) && isset($end_date) && $start_date == $end_date){
			$DateSql = " AND DATE(transactions.created_at)='".$start_date."'";
		}
		elseif(isset($start_date) && isset($end_date) &&  $start_date!=$end_date){
			$DateSql = " AND DATE(transactions.created_at) BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		$Sql = "
		SELECT 
		SUM(transactions.value * phases.token_price) as euro_worth_total,
		SUM(transactions.value) as elt_worth_total
		FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND transactions.exclude_in_sales=0 $InSql $DateSql";
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	public static function get_user_elt_worth_in_euro_reset_date($user_id=array(), $start_date, $end_date)
	{
		$accounting_reset_from_date = config("constants.accounting_reset_from_date");
		$InSql = $DateSql='';
		if(count($user_id) > 0){
			$InSql = " AND transactions.user_id IN (".implode(',',$user_id).")";
		}
		if(isset($start_date) && isset($end_date) && $start_date == $end_date){
			$DateSql = " AND DATE(transactions.created_at)='".$start_date."'";
		}
		elseif(isset($start_date) && isset($end_date) &&  $start_date!=$end_date){
			$DateSql = " AND DATE(transactions.created_at) BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		$Sql = "
		SELECT 
		SUM(transactions.value * phases.token_price) as euro_worth_total,
		SUM(transactions.value) as elt_worth_total
		FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE DATE(transactions.created_at)>='".$accounting_reset_from_date."' AND transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND transactions.exclude_in_sales=0 $InSql $DateSql";		
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	public static function get_current_user_level($user_id)
	{
		$euro_worth_stage_0 = config('global_vars.euro_worth_stage_0');
		$euro_worth_stage_1 = config('global_vars.euro_worth_stage_1');
		$euro_worth_stage_2 = config('global_vars.euro_worth_stage_2');
		$euro_worth_stage_3 = config('global_vars.euro_worth_stage_3');
		$euro_worth_stage_4 = config('global_vars.euro_worth_stage_4');
		$euro_worth_stage_5 = config('global_vars.euro_worth_stage_5');		
		$level_detail = Transactions::get_user_elt_worth_in_euro($user_id);
		$euro_worth = $level_detail[0]->euro_worth_total;
		$response['current_level'] = '0';
		$response['euro_worth_for_next_level'] = $euro_worth_stage_0['max'];
		if($euro_worth <= $euro_worth_stage_0['max'])
		{
			$response['euro_worth_for_next_level'] = ($euro_worth_stage_0['max']) - $euro_worth;
		}
		elseif($euro_worth >= $euro_worth_stage_1['min'] && $euro_worth < $euro_worth_stage_1['max'])
		{
           $response['current_level'] = '1';
		   $response['euro_worth_for_next_level'] = ($euro_worth_stage_1['max']) - $euro_worth;
		}
		elseif($euro_worth >= $euro_worth_stage_2['min'] && $euro_worth < $euro_worth_stage_2['max'])
		{
           $response['current_level'] = '2';
		   $response['euro_worth_for_next_level'] = ($euro_worth_stage_2['max']) - $euro_worth;
		}
		elseif($euro_worth >=$euro_worth_stage_3['min'] && $euro_worth < $euro_worth_stage_3['max'])
		{
           $response['current_level'] = '3';
		   $response['euro_worth_for_next_level'] = ($euro_worth_stage_3['max']) - $euro_worth;
		}
	    elseif($euro_worth >=$euro_worth_stage_4['min'] && $euro_worth < $euro_worth_stage_4['max'])
		{
           $response['current_level'] = '4';
		   $response['euro_worth_for_next_level'] = ($euro_worth_stage_4['max']) - $euro_worth;
		}
		elseif($euro_worth >= $euro_worth_stage_5['min'])
		{
			$response['current_level'] = '5';
			$response['euro_worth_for_next_level'] = '0';
		}	   
		return $response;
	}
	
	public static function get_new_bonus_percent_per_euro_worth($userid, $actual_percent=0)
	{
		$returnData = array();
		$euro_worth_stage_0 = config('global_vars.euro_worth_stage_0');
		$euro_worth_stage_1 = config('global_vars.euro_worth_stage_1');
		$euro_worth_stage_2 = config('global_vars.euro_worth_stage_2');
		$euro_worth_stage_3 = config('global_vars.euro_worth_stage_3');
		$euro_worth_stage_4 = config('global_vars.euro_worth_stage_4');
		$euro_worth_stage_5 = config('global_vars.euro_worth_stage_5');
		$new_bonus_percent = 0;
		$euroPercentUnpaid = 0;
		$unpaid_bonus_percent = 0;
		$euro_elt_worth = self::get_user_elt_worth_in_euro($userid);
		$euro_worth_total = $euro_elt_worth[0]->euro_worth_total;		
		if(CommonHelper::isUserLogin()){
			$bonus_level = Auth::user()->bonus_level;
		}
		else{
			$userConfigRow = self::get_user_config_setting($userid);
			$bonus_level = $userConfigRow->bonus_level;
		}		
		if($bonus_level > 0)
		{
			switch ($bonus_level) 
			{
				case 5:
				case 4:
				case 3:
				case 2:
				case 1:
						$returnData = self::get_new_bonus_slab($bonus_level,$actual_percent);break;
				default:
						$returnData = self::get_new_bonus_slab(0,$actual_percent);break;
			}
			return $returnData;
		}
		
		if($euro_worth_total >= $euro_worth_stage_5['min'])
		{
			$returnData = self::get_new_bonus_slab(5,$actual_percent);
		}
		elseif($euro_worth_total >= $euro_worth_stage_4['min'] && $euro_worth_total < $euro_worth_stage_4['max'])
		{
			$returnData = self::get_new_bonus_slab(4,$actual_percent);
		}
		elseif($euro_worth_total >= $euro_worth_stage_3['min'] && $euro_worth_total < $euro_worth_stage_3['max'])
		{
			$returnData = self::get_new_bonus_slab(3,$actual_percent);
		}		
		elseif($euro_worth_total >= $euro_worth_stage_2['min'] && $euro_worth_total < $euro_worth_stage_2['max'])
		{
			$returnData = self::get_new_bonus_slab(2,$actual_percent);
		}		
		elseif($euro_worth_total >= $euro_worth_stage_1['min'] && $euro_worth_total < $euro_worth_stage_1['max'])
		{
			$returnData = self::get_new_bonus_slab(1,$actual_percent);
		}
		elseif($euro_worth_total <= $euro_worth_stage_0['max'])
		{
			$returnData = self::get_new_bonus_slab(0,$actual_percent);
		}
		
		return $returnData;
		
	}
	
	public static function get_new_bonus_slab($level,$actual_percent)
	{
		$new_bonus_percent = 0;		
		$unpaid_bonus_percent = 0;		
		if($level == 5)
		{
			$euroPercent = DB::table('configurations')->select('defined_value')->where('name','Referral-%-Level-Euro-10k')->where('valid_to', '=', "9999-12-31")->first();	
			$new_bonus_percent =  ($actual_percent * $euroPercent->defined_value)/100;
		}
		elseif($level == 4)
		{
			$euroPercent = DB::table('configurations')->select('defined_value')->where('name','Referral-%-Level-Euro-8k')->where('valid_to', '=', "9999-12-31")->first();
			$new_bonus_percent =  ($actual_percent * $euroPercent->defined_value)/100;
		}
		elseif($level == 3)
		{
			$euroPercent = DB::table('configurations')->select('defined_value')->where('name','Referral-%-Level-Euro-4k')->where('valid_to', '=', "9999-12-31")->first();
			$new_bonus_percent =  ($actual_percent * $euroPercent->defined_value)/100;
		}		
		elseif($level == 2)
		{
			$euroPercent = DB::table('configurations')->select('defined_value')->where('name','Referral-%-Level-Euro-2k')->where('valid_to', '=', "9999-12-31")->first();
			$new_bonus_percent =  ($actual_percent * $euroPercent->defined_value)/100;
		}		
		elseif($level == 1)
		{
			$euroPercent = DB::table('configurations')->select('defined_value')->where('name','Referral-%-Level-Euro-1k')->where('valid_to', '=', "9999-12-31")->first();
			$new_bonus_percent =  ($actual_percent * $euroPercent->defined_value)/100;
		}
		elseif($level == 0)
		{
			$euroPercent = DB::table('configurations')->select('defined_value')->where('name','Referral-%-Level-Euro-0k')->where('valid_to', '=', "9999-12-31")->first();
			$new_bonus_percent =  ($actual_percent * $euroPercent->defined_value)/100;
		}
		
		if($actual_percent >= $new_bonus_percent)
		{
			$unpaid_bonus_percent = $actual_percent - $new_bonus_percent;
		}
		
		return array("new_bonus_percent"=>$new_bonus_percent,"unpaid_bonus_percent"=>$unpaid_bonus_percent);
	}
	
	public static function get_total_transaction_sum($ledger, $date='')
	{		
		if(isset($date) && $date!='')
		{
			$response = DB::select( DB::raw("SELECT SUM(value) as Total_Sum FROM transactions WHERE ledger = '$ledger' AND value NOT LIKE '%-%' AND status=1 AND description LIKE 'Converted unit%' AND DATE(created_at)='".$date."'") );
		}
		else
		{
			$response = DB::select( DB::raw("SELECT SUM(value) as Total_Sum FROM transactions WHERE ledger = '$ledger' AND value NOT LIKE '%-%' AND status=1") );
		}
		
		if(isset($response[0]->Total_Sum))
		{
			return $response[0]->Total_Sum;
		}
		else
		{
			return 0;
		}
	}
	
	public static function get_total_payments($ledger, $date='')
	{
		if(isset($date) && $date!='')
		{
			$response = DB::select( DB::raw("
			SELECT SUM(term_amount) as Total_Sum 
			FROM transactions 
			WHERE ledger = 'ELT' AND status=1 AND type IN (1) AND type_name NOT IN ('bonus') AND term_currency='$ledger' AND DATE(created_at)='".$date."'") );
		}
		else
		{
			$response = DB::select( DB::raw("
			SELECT SUM(term_amount) as Total_Sum 
			FROM transactions 
			WHERE ledger = 'ELT' AND status=1 AND type IN (1) AND type_name NOT IN ('bonus') AND term_currency='$ledger'") );
		}
		
		if(isset($response[0]->Total_Sum))
		{
			return $response[0]->Total_Sum;
		}
		else
		{
			return 0;
		}
	}
	
	
	public static function get_all_coin_transactions()
	{				
		$response = DB::select( DB::raw("SELECT transactions.*, DATE(transactions.created_at) AS today_date FROM transactions WHERE term_currency IN ('NEXO', 'SALT', 'ORME')") );
		return $response;
	}
	
	public static function get_total_external_sum($ledger, $date='')
	{
		if(isset($date) && $date!='')
		{
			$response = DB::select( DB::raw("SELECT SUM(value) as Total_Sum FROM transactions WHERE ledger = '$ledger' AND value NOT LIKE '%-%' AND status=1 AND type=1 AND DATE(created_at)='".$date."'") );
		}
		else
		{
			$response = DB::select( DB::raw("SELECT SUM(value) as Total_Sum FROM transactions WHERE ledger = '$ledger' AND value NOT LIKE '%-%' AND status=1 AND type=1") );
		}
	
		if(isset($response[0]->Total_Sum))
		{
			return $response[0]->Total_Sum;
		}
		else
		{
			return 0;
		}
	}
	
	public static function getAllSuccessTransactionTest()
	{
		$result = DB::select( DB::raw("SELECT id,value,ledger,term_currency,term_amount,status,description FROM transactions WHERE value NOT LIKE '%-%'"));
		return $result;
	}
	
	public static function update_table_row($table,$data,$id)
	{
		return DB::table($table)->where('id',$id)->update($data);
	}
	
	public static function total_phase_token_amount()
	{
		$result = DB::select( DB::raw("SELECT SUM(token_target) as total_token_target FROM phases WHERE id!=1"));
		return round($result[0]->total_token_target);
	}

	public static function getAllSuccessTransaction($ledger='ETH')
	{
		$result = DB::select( DB::raw("SELECT id,value,ledger,term_currency,term_amount,status FROM transactions WHERE value NOT LIKE '%-%' AND status=1"));
		return $result;
	}

    public static function total_elt_distribution()
    {
        $result = DB::select(DB::raw("SELECT SUM(value) as total_elt_distribution FROM transactions WHERE status=1 AND ledger='ELT'"));
        return round($result[0]->total_elt_distribution);
	}
	
	public static function total_elt_distributed_in_current_phase()
	{
		$phase_row = DB::select( DB::raw("SELECT id,phase_start_date,phase_end_date FROM phases WHERE status=1"));
		$result = DB::select( DB::raw("SELECT SUM(value) as total_elt_in_phase FROM transactions WHERE status=1 AND ledger='ELT' AND created_at BETWEEN '".$phase_row[0]->phase_start_date." 17:00:00' AND '".$phase_row[0]->phase_end_date." 17:00:00'"));
		return round($result[0]->total_elt_in_phase);
	}

	public static function get_elt_eth_euro_barometer()
	{
		$data = array();		
		$data['total_elt_distribution'] = self::total_elt_distribution();
		$Conversion_EUR_ELT = Configurations::where([['valid_to','9999-12-31'],['name','Conversion-EUR-ELT']])->get();
		$data['elt_in_euro'] = ($data['total_elt_distribution'])*(1/$Conversion_EUR_ELT[0]->defined_value);		
		$EURO_rates = CommonHelper::get_coinbase_currency("EUR");		
		$data['elt_in_eth'] = $data['elt_in_euro'] * $EURO_rates['data']['rates']['ETH'];
		return $data;		
	}
	
	public static function get_transactions_list($transaction_ids)
	{
		$Sql = "SELECT * FROM transactions WHERE transaction_id IN ('".implode("','",$transaction_ids)."')";				
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	public static function get_current_phase_id()
	{
		$row = DB::table('phases')->select('id')->where('status','=',1)->first();
		if(isset($row->id) && $row->id > 0)
		{
			return $row->id;
		}
		return 0;		
	}
	
	public static function get_user_config_setting($userid)
	{
		$row = DB::table('users')->select('*')->where('id','=',$userid)->first();
		if(isset($row->id) && $row->id > 0)
		{
			return $row;
		}
		return '';		
	}
	
	public static function get_user_paid_bonus($userid)
	{
		$Sql = "
		SELECT ledger, SUM(value) as total_paid_bonus 
		FROM transactions 
		WHERE value > 0 AND user_id = ".$userid." AND status=1 AND type_name='bonus' 
		GROUP BY ledger";
		$result = DB::select( DB::raw($Sql));
		$paid_currency_bonus = array("BTC"=>0,"ETH"=>0,"ELT"=>0,"EUR"=>0,"LTC"=>0,"BCH"=>0,"XRP"=>0,"DASH"=>0);
		foreach($result as $row){
			$paid_currency_bonus[$row->ledger] = $row->total_paid_bonus;
		}		
		return $paid_currency_bonus;
	}
	
	public static function get_user_unpaid_bonus($userid)
	{
		$Sql = "
		SELECT ledger, SUM(unpaid_bonus) as total_unpaid_bonus 
		FROM transactions 
		WHERE unpaid_bonus > 0 AND user_id = ".$userid." AND status=1 AND type_name='bonus' 
		GROUP BY ledger";
		$result = DB::select( DB::raw($Sql));
		$unpaid_currency_bonus = array("BTC"=>0,"ETH"=>0,"ELT"=>0,"EUR"=>0,"LTC"=>0,"BCH"=>0,"XRP"=>0,"DASH"=>0);
		foreach($result as $row){
			$unpaid_currency_bonus[$row->ledger] = $row->total_unpaid_bonus;
		}
		return $unpaid_currency_bonus;
	}
	
	public static function add_row_in_table($table,$data)
	{
		return DB::table($table)->insert($data);
	}
	
	public static function get_invoice_no($userid)
	{
		$Sql = "
		SELECT count(id) as user_invoice_count 
		FROM elt_invoices 
		WHERE elt_invoices.user_id = '".$userid."'";
		$result = DB::select( DB::raw($Sql));
		return ($result[0]->user_invoice_count)+1;
	}
	
	public static function get_invoice_detail($invoice_number)
	{
		$Sql = "
		SELECT elt_invoices.*, users.first_name, users.last_name, users.email, users.address1, users.address2, users.city, users.postal_code, users.city, countries.name as country_name
		FROM elt_invoices
		LEFT JOIN users ON users.id = elt_invoices.user_id
		LEFT JOIN countries ON users.country_code = countries.code
		WHERE elt_invoices.invoice_number = '".$invoice_number."' LIMIT 1";
		$result = DB::select( DB::raw($Sql));
		return $result[0];
	}
	public static function get_performa_detail($reference_no)
	{
		$Sql = "
		SELECT proforma_invoices.*, users.first_name, users.last_name, users.email, users.address1, users.address2, users.city, users.postal_code, users.city, countries.name as country_name
		FROM proforma_invoices
		LEFT JOIN users ON users.id = proforma_invoices.user_id
		LEFT JOIN countries ON users.country_code = countries.code
		WHERE proforma_invoices.reference_no = '".$reference_no."' LIMIT 1";
		$result = DB::select( DB::raw($Sql));
		return $result[0];
	}
	
	public static function get_invoice_bonus($ref_transaction_id, $userId)
	{
		$Sql = "SELECT * FROM transactions WHERE  ref_transaction_id= '".$ref_transaction_id."' AND user_id='".$userId."' AND type_name='bonus' AND ledger='ELT' AND description LIKE 'Additional%'";
		$result = DB::select( DB::raw($Sql));
		return isset($result[0]->id)?$result[0]:'';
	}
	
	/* Get ?-EURO conversion rate with fee */
    public static function getConversionInEUROWithFee($currency, $amount)
    {
        $__to_euro = 0;
        $total_amount_in_EUR = 0;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $allowedTypeCoinBaseAPI = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC');
		$Conversion_EUR_Fee = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-' . $currency . '-EUR-Fee']])->get();		
	    if (in_array($currency, $allowedTypeCoinBaseAPI)) {
            $coinbase_rate = CommonHelper::get_coinbase_currency($currency);
            $__to_euro = $coinbase_rate['data']['rates']['EUR'];
        } elseif (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
		elseif($currency == 'EUR' || $currency == 'ELT')
		{
			$__to_euro = 1;
			if($currency == 'ELT')
			{		
				$euro_to_elt = self::getConversionInEUROTOELTWithFee();
				$__to_euro = 1/$euro_to_elt;	
			}
		}
        if ($__to_euro > 0) {
            if($currency == 'EUR' || $currency == 'ELT'){
				$total_amount_in_EUR = 1;
				if($currency == 'ELT')
				{				
					$euro_to_elt = self::getConversionInEUROTOELTWithFee();
					$total_amount_in_EUR = 1/$euro_to_elt;
				}
			}
			else
			{
				$__to_euro_reverse = 1 / $__to_euro;
				$fees =  $Conversion_EUR_Fee[0]->defined_value;
				$total_amount_in_EUR = (1 / $__to_euro_reverse) ;
			}
        }		
		return number_format($total_amount_in_EUR * $amount,2);
    }
	
	/* Get EURO-ELT conversion rate with fee */
    public static function getConversionInEUROTOELTWithFee()
    {
		$Conversion_EUR_ELT = Configurations::where([['valid_to', '9999-12-31'],['name','Conversion-EUR-ELT']])->get();	
		return number_format($Conversion_EUR_ELT[0]->defined_value, 6);
    }
	
	public static function get_user_referral_at_level($userId, $level=1, $type='count')
	{
		$level_array = array();
		if($level != 'all'){
			$level_array[] = $level;
		}
		$returnData  =  self::get_user_referral_at_level_pc($userId, $level_array,$type);
		return $returnData;
	}
	
	public static function get_user_downline_list($userId, $level='all', $type='count')
	{
		$selectSql = '';		
		if($type == 'list')
		{
			$selectSql = 'SELECT child_id,parent_id,level';
		}
		else
		{
			$selectSql = 'SELECT count(child_id) as referral_count';
		}
		
		if($level != 'all')
		{
			$levelSql = " AND level = $level";
		}
		else
		{
			$levelSql = '';
		}
		
		$sql = $selectSql." FROM parent_child_relation WHERE parent_id = $userId $levelSql";
		
		$result = DB::select( DB::raw($sql));
		
		if($type == 'list')
		{
			$referrer_user_id = array();
			foreach($result as $row)
			{
				$referrer_user_id[] = $row;
			}
			return $referrer_user_id;
		}
		else
		{
			return $result[0]->referral_count;
		}
	}
	
	public static function get_user_referral_all_five_level($userId, $type='count')
	{
		return self::get_user_referral_at_level_pc($userId, array(1,2,3,4,5),$type);
	}
	
	public static function get_user_referral_at_level_pc($userId, $level = array(1), $type='count')
	{		
		$selectSql = '';
		if($type == 'list'){
			$selectSql = 'SELECT child_id as referral_id';
		}
		else{
			$selectSql = 'SELECT count(child_id) as referral_count';
		}
		if(count($level) > 0){
			$levelSql = " AND level IN (".implode(",",$level).")";
		}
		else{
			$levelSql = '';
		}
		$sql = $selectSql." FROM parent_child_relation WHERE parent_id = $userId $levelSql";		
		$result = DB::select( DB::raw($sql));
		if($type == 'list'){
			$referrer_user_id = array();
			foreach($result as $row){
				$referrer_user_id[] = $row->referral_id;
			}
			return $referrer_user_id;
		}
		else{
			return $result[0]->referral_count;
		}
	}
	
	public static function get_user_referral_at_level_one_pc($userId, $type='count')
	{		
		$selectSql = '';
		if($type == 'list'){
			$selectSql = 'SELECT id as referral_id';
		}
		else{
			$selectSql = 'SELECT count(id) as referral_count';
		}		
		$sql = $selectSql." FROM users WHERE referrer_user_id = $userId";		
		$result = DB::select( DB::raw($sql));
		if($type == 'list'){
			$referrer_user_id = array();
			foreach($result as $row){
				$referrer_user_id[] = $row->referral_id;
			}
			return $referrer_user_id;
		}
		else
		{
			return $result[0]->referral_count;
		}
	}
	
	
	public static function get_levelwise_user_elt_euro_worth($loginid)
	{
		$returnData = array();		
		$five_level_elt_worth = 0;
		$five_level_euro_worth = 0;
		$all_level_elt_worth = 0;
		$all_level_euro_worth = 0;		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();		
		$referral_level_all_list = self::get_user_downline_list($loginid,'all','list');
		$referral_level_all_count = count($referral_level_all_list);		
		foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}		
		$referral_level_1_count = count($referral_level_1_list);
		$referral_level_2_count = count($referral_level_2_list);
		$referral_level_3_count = count($referral_level_3_list);
		$referral_level_4_count = count($referral_level_4_list);
		$referral_level_5_count = count($referral_level_5_list);		
		$referral_level_five_count = $referral_level_1_count + $referral_level_2_count + $referral_level_3_count + $referral_level_4_count+ $referral_level_5_count;		
		$returnData['referral_level_1'] = $referral_level_1_count;
		$returnData['referral_level_2'] = $referral_level_2_count;
		$returnData['referral_level_3'] = $referral_level_3_count;
		$returnData['referral_level_4'] = $referral_level_4_count;
		$returnData['referral_level_5'] = $referral_level_5_count;
		$returnData['referral_level_five_count'] = $referral_level_five_count;
		$returnData['referral_level_all'] = $referral_level_all_count;
		$returnData['level_1_sales_data'] = array();
		if(count($referral_level_1_list) > 0){
			$data = self::get_user_elt_worth_in_euro($referral_level_1_list,'list');
			$returnData['level_1_sales_data'] = $data[0]; 
			$all_level_elt_worth+=$data[0]->elt_worth_total;
			$all_level_euro_worth+=$data[0]->euro_worth_total;
			$five_level_elt_worth+=$data[0]->elt_worth_total;
			$five_level_euro_worth+=$data[0]->euro_worth_total;
		}
		
		$returnData['level_2_sales_data'] = array();
		if(count($referral_level_2_list) > 0){
			$data = self::get_user_elt_worth_in_euro($referral_level_2_list,'list');
			$returnData['level_2_sales_data'] = $data[0];
			$all_level_elt_worth+=$data[0]->elt_worth_total;
			$all_level_euro_worth+=$data[0]->euro_worth_total;
			$five_level_elt_worth+=$data[0]->elt_worth_total;
			$five_level_euro_worth+=$data[0]->euro_worth_total;
		}
		
		$returnData['level_3_sales_data'] = array();
		if(count($referral_level_3_list) > 0){
			$data = self::get_user_elt_worth_in_euro($referral_level_3_list,'list');
			$returnData['level_3_sales_data'] = $data[0]; 
			$all_level_elt_worth+=$data[0]->elt_worth_total;
			$all_level_euro_worth+=$data[0]->euro_worth_total;
			$five_level_elt_worth+=$data[0]->elt_worth_total;
			$five_level_euro_worth+=$data[0]->euro_worth_total;
		}
		
		$returnData['level_4_sales_data'] = array();
		if(count($referral_level_4_list) > 0){
			$data = self::get_user_elt_worth_in_euro($referral_level_4_list,'list');
			$returnData['level_4_sales_data'] = $data[0]; 
			$all_level_elt_worth+=$data[0]->elt_worth_total;
			$all_level_euro_worth+=$data[0]->euro_worth_total;
			$five_level_elt_worth+=$data[0]->elt_worth_total;
			$five_level_euro_worth+=$data[0]->euro_worth_total;
		}
		
		$returnData['level_5_sales_data'] = array();
		if(count($referral_level_5_list) > 0){
			$data = self::get_user_elt_worth_in_euro($referral_level_5_list,'list');
			$returnData['level_5_sales_data'] = $data[0]; 
			$all_level_elt_worth+=$data[0]->elt_worth_total;
			$all_level_euro_worth+=$data[0]->euro_worth_total;			
			$five_level_elt_worth+=$data[0]->elt_worth_total;
			$five_level_euro_worth+=$data[0]->euro_worth_total;
		}
		
		$returnData['level_all_sales_data'] = array();
		if(count($referral_level_all_id) > 0){
			$data = self::get_user_elt_worth_in_euro($referral_level_all_id,'list');
			$returnData['level_all_sales_data'] = $data[0]; 
			$all_level_elt_worth+=$data[0]->elt_worth_total;
			$all_level_euro_worth+=$data[0]->euro_worth_total;
		}		
		$returnData['five_level_elt_worth'] = $five_level_elt_worth;
		$returnData['five_level_euro_worth'] = $five_level_euro_worth;
		$returnData['all_level_elt_worth'] = $all_level_elt_worth;
		$returnData['all_level_euro_worth'] = $all_level_euro_worth;
		return $returnData;
	}
	
	public static function get_currency_to_euro_rate($currency)
	{
		$currency_to_euro = 0;
        $total_amount_in_EUR = 0;		
        $braveCoinCurrencies = array('ETC', 'XRP', 'DASH');
        $baseCoinCurrencies = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC');
	    if (in_array($currency, $baseCoinCurrencies)) {
            $coinbase_rate = CommonHelper::get_coinbase_currency($currency);
            $currency_to_euro = $coinbase_rate['data']['rates']['EUR'];
        } 
		elseif (in_array($currency, $braveCoinCurrencies)) {
            $currency_to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
		elseif($currency == 'EUR'){
			return 1;
		}
		elseif($currency == 'ELT')
		{
			return self::get_current_elt_euro_rate();
		}
        if ($currency_to_euro > 0) {
			$Conversion_EUR_Fee = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-' . $currency . '-EUR-Fee']])->get();	
			$fees =  $Conversion_EUR_Fee[0]->defined_value;
			//$currency_to_euro = $currency_to_euro - ($currency_to_euro * $fees)/100;
			$total_amount_in_EUR = $currency_to_euro;
        }
		return $total_amount_in_EUR;
	}
	
	public static function call_accounting_proc($tabname='bonus', $currency='ETH')
	{
		$returnData = array();		
		$current_week_date_range = CommonHelper::current_week_date_range();
		$this_week_sd = $current_week_date_range['start_week'];
		$this_week_ed = $current_week_date_range['end_week'];
		$accounting_reset_from_date = config("constants.accounting_reset_from_date");
		if($tabname == 'sales')
		{
			if($currency=='all'){
				DB::select("CALL procSalesRevenueAll(@Today_data, @All_data, '".$this_week_sd."','".$this_week_ed."', @Week_data, @Month_data, @Year_data, '".$accounting_reset_from_date."')");
				$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
			}
			else{
				DB::select("CALL procSalesRevenueCurrency(@Today_data, @All_data, '".$currency."','".$this_week_sd."','".$this_week_ed."', @Week_data, @Month_data, @Year_data, '".$accounting_reset_from_date."')");
				$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
			}
		}
		elseif($tabname == 'bonus')
		{
			if($currency=='all'){				
				DB::select("CALL procPaidBonusAll(@Today_data, @All_data, '".$this_week_sd."','".$this_week_ed."', @Week_data, @Month_data, @Year_data, '".$accounting_reset_from_date."')");
				$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
			}
			else{
				DB::select("CALL procPaidBonus(@Today_data, @All_data, '".$currency."','".$this_week_sd."','".$this_week_ed."', @Week_data, @Month_data, @Year_data, '".$accounting_reset_from_date."')");
				$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
			}
		}
		elseif($tabname == 'unpaid')
		{
			if($currency=='all'){
				DB::select("CALL procUnpaidBonusAll(@Today_data, @All_data, '".$this_week_sd."','".$this_week_ed."', @Week_data, @Month_data, @Year_data, '".$accounting_reset_from_date."')");
				$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
			}
			else{
				DB::select("CALL procUnpaidBonus(@Today_data, @All_data, '".$currency."','".$this_week_sd."','".$this_week_ed."', @Week_data, @Month_data, @Year_data, '".$accounting_reset_from_date."')");
				$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
			}
		}		
		$today_stats = explode("__",$procResponse[0]->today_stats);		
		$week_stats = explode("__",$procResponse[0]->week_stats);	
		$month_stats = explode("__",$procResponse[0]->month_stats);	
		$year_stats = explode("__",$procResponse[0]->year_stats);	
		$all_stats = explode("__",$procResponse[0]->all_stats);	
		
		$returnData["today"] = CommonHelper::format_wallet_balance($today_stats[0],config("constants.DEFAULT_PRECISION"));
		$returnData["today_euro"] = CommonHelper::format_wallet_balance($today_stats[1],config("constants.EUR_PRECISION"));
		
		$returnData["week"] = CommonHelper::format_wallet_balance($week_stats[0],config("constants.DEFAULT_PRECISION"));
		$returnData["week_euro"] = CommonHelper::format_wallet_balance($week_stats[1],config("constants.EUR_PRECISION"));
		
		$returnData["month"] = CommonHelper::format_wallet_balance($month_stats[0],config("constants.DEFAULT_PRECISION"));
		$returnData["month_euro"] = CommonHelper::format_wallet_balance($month_stats[1],config("constants.EUR_PRECISION"));
		
		$returnData["year"] = CommonHelper::format_wallet_balance($year_stats[0],config("constants.DEFAULT_PRECISION"));
		$returnData["year_euro"] = CommonHelper::format_wallet_balance($year_stats[1],config("constants.EUR_PRECISION"));
		
		$returnData["all"] = CommonHelper::format_wallet_balance($all_stats[0],config("constants.DEFAULT_PRECISION"));		
		$returnData["all_euro"] = CommonHelper::format_wallet_balance($all_stats[1],config("constants.EUR_PRECISION"));
		
		return $returnData;
	}
	
	public static function getUserDownlineSalesData($userid, $type, $level=1)
	{
		$returnData = array();		
		$current_week_date_range = CommonHelper::current_week_date_range();
		$this_week_sd = $current_week_date_range['start_week'];
		$this_week_ed = $current_week_date_range['end_week'];
		
		if($type == 'level' && $level > 0)
		{
			DB::select("CALL procDownlineLevelSales(@Today_data, @Week_data, @Month_data, @Year_data, @All_data, '".$this_week_sd."','".$this_week_ed."', '".$userid."', '".$level."')");
			$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
		}
		else if($type == 'five')
		{
			DB::select("CALL procDownlineLevelFiveSales(@Today_data, @Week_data, @Month_data, @Year_data, @All_data, '".$this_week_sd."','".$this_week_ed."', '".$userid."')");
			$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
		}
		else if($type == 'all')
		{
			DB::select("CALL procDownlineLevelAllSales(@Today_data, @Week_data, @Month_data, @Year_data, @All_data, '".$this_week_sd."','".$this_week_ed."', '".$userid."')");
			$procResponse = DB::select("SELECT @Today_data as today_stats, @All_data as all_stats, @Week_data as week_stats, @Month_data as month_stats, @Year_data as year_stats");
		}
				
		$today_stats = $procResponse[0]->today_stats;
		$week_stats = $procResponse[0]->week_stats;	
		$month_stats = $procResponse[0]->month_stats;	
		$year_stats = $procResponse[0]->year_stats;	
		$all_stats = $procResponse[0]->all_stats;	
		
		$returnData["today"] = CommonHelper::format_wallet_balance($today_stats,config("constants.DEFAULT_PRECISION"));
		
		$returnData["week"] = CommonHelper::format_wallet_balance($week_stats,config("constants.DEFAULT_PRECISION"));
			
		$returnData["month"] = CommonHelper::format_wallet_balance($month_stats,config("constants.DEFAULT_PRECISION"));
			
		$returnData["year"] = CommonHelper::format_wallet_balance($year_stats,config("constants.DEFAULT_PRECISION"));
		
		$returnData["all"] = CommonHelper::format_wallet_balance($all_stats,config("constants.DEFAULT_PRECISION"));		
		return $returnData;
	}
	
	public static function get_current_elt_euro_rate($type='elt_euro')
	{
		$result = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-EUR-ELT']])->get();
		if($type == 'elt_euro'){
			return (1/$result[0]->defined_value);
		}
		elseif($type == 'euro_elt'){
			return $result[0]->defined_value;
		}
		else{
			return 1;
		}		
	}	
	
	public static function get_periodic_table_data($filterType='today',$start_date='',$end_date='',$currencyList=array('BTC','ETH','EUR','BCH','LTC','XRP','DASH'), $tabame='sales')
	{
		$accounting_reset_from_date = config("constants.accounting_reset_from_date");
		$returnData = array();		
		$dateFilterSql = '';		
		$today = date("Y-m-d");
		if(!empty($start_date) && !empty($end_date)){
			$dateFilterSql = " AND DATE(created_at) BETWEEN '".$start_date."' AND '".$end_date."' ";
		}
		elseif($filterType == 'all' ){
			$dateFilterSql='';
		}
		else{
			$dateFilterSql = " AND DATE(created_at)='".$today."'";
		}		
		if($tabame=='sales'){
			$Sql = "
			SELECT 
			term_currency as currency_name,
			SUM(term_amount) as total_sales,
			SUM(value) as total_elt, 
			SUM(term_amount*value_in_euro) as total_sale_in_euro			
			FROM transactions 
			WHERE DATE(created_at)>='".$accounting_reset_from_date."' AND status=1 AND type_name NOT IN ('bonus') AND ledger='ELT' AND transactions.type IN (1,5) AND exclude_in_sales=0 AND term_currency IN ('".implode("','",$currencyList)."') $dateFilterSql 
			GROUP BY term_currency";
		}
		elseif($tabame=='bonus'){
			$Sql = "
			SELECT 
			ledger as currency_name,
			SUM(value) as total_sales,
			0 as total_elt,
			SUM(value*value_in_euro) as total_sale_in_euro
			FROM transactions 
			WHERE DATE(created_at)>='".$accounting_reset_from_date."' AND status=1 AND type_name IN ('bonus') AND ledger!='ELT' AND transactions.type IN (1,5) AND 
			ledger IN ('".implode("','",$currencyList)."') $dateFilterSql 
			GROUP BY ledger";
		}
		elseif($tabame=='unpaid'){
			$Sql = "
			SELECT 
			ledger as currency_name,
			SUM(unpaid_bonus) as total_sales,
			0 as total_elt,
			SUM(value*value_in_euro) as total_sale_in_euro
			FROM transactions 
			WHERE DATE(created_at)>='".$accounting_reset_from_date."' AND status=1 AND type_name IN ('bonus') AND ledger!='ELT' AND transactions.type IN (1,5) AND 
			ledger IN ('".implode("','",$currencyList)."') $dateFilterSql 
			GROUP BY ledger";
		}
		//echo $Sql;die;
		$result = DB::select(DB::raw($Sql));
		if($result){
			foreach($result as $row){
				$returnData[$row->currency_name] = $row;
			}
		}
		
		return $returnData;		
	}
	
	public static function get_wallet_change_status($userid,$type)
	{
		$Sql = "SELECT * FROM change_requests WHERE type=$type AND user_id=$userid AND is_delete=1 ORDER BY id DESC LIMIT 1";		
		$result = DB::select(DB::raw($Sql));		
		$time_diff = config('constants.UPDATE_WALLET_TIME_IN_HRS') * 60 * 60;
		//$time_diff = 120;
		
		if($result[0])
		{			
			$passed_time = time() - strtotime($result[0]->updated_at);
			if($passed_time >= $time_diff)
			{
				return 1;
			}
		}
		return 0;
	}
	
}
