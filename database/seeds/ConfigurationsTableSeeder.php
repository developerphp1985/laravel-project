<?php

use App\Models\Configurations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ConfigurationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	/***************************************************************************************/

    	if (Configurations::where('name', '=', 'Conversion-BTC-EUR')->first() === null) {
    		Configurations::create([
	            'name'         	=> 'Conversion-BTC-EUR',
	            'valid_from'    => '2017-11-01 00:00:00',
	            'valid_to'      => '9999-12-31',
	            'defined_value'	=> '12000',
	            'defined_unit'	=> 'EUR',
	            'updated_by'	=> '1'
		   	]);
		}

		if (Configurations::where('name', '=', 'Conversion-ETH-EUR')->first() === null) {
		   	Configurations::create([
	        	'name'         	=> 'Conversion-ETH-EUR',
				'valid_from'    => '2017-11-01 00:00:00',
				'valid_to'      => '9999-12-31',
				'defined_value'	=> '250',
				'defined_unit'	=> 'EUR',
				'updated_by'	=> '1'
		    ]);
		}

		if (Configurations::where('name', '=', 'Conversion-EUR-ELT')->first() === null) {
			Configurations::create([
	        	'name'         	=> 'Conversion-EUR-ELT',
				'valid_from'    => '2017-11-01 00:00:00',
				'valid_to'      => '9999-12-31',
				'defined_value'	=> '0.20',
				'defined_unit'	=> 'ETL',
				'updated_by'	=> '1'
		    ]);
		}

		if (Configurations::where('name', '=', 'Conversion-BTC-ELT')->first() === null) {
			Configurations::create([
				'name'			=> 'Conversion-BTC-ELT',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '55556',
				'defined_unit'	=> 'ELT',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Conversion-ETH-ELT')->first() === null) {
			Configurations::create([
				'name'			=> 'Conversion-ETH-ELT',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '20',
				'defined_unit'	=> 'ELT',
				'updated_by'	=> '1'
			]);
		}

		/***************************************************************************************/

		if (Configurations::where('name', '=', 'Minimum-Withdraw-EUR')->first() === null) {
			Configurations::create([
	        	'name'         	=> 'Minimum-Withdraw-EUR',
				'valid_from'    => '2017-11-01 00:00:00',
				'valid_to'      => '9999-12-31',
				'defined_value'	=> '100',
				'defined_unit'	=> 'EUR',
				'updated_by'	=> '1'
		    ]);
		}

		if (Configurations::where('name', '=', 'Minimum-Withdraw-ETH')->first() === null) {
		    Configurations::create([
	        	'name'			=> 'Minimum-Withdraw-ETH',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '1',
				'defined_unit'	=> 'ETH',
				'updated_by'	=> '1'
	        ]);
	    }

	    if (Configurations::where('name', '=', 'Minimum-Withdraw-BTC')->first() === null) {
	        Configurations::create([
				'name'			=> 'Minimum-Withdraw-BTC',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '0.1',
				'defined_unit'	=> 'BTC',
				'updated_by'	=> '1'
		    ]);
		}

		if (Configurations::where('name', '=', 'Minimum-Withdraw-ELT')->first() === null) {
	        Configurations::create([
				'name'			=> 'Minimum-Withdraw-ELT',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '1',
				'defined_unit'	=> 'ELT',
				'updated_by'	=> '1'
		    ]);
		}

		/***************************************************************************************/

		if (Configurations::where('name', '=', 'Minimum-Buy-BTC')->first() === null) {
		    Configurations::create([
				'name'			=> 'Minimum-Buy-BTC',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '0.000018',
				'defined_unit'	=> 'BTC',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Minimum-Buy-ETH')->first() === null) {
			Configurations::create([
				'name'			=> 'Minimum-Buy-ETH',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '0.5',
				'defined_unit'	=> 'ETH',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Minimum-Buy-EUR')->first() === null) {
			Configurations::create([
				'name'			=> 'Minimum-Buy-EUR',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '100',
				'defined_unit'	=> 'EUR',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Minimum-Buy-ELT')->first() === null) {
		    Configurations::create([
				'name'			=> 'Minimum-Buy-ELT',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '1',
				'defined_unit'	=> 'ELT',
				'updated_by'	=> '1'
			]);
		}

		/***************************************************************************************/

		if (Configurations::where('name', '=', 'Maximum-Buy-BTC')->first() === null) {
		    Configurations::create([
				'name'			=> 'Maximum-Buy-BTC',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '100',
				'defined_unit'	=> 'BTC',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Maximum-Buy-ELT')->first() === null) {
		    Configurations::create([
				'name'			=> 'Maximum-Buy-ELT',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '10000',
				'defined_unit'	=> 'ELT',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Maximum-Buy-ETH')->first() === null) {
		    Configurations::create([
				'name'			=> 'Maximum-Buy-ETH',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '300',
				'defined_unit'	=> 'ETH',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Maximum-Buy-EUR')->first() === null) {
		    Configurations::create([
				'name'			=> 'Maximum-Buy-EUR',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '10000',
				'defined_unit'	=> 'EUR',
				'updated_by'	=> '1'
			]);
		}

		/***************************************************************************************/

		if (Configurations::where('name', '=', 'Referral-%-Level-1')->first() === null) {
			Configurations::create([
				'name'			=> 'Referral-%-Level-1',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '10',
				'defined_unit'	=> '%',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Referral-%-Level-2')->first() === null) {
			Configurations::create([
				'name'			=> 'Referral-%-Level-2',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '6',
				'defined_unit'	=> '%',
				'updated_by'	=> '1'
			]);
		}

		if (Configurations::where('name', '=', 'Referral-%-Level-3')->first() === null) {
			Configurations::create([
				'name'			=> 'Referral-%-Level-3',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '3',
				'defined_unit'	=> '%',
				'updated_by'	=> '1'
			]);
		}

		/***************************************************************************************/

		if (Configurations::where('name', '=', 'Session-Timeout')->first() === null) {
			Configurations::create([
				'name'			=> 'Session-Timeout',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '1800',
				'defined_unit'	=> 's',
				'updated_by'	=> '1'
			]);
		}

		/***************************************************************************************/

		if (Configurations::where('name', '=', 'Default-referrer-user-id')->first() === null) {
			Configurations::create([
				'name'			=> 'Default-referrer-user-id',
				'valid_from'	=> '2017-11-01 00:00:00',
				'valid_to'		=> '9999-12-31',
				'defined_value'	=> '1',
				'defined_unit'	=> 'id',
				'updated_by'	=> '1'
			]);
		}

		/***************************************************************************************/
    }
}
