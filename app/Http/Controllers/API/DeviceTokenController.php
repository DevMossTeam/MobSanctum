<?php

namespace App\Http\Controllers\API;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DeviceTokenController extends Controller
{
    public function store(Request $req)
    {
        $data = $req->validate([
            'device_token' => 'required|string|unique:device_tokens,device_token',
            'device_type' => 'required|in:android,ios',
        ]);
        return DeviceToken::create($data);
    }

    public function index()
    {
        return DeviceToken::all();
    }

    public function destroy($id)
    {
        $token = DeviceToken::find($id);
        if (!$token) return response()->json(['message' => 'Not Found'], 404);
        $token->delete();
        return response()->noContent();
    }
}
