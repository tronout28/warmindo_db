<?php

namespace App\Http\Controllers;

use App\Models\StoreStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\FirebaseService;

use Illuminate\Support\Facades\Log;
class StoreStatusController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function index()
    {
        $timezone = 'Asia/Jakarta'; 
        $today = Carbon::now($timezone)->locale('id')->dayName;
        $currentTime = Carbon::now($timezone)->format('H:i:s'); // Get the current time
    
        $storeStatuses = StoreStatus::all();
    
        foreach ($storeStatuses as $storeStatus) {
            Log::info('Current Store Status:', [
                'id' => $storeStatus->id,
                'is_open' => $storeStatus->is_open,
                'start_time' => $storeStatus->start_time,
                'end_time' => $storeStatus->end_time,
                'temporary_closure_duration' => $storeStatus->temporary_closure_duration,
                'current_time' => $currentTime
            ]);
    
            // Default to false
            $startTime_C = strtotime($storeStatus->start_time);
            $endTime_C = strtotime($storeStatus->end_time);
            $currentTime_C = strtotime($currentTime);
            // $storeStatus->is_open = false;
            Log::info('Current Store Status:', [
                'id' => $storeStatus->id,
                'is_open' => $storeStatus->is_open,
                'start_time' => $startTime_C,
                'end_time' => $endTime_C,
                'temporary_closure_duration' => $storeStatus->temporary_closure_duration,
                'current_time' => $currentTime_C
            ]);
            // Check if today is a valid day
            if ($storeStatus->days && stripos($storeStatus->days, $today) !== false) {
                // Check if within operating hours
                if ($currentTime_C >= $startTime_C && $currentTime_C <= $endTime_C) {
                    $storeStatus->is_open = false;
                    if ($storeStatus->temporary_closure_duration) {
                        $duration = (int) $storeStatus->temporary_closure_duration;
                        $closureEndTime = Carbon::parse($storeStatus->updated_at)->addMinutes($duration);
    
                        if (Carbon::now($timezone)->greaterThanOrEqualTo($closureEndTime)) {
                            // Reset the closure duration and open the store
                            $storeStatus->temporary_closure_duration = 0;
                            $storeStatus->is_open = true;
                               if($storeStatus->is_open == true){
                            $this->firebaseService->sendNotificationToAll('Toko Sudah Buka', 'Status Toko Sekarang sudah buka kamu bisa memesan makanan sekarang', '', []);
                        }
                            
                        }
                    } else {
                        // No temporary closure, set `is_open` to true if within hours
                        $storeStatus->is_open = true;
                        if($storeStatus->is_open == true){
                            $this->firebaseService->sendNotificationToAll('Toko Sudah Buka', 'Status Toko Sekarang sudah buka kamu bisa memesan makanan sekarang', '', []);
                        }
                        
                    }
                }else{
                    $storeStatus->is_open = false;
                    if($storeStatus->is_open == false){
                        $this->firebaseService->sendNotificationToAll('Toko Sudah Tutup', 'Status Toko Sekarang sudah tutup kamu tidak bisa memesan makanan sekarang', '', []);
                    }
                }
            }
    
            // Check if the store is still open based on time conditions
            if ($currentTime_C >= $startTime_C && $currentTime_C <= $endTime_C) {
                $storeStatus->is_open = false;
            }
    
            Log::info('Updated Store Status:', [
                'id' => $storeStatus->id,
                'is_open' => $storeStatus->is_open,
                'start_time' => $storeStatus->start_time,
                'end_time' => $storeStatus->end_time,
                'temporary_closure_duration' => $storeStatus->temporary_closure_duration,
                'current_time' => $currentTime
            ]);
    
            $storeStatus->save();
        }
    
        return response()->json(['data' => $storeStatuses], 200);
    }
    
    
    
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'is_open' => 'nullable|boolean',
            'days' => 'nullable|string',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'temporary_closure_duration' => 'nullable|string',
        ]);

        $storeStatus = StoreStatus::create($validatedData);

        return response()->json(['data' => $storeStatus], 201);
    }

    public function show($id)
    {
        $storeStatus = StoreStatus::find($id);

        if (is_null($storeStatus)) {
            return response()->json(['message' => 'Store status not found'], 404);
        }

        return response()->json(['data' => $storeStatus], 200);
    }

    public function update(Request $request, $id)
    {
        Log::info('Update Store Status Request: ', $request->all());

        $validatedData = $request->validate([
            'is_open' => 'nullable|boolean',
            'days' => 'nullable|string',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'temporary_closure_duration' => 'nullable|string',
        ]);

        $storeStatus = StoreStatus::find($id);

        if (is_null($storeStatus)) {
            return response()->json(['message' => 'Store status not found'], 404);
        }

        $storeStatus->update($validatedData);
        if ($storeStatus->is_open == false) {
            $this->firebaseService->sendNotificationToAll('Toko Sudah Tutup', 'Status Toko Sekarang sudah tutup kamu tidak bisa memesan makanan sekarang', '', []);
        }
        return response()->json(['data' => $storeStatus], 200);
    }

    public function destroy($id)
    {
        $storeStatus = StoreStatus::find($id);

        if (is_null($storeStatus)) {
            return response()->json(['message' => 'Store status not found'], 404);
        }

        $storeStatus->delete();

        return response()->json(['message' => 'Store status deleted'], 204);
    }
}
