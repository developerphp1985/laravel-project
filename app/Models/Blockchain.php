<?php

namespace App\Models;
use DB;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Blockchain extends Model
{
    //
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'bc_transations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id',
        'user_id',
        'ledger',
        'description',
        'value',
        'status',
		'term_currency',
		'term_amount',
		'transaction_type',
        'status_message',
        'phase_id',
        'address',
        'ipaddress'
    ];

    public function user() { 
        return $this->belongsTo('App\Models\User', 'user_id');
    }
	
	public static function get_datatables_join($column_order, $postarray, $send_count=0)	
	{
		$allowedCurrency = config('global_vars.allowedCurrencyList');
		
		$query = Transactions::query();
		$query->leftJoin('users', 'transactions.user_id', '=', 'users.id');			
		$query->select('transactions.*','users.user_name','users.email');
	
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))		
		{	
			$search_text = $postarray['search_text'];	
			$search_text1 =	explode(' ',$search_text);
			$search_text3 = $search_text1[0];
		
			if(isset($search_text1[1]))
			{
				$search_text2 = $search_text1[1];
			} 
			else 
			{
				$search_text2 = $search_text1[0];
			}
			
			$query->where(function($query) use ($search_text3, $search_text,$search_text2)			
			{	
				$query->where('transactions.transaction_id', 'LIKE', "%$search_text%")	
				->orWhere('transactions.ledger', 'LIKE', "%$search_text%")				
				->orWhere('users.user_name', 'LIKE', "%$search_text3%")				
				->orWhere('users.email', 'LIKE', "%$search_text%")				
				->orWhere('users.last_name', 'LIKE', "%$search_text2%");	 	
			});		
		}
		
		if(!empty($postarray['start_date']) && !empty($postarray['end_date']))
		{
			$start_date_array = explode("/",$postarray['start_date']);		
			$end_date_array = explode("/",$postarray['end_date']);		
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];		
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';			
			$end_date = $end_date.' 23:59:59';
			$query->where('transactions.created_at', '>=', $start_date);	
			$query->where('transactions.created_at', '<=', $end_date);	
		}
		
		$currency_filter = '';
		
		if(isset($postarray['currency_filter']) && !empty($postarray['currency_filter']))
		{	
			$currency_filter = $postarray['currency_filter'];
			$query->where(function($query) use ($currency_filter)			
			{	
				$query->where('transactions.ledger', '=', $currency_filter)
					  ->orWhere('transactions.term_currency', '=', $currency_filter);	 	
			});
		}
		
		$status_filter = $postarray['status_filter'];
		if(in_array($postarray['status_filter'],array(0,1,2,3)))
		{
			$status_filter = $postarray['status_filter'];
			if($postarray['status_filter'] == 3)
				$query->where('transactions.status', '=',0);
			else
				$query->where('transactions.status', '=', $status_filter);
		}
		
		if($send_count == 0)
		{
			if(isset($postarray['start']))
			{
				$query->offset($postarray['start']);			
			}
			
			if(isset($postarray['length']))
			{
				$query->limit($postarray['length']);		
			}
		}

		if(isset($postarray['order'])) 		
		{		
			$direction = 'desc';		
			if($postarray['order']['0']['dir'] == 'asc')		
			{	
				$direction = 'desc';			
			}		
			else if($postarray['order']['0']['dir'] == 'desc')			
			{	
				$direction = 'asc';			
			}		
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);	
		}		
		else	
		{		
			$query->orderBy('transactions.created_at', 'desc');
		}	
		
		//echo $query->toSql();die;
		
		if($send_count == 1)
		{
			return $query->count();
		}
		else
		{
			return $query->get();
		}
		
	}
		
	public static function get_datatables_count($column_order, $postarray)	
	{		
		$query = Transactions::query();
		$query->leftJoin('users', 'transactions.user_id', '=', 'users.id');			
		$query->select('transactions.*','users.user_name','users.email');
	
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))		
		{	
			$search_text = $postarray['search_text'];	
			$search_text1 =	explode(' ',$search_text);
			$search_text3 = $search_text1[0];
		
			if(isset($search_text1[1])){
				$search_text2 = $search_text1[1];
			} 
			else {
				$search_text2 = $search_text1[0];
			}
			
			$query->where(function($query) use ($search_text3, $search_text,$search_text2)			{	
			 $query->where('transactions.transaction_id', 'LIKE', "%$search_text%")	
			->orWhere('transactions.ledger', 'LIKE', "%$search_text%")				
			->orWhere('users.user_name', 'LIKE', "%$search_text3%")				
			->orWhere('users.email', 'LIKE', "%$search_text%")				
			->orWhere('users.last_name', 'LIKE', "%$search_text2%");	 	
			});		
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date'])){
			$start_date_array = explode("/",$_POST['start_date']);		
			$end_date_array = explode("/",$_POST['end_date']);		
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];		
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';			
			$end_date = $end_date.' 23:59:59';
			$query->where('transactions.created_at', '>=', $start_date);	
			$query->where('transactions.created_at', '<=', $end_date);	
		}	
		
		if(isset($_POST['currency_filter']) && !empty($_POST['currency_filter']))
		{			
			$query->where('transactions.ledger', '=', $_POST['currency_filter']);
		}
		
		if(isset($postarray['start']))
		{
			$query->offset($postarray['start']);			
		}
		
		if(isset($postarray['length']))
		{
			$query->limit($postarray['length']);		
		}

		if(isset($postarray['order'])) 		
		{		
			$direction = 'desc';		
			if($postarray['order']['0']['dir'] == 'asc')		
			{	
				$direction = 'desc';			
			}		
			else if($postarray['order']['0']['dir'] == 'desc')			
			{	
				$direction = 'asc';			
			}		
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);	
		}		
		else	
		{		
			$query->orderBy('transactions.created_at', 'desc');
		}	
		return $query->count();
	}
	
    /**
     * The method to add provided value to existing balance.
     *
     * @var key - column name to be updated 
     * @var value - value to be subtracted
     */
    public static function createTransaction($userId, $balanceType, $amount, $description, $status, $transaction_id, $phaseId = 0, $address = NULL, $transaction_type='other')
    {
        if( (isset($amount) && ($amount!=0)) && ($balanceType != '') ) { 
            $transactionCreate = self::create([
                    'user_id'         => $userId,
                    'ledger'          => $balanceType,
                    'value'           => $amount,
                    'description'     => $description,
                    'status'          => $status,//1
                    'transaction_id'  => $transaction_id, 
                    'phase_id'        => $phaseId,
					'address'         => $address,
					'transaction_type'=>$transaction_type,
                    'ipaddress'       => \Request::getClientIp(true)
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
                        'ipaddress'       => \Request::getClientIp(true)
                    )
                )
            ]
        ];
        LoggerHelper::writeDB($record);
    }
	public static function get_datatables_join_blockchain($column_order, $postarray, $Custom_Eth_Address='', $is_count=0)
	{		
		$query = Blockchain::query();
		
		$query->select('bc_transations.*');		
	
		if(isset($Custom_Eth_Address) && !empty($Custom_Eth_Address))
		{
			$query->where(function($query) use ($Custom_Eth_Address)
			{
				$query->where('bc_transations.from_address', '=', "$Custom_Eth_Address")
				->orWhere('bc_transations.to_address', '=', "$Custom_Eth_Address");
			});
		}
			
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];
			$query->where(function($query) use ($search_text)
			{
				$query->where('bc_transations.txid', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.amount', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.from_address', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.to_address', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.fees', 'LIKE', "%$search_text%");
			});
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date']))
		{
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			
			$query->where('bc_transations.time_stamp', '>=', $start_date);
			$query->where('bc_transations.time_stamp', '<=', $end_date);
		}
	
		if(isset($postarray['start']))
			$query->offset($postarray['start']);
		
		
		if(isset($postarray['length']))
			$query->limit($postarray['length']);
		
		if(isset($postarray['order'])) 
		{
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc')
			{
				$direction = 'desc';
			}
			else if($postarray['order']['0']['dir'] == 'desc')
			{
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else
		{
			$query->orderBy('bc_transations.time_stamp', 'desc');
		}
		
		//echo  $result = $query->toSql();die;
		
		if($is_count == 1)
		{
			return $query->count();
		}
		else
		{
			return $query->get();
		}
	}
	
	public static function get_datatables_join_count_blockchain($column_order, $postarray,  $Custom_Eth_Address='')
	{
		$query = Blockchain::query();
		$query->select
		(
			'bc_transations.id'
		);
		
		if(isset($Custom_Eth_Address) && !empty($Custom_Eth_Address))
		{
			$query->where(function($query) use ($Custom_Eth_Address)
			{
				$query->where('bc_transations.from_address', '=', "$Custom_Eth_Address")
				->orWhere('bc_transations.to_address', '=', "$Custom_Eth_Address");
			});
		}
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];
			
			$query->where(function($query) use ($search_text)
			{
				$query->where('bc_transations.txid', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.amount', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.from_address', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.to_address', 'LIKE', "%$search_text%")
				->orWhere('bc_transations.fees', 'LIKE', "%$search_text%");
			});
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date']))
		{
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			
			$query->where('bc_transations.time_stamp', '>=', $start_date);
			$query->where('bc_transations.time_stamp', '<=', $end_date);
		}
	
		//echo $query->count();die;		
		return $query->count();
		
	}
	
	public static function get_address_transaction_total_count($Custom_Eth_Address='')
	{
		$query = Blockchain::query();
		
		$query->select
		(
			'bc_transations.id'
		);
		
		if(isset($Custom_Eth_Address) && !empty($Custom_Eth_Address))
		{
			$query->where(function($query) use ($Custom_Eth_Address)
			{
				$query->where('bc_transations.from_address', '=', "$Custom_Eth_Address")
				->orWhere('bc_transations.to_address', '=', "$Custom_Eth_Address");
			});
		}
		//echo $query->count();die;		
		return $query->count();
		
	}
	
	
	

}
