<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;


class LendoCards extends Model

{

    //

    protected $table = 'lendo_cards';

    //

    public $timestamps = false;



    public static function getCardData() { 
	      $card_data_array = array();
          $card_data = LendoCards::where('is_active','1')->get();
		  if(!empty($card_data))
		  {
			  $card_data_array = $card_data;
			  
		  }
        
         return $card_data_array;
    }

}

