<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AssetController extends Controller
{
    public function getAllAssets(Request $request){

        $endpoint = env('ASSET_URL') . '/api/assets' .'?search=' . $request->search;

        $response = Http::get($endpoint)->json();

        return response()->json([
            'success' => true,
            'message' => 'success get active asset',
            'data' => $response['data'] ?? null,
        ]);
    }

    public function getDetailAssets($assetId){

    }
}
