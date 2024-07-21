<?php

namespace App\Http\Controllers;

use App\Models\StoreStatus;
use Illuminate\Http\Request;

class StoreStatusController extends Controller
{
    public function index()
    {
        $storeStatuses = StoreStatus::all();

        return response()->json(['data' => $storeStatuses], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'is_open' => 'required|boolean',
            'days' => 'nullable|string',
            'hours' => 'nullable|string',
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
        $validatedData = $request->validate([
            'is_open' => 'required|boolean',
            'days' => 'nullable|string',
            'hours' => 'nullable|string',
            'temporary_closure_duration' => 'nullable|string',
        ]);

        $storeStatus = StoreStatus::find($id);

        if (is_null($storeStatus)) {
            return response()->json(['message' => 'Store status not found'], 404);
        }

        $storeStatus->update($validatedData);

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
