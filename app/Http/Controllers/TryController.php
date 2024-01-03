<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TryController extends Controller {

public function index(Request $request){
    $response = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=10&lv2=11');
    $prov = $request->input('prov');

    if ($response->successful()) {
        $data = $response->json(); // Convert the response to JSON
        // Handle and process the $data as needed
        // return response()->json($data); // Return the data as JSON response
        // return $data;

        // Reverse the key-value pairs of the JSON data
        $reversedData = array_flip($data);

            $searchValue = $prov; // The value you want to search for
            $foundKey = $reversedData[$searchValue] ?? null; // Using null coalescing operator
            
            if ($foundKey !== null) {
                return response()->json([$foundKey]);
            } else {
                return response()->json(['error' => 'Value not found'], 404);
            }
    } else {
        return response()->json(['error' => 'Failed to fetch data'], 500);
    }
}

}
