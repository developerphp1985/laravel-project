<?php

use App\Models\Phases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class AddNewRowPhasesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        if (Phases::where('phase_name', '=', 'Early Bird ICO')->first() === null) {
            Phases::create([
                'phase_name' => 'Early Bird ICO',
                'phase_start_date' => '2018-01-01',
                'phase_end_date' => '2018-01-31',
                'token_target' => '100000000',
                'phase_bonus' => '100',
                'status' => '0',
                'updated_by' => '1'
            ]);
        }
    }
}
