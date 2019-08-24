<?php
/**
 * Created by IntelliJ IDEA.
 * User: george
 * Date: 30/04/2018
 * Time: 05:20
 */

namespace App\Helpers;

use App\Models\Phases;


class PhaseHelper
{
    public static function getPhases()
    {
        return Phases::orderBy('phase_start_date', 'asc')->get();
    }
    
    public static function getCurrentPhase() {
        return Phases::where('status', '1')->first();
    }
}