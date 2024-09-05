<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class StoreStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_open',
        'days',
        'start_time',
        'end_time',
        'temporary_closure_duration',
        'force_close',

    ];
   
    public function getIsOpenAttribute()
    {
        $today = Carbon::now('Asia/Jakarta')->locale('id')->dayName; // Get the current day name in Indonesian
        $currentTime = Carbon::now('Asia/Jakarta')->format('H:i:s'); // Get the current time
        if ($this->force_close) {
            return false;
        }
    
        if ($this->temporary_closure_duration) {
            return false;
        }
        $startTime_C = strtotime($this->start_time);
        $endTime_C = strtotime($this->end_time);
        $currentTime_C = strtotime($currentTime);
        if (stripos($this->days, $today) !== false) {
            // Check if the current time is within the operating hours
            if ($currentTime >= $this->start_time && $currentTime <= $this->end_time) {
                return true;
            }
        }

        return false;
    }
}
