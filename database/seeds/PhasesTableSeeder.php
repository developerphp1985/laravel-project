<?php

use App\Models\Phases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class PhasesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Phases::where('phase_name', '=', 'Early Bird ICO')->first() === null) {
            Phases::create([
                'phase_name' => 'Early Bird ICO',
                'phase_start_date' => '2018-01-01',
                'phase_end_date' => '2018-01-31',
                'token_target' => '100000000',
                'phase_bonus' => '100',
                'status' => '1',
                'updated_by' => '1'
            ]);
        }

        if (Phases::where('phase_name', '=', 'Pre-ICO')->first() === null) {
            Phases::create([
                'phase_name' => 'Pre-ICO',
                'phase_start_date' => '2018-02-01',
                'phase_end_date' => '2018-02-28',
                'token_target' => '30000000',
                'phase_bonus' => '50',
                'status' => '0',
                'updated_by' => '1'
            ]);
        }

        if (Phases::where('phase_name', '=', 'Phase 1')->first() === null) {
            Phases::create([
                'phase_name' => 'Phase 1',
                'phase_start_date' => '2018-03-01',
                'phase_end_date' => '2018-03-31',
                'token_target' => '70000000',
                'phase_bonus' => '30',
                'status' => '0',
                'updated_by' => '1'
            ]);
        }

        if (Phases::where('phase_name', '=', 'Phase 2')->first() === null) {
            Phases::create([
                'phase_name' => 'Phase 2',
                'phase_start_date' => '2018-04-01',
                'phase_end_date' => '2018-04-30',
                'token_target' => '125000000',
                'phase_bonus' => '25',
                'status' => '0',
                'updated_by' => '1'
            ]);
        }

        if (Phases::where('phase_name', '=', 'Phase 3')->first() === null) {
            Phases::create([
                'phase_name' => 'Phase 3',
                'phase_start_date' => '2018-05-01',
                'phase_end_date' => '2018-05-31',
                'token_target' => '175000000',
                'phase_bonus' => '20',
                'status' => '0',
                'updated_by' => '1'
            ]);
        }

        if (Phases::where('phase_name', '=', 'Phase 4')->first() === null) {
            Phases::create([
                'phase_name' => 'Phase 4',
                'phase_start_date' => '2018-06-01',
                'phase_end_date' => '2018-06-30',
                'token_target' => '200000000',
                'phase_bonus' => '10',
                'status' => '0',
                'updated_by' => '1'
            ]);
        }
    }
}
