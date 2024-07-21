<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoryController extends Controller
{
    public function index()
    {
        $history = History::with('order')->get();

        return response()->json([
            'success' => true,
            'message' => 'List of history',
            'data' => $history
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'status' => 'required|string',
            'change_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $history = History::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'History created successfully',
            'data' => $history,
        ], 201);
    }

    public function show($id)
    {
        $history = History::with('order')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'History retrieved successfully',
            'data' => $history,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'status' => 'required|string',
            'change_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $history = History::findOrFail($id);
        $history->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'History updated successfully',
            'data' => $history,
        ], 200);
    }

    public function destroy($id)
    {
        $history = History::findOrFail($id);
        $history->delete();

        return response()->json([
            'success' => true,
            'message' => 'History deleted successfully'
        ], 200);
    }
}
