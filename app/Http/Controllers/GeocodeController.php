<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\NotOk;
use GuzzleHttp\Client;
use App\Models\GridMap;
use App\Models\Latlong;
use App\Models\Strange;
use App\Models\District;
use App\Models\FailCity;
use App\Models\FailFull;
use App\Models\FailProv;
use App\Models\Province;
use App\Models\FailLatlong;
use App\Models\Subdistrict;
use App\Models\FailDistrict;
use Illuminate\Http\Request;
use App\Models\FailSubdistrict;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\EachPromise;
use Illuminate\Support\Facades\Http;

class GeocodeController extends Controller
{
    public function getData(Request $request)
    {
        $insertCounts = [
            'provinces' => 0,
            'cities' => 0,
            'districts' => 0,
            'subdistricts' => 0,
            'gridMaps' => 0,
            'already' => 0,
            'statusNotOk' => 0,
            'failGetProv' => 0,
            'failGetKeyProv' => 0,
            'failGetCity' => 0,
            'failGetKeyCity' => 0,
            'failGetDistrict' => 0,
            'failGetKeyDistrict' => 0,
            'failGetSubdistrict' => 0,
            'failGetKeySubdistrict' => 0,
            'failLatlong' => 0,
            'failFull' => 0,
        ];
        
        $promises = [];
        
        for ($i = 1; $i <= 14957; $i++) {
            $promises[$i] = $this->getDistrictSubdistrictAsync($i, $insertCounts);
        }
    
        // Wait for all promises to complete
        $results = (new EachPromise($promises))->promise()->wait();
    
        return response()->json([
            'message' => 'Success',
            'insertCounts' => $insertCounts, // Include the insertion counts in the response
        ]);
    }

    public function getDistrictSubdistrictAsync($i, &$insertCounts)
    {
        $promise = new Promise(function () use ($i, &$promise, &$insertCounts) {
            $result = $this->getDistrictSubdistrict($i, $insertCounts);
            $promise->resolve($result);
        });
    
        return $promise;
    }    

    public function getDistrictSubdistrict($i, &$insertCounts){
    // public function getDistrictSubdistrict(){
        $ls = Latlong::where('id', $i)->first();
        $latitude = $ls['lat'];
        $longitude = $ls['long'];
        $checkLatLong = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude]])->first();
        if(!$checkLatLong){
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            $client = new Client();
            $response = $client->get("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&language=id&key=$apiKey");

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK') {
                $addressComponents = $data['results'][0]['address_components'];
                $prov = null;
                $city = null;
                $district = null;
                $subdistrict = null;

                foreach ($addressComponents as $component) {
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $prov = strtoupper($component['long_name']);
                    }
                    if (in_array('administrative_area_level_2', $component['types'])) {
                        $city = strtoupper($component['long_name']);
                    }
                    if (in_array('administrative_area_level_3', $component['types'])) {
                        $district = strtoupper($component['long_name']);
                    }
                    if (in_array('administrative_area_level_4', $component['types'])) {
                        $subdistrict = strtoupper($component['long_name']);
                    }
                }

                $responseProv = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=10&lv2=11');
                
                if ($responseProv->successful()) {
                    $data = $responseProv->json(); // Convert the response to JSON
                    $reversedData = array_flip($data);
                    if($prov == "DAERAH ISTIMEWA YOGYAKARTA"){
                        $foundKeyProv = 34;
                    } else if($prov == "DAERAH KHUSUS IBUKOTA JAKARTA"){
                        $foundKeyProv = 31;
                    } else {
                        $searchValue = $prov; // The value you want to search for
                        $foundKeyProv = $reversedData[$searchValue] ?? null; // Using null coalescing operator
                    }
                    
                    if ($foundKeyProv !== null) {
                            $responseCity = retry(3, function () use ($foundKeyProv) {
                                return Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=11&pro=$foundKeyProv&lv2=12");
                            }, 1000);                    
                            if ($responseCity->successful()) {
                                $dataCity = $responseCity->json();
                                $reversedDataCity = array_flip($dataCity);
                                $searchValueCity = $city; // The value you want to search for
                                $foundKeyCityOri = $reversedDataCity[$searchValueCity] ?? null;
                                if ($foundKeyCityOri !== null) {
                                    $foundKeyCity = substr($foundKeyCityOri, -2);
                                    $responseDistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=12&pro=$foundKeyProv&kab=$foundKeyCity&lv2=13");
                                    if ($responseDistrict->successful()) {
                                        $dataDistrict = $responseDistrict->json();
                                        $reversedDataDistrict = array_flip($dataDistrict);
                                        if (strpos($district, "KECAMATAN") !== false) {
                                            $districtN = str_replace("KECAMATAN", "", $district);
                                            $districtN = trim($districtN);
                                        } else {
                                            $districtN = $district;
                                        }
                                        $searchValueDistrict = $districtN; // The value you want to search for
                                        $foundKeyDistrictOri = $reversedDataDistrict[$searchValueDistrict] ?? null;
                                        if ($foundKeyDistrictOri !== null) {
                                            $foundKeyDistrict = substr($foundKeyDistrictOri, -3);
                                            $responseSubdistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=13&pro=$foundKeyProv&kab=$foundKeyCity&kec=$foundKeyDistrict&lv2=14");
                                            if ($responseSubdistrict->successful()) {
                                                $dataSubdistrict = $responseSubdistrict->json();
                                                $reversedDataSubdistrict = array_flip($dataSubdistrict);
                                                $searchValueSubdistrict = $subdistrict; // The value you want to search for
                                                $foundKeySubdistrict = $reversedDataSubdistrict[$searchValueSubdistrict] ?? null;
                                                if ($foundKeySubdistrict !== null) {
                                                    // $insertCounts['aneh']++;
                                                    $checkProv = Province::where('ID', $foundKeyProv)->first();
                                                    $checkCity = City::where('ID', $foundKeyCityOri)->first();
                                                    $checkDistrict = District::where('ID', $foundKeyDistrictOri)->first();
                                                    $checkSubdistrict = Subdistrict::where('ID', $foundKeySubdistrict)->first();
                                                    $checkGridMap = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude]])->first();
                                                    $insertCounts['aneh']++;
                                                    if($checkProv && $checkCity && $checkDistrict && $checkSubdistrict && $checkGridMap){

                                                    }
                                                    if(!$checkProv){
                                                        Province::create([
                                                            'ID' => $foundKeyProv,
                                                            'PROVINCE_NAME' => $prov,
                                                            'REMARK' => 'FROM GRID POINT',
                                                        ]);
                                                        $insertCounts['provinces']++;
                                                    }
                                                    if(!$checkCity){
                                                        City::create([
                                                            'ID' => $foundKeyCityOri,
                                                            'CITY_NAME' => $city,
                                                            'REMARK' => 'FROM GRID POINT',
                                                            'LONGITUDE' => $longitude,
                                                            'LATITUDE' => $latitude,
                                                            'PROVINCE' => $prov,
                                                            'CPR_MST_PROVINCE_ID' => $foundKeyProv,
                                                        ]);
                                                        $insertCounts['cities']++;
                                                    }
                                                    if(!$checkDistrict){
                                                        District::create([
                                                            'ID' => $foundKeyDistrictOri,
                                                            'DISTRICT_NAME' => $district,
                                                            'REMARK' => 'FROM GRID POINT',
                                                            'CITY' => $city,
                                                            'CPR_MST_CITY_ID' => $foundKeyCityOri,
                                                            'CPR_MST_CITY_ID_BACKUP' => $foundKeyCityOri,
                                                        ]);
                                                        $insertCounts['districts']++;
                                                    }
                                                    if(!$checkSubdistrict){
                                                        Subdistrict::create([
                                                            'ID' => $foundKeySubdistrict,
                                                            'SUBDISTRICT_NAME' => $subdistrict,
                                                            'REMARK' => 'FROM GRID POINT',
                                                            'DISTRICT_NAME' => $district,
                                                            'CPR_MST_DISTRICT_ID' => $foundKeyDistrictOri,
                                                        ]);
                                                        $insertCounts['subdistricts']++;
                                                    }
                                                    if(!$checkGridMap){
                                                        GridMap::create([
                                                            'LONGITUDE' => $longitude,
                                                            'LATITUDE' => $latitude,
                                                            'REMARK' => 'FROM GRID POINT',
                                                            'CPR_MST_DISTRICT_ID' => $foundKeyDistrictOri,
                                                            'CPR_MST_SUBDISTRICT_ID' => $foundKeySubdistrict,
                                                            'SUBDISTRICT' => $subdistrict,
                                                        ]);
                                                        $insertCounts['gridMaps']++;
                                                    }
                                                    return [
                                                        'success' => true,
                                                        'insertCounts' => $insertCounts, // Return the updated insertion counts
                                                    ];
                                                }else {
                                                    $checkFailSubdistrict = FailSubdistrict::where([['name', $subdistrict], ['id_district', $foundKeyDistrictOri]])->first();
                                                    $checkFailLatlong = FailLatLong::where([['lat', $latitude], ['long', $longitude]])->first();
                                                    if(!$checkFailSubdistrict){
                                                        FailSubdistrict::create([
                                                            'name' => $subdistrict,
                                                            'lat' => $latitude,
                                                            'long' => $longitude,
                                                            'id_prov' => $foundKeyProv,
                                                            'prov' => $prov,
                                                            'id_city' => $foundKeyCityOri,
                                                            'city' => $city,
                                                            'id_district' => $foundKeyDistrictOri,
                                                            'district' => $district
                                                        ]);
                                                        $insertCounts['failGetKeySubdistrict']++;
                                                    }
                                                    if(!$checkFailLatlong){
                                                        FailLatLong::create([
                                                            'lat' => $latitude,
                                                            'long' => $longitude
                                                        ]);
                                                        $insertCounts['failLatlong']++;
                                                    }
                                                    $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                                    if(!cf){
                                                        FailFull::create([
                                                            'id' => $ls['id'],
                                                            'lat' => $latitude,
                                                            'long' => $longitude,
                                                            'subdistrict' => $subdistrict,
                                                            'district' => $district,
                                                            'city' => $city,
                                                            'prov' => $prov,
                                                            'description' => 'Fail in key subdistrict',
                                                        ]);
                                                        $insertCounts['failFull']++;
                                                    }
                                                    return [
                                                        'success' => true,
                                                        'insertCounts' => $insertCounts, // Return the updated insertion counts
                                                    ];
                                                }
                                            }else {
                                                $insertCounts['failGetSubdistrict']++;
                                                $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                                if(!$checkFailLatlong){
                                                    FailLatlong::create([
                                                        'lat' => $latitude,
                                                        'long' => $longitude
                                                    ]);
                                                    $insertCounts['failLatlong']++;
                                                }
                                                $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                                if(!cf){
                                                    FailFull::create([
                                                        'id' => $ls['id'],
                                                        'lat' => $latitude,
                                                        'long' => $longitude,
                                                        'subdistrict' => $subdistrict,
                                                        'district' => $district,
                                                        'city' => $city,
                                                        'prov' => $prov,
                                                        'description' => 'Fail in get subdistrict',
                                                    ]);
                                                    $insertCounts['failFull']++;
                                                }
                                                return [
                                                    'success' => true,
                                                    'insertCounts' => $insertCounts, // Return the updated insertion counts
                                                ];
                                            }
                                        }else {
                                            $checkFailDistrict = FailDistrict::where([['name', $district], ['id_city', $foundKeyCityOri]])->first();
                                            $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                            if(!$checkFailDistrict){
                                                FailDistrict::create([
                                                    'name' => $district,
                                                    'lat' => $latitude,
                                                    'long' => $longitude,
                                                    'id_prov' => $foundKeyProv,
                                                    'prov' => $prov,
                                                    'id_city' => $foundKeyCityOri,
                                                    'city' => $city,
                                                ]);
                                                $insertCounts['failGetKeyDistrict']++;
                                            }
                                            if(!$checkFailLatlong){
                                                FailLatlong::create([
                                                    'lat' => $latitude,
                                                    'long' => $longitude
                                                ]);
                                                $insertCounts['failLatlong']++;
                                            }
                                            $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                            if(!cf){
                                                FailFull::create([
                                                    'id' => $ls['id'],
                                                    'lat' => $latitude,
                                                    'long' => $longitude,
                                                    'subdistrict' => $subdistrict,
                                                    'district' => $district,
                                                    'city' => $city,
                                                    'prov' => $prov,
                                                    'description' => 'Fail in key district',
                                                ]);
                                                $insertCounts['failFull']++;
                                            }
                                            return [
                                                'success' => true,
                                                'insertCounts' => $insertCounts, // Return the updated insertion counts
                                            ];
                                        }
                                    }else {
                                        $insertCounts['failGetDistrict']++;
                                        $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                        if(!$checkFailLatlong){
                                            FailLatlong::create([
                                                'lat' => $latitude,
                                                'long' => $longitude
                                            ]);
                                            $insertCounts['failLatlong']++;
                                        }
                                        $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                        if(!cf){
                                            FailFull::create([
                                                'id' => $ls['id'],
                                                'lat' => $latitude,
                                                'long' => $longitude,
                                                'subdistrict' => $subdistrict,
                                                'district' => $district,
                                                'city' => $city,
                                                'prov' => $prov,
                                                'description' => 'Fail in get district',
                                            ]);
                                            $insertCounts['failFull']++;
                                        }
                                        return [
                                            'success' => true,
                                            'insertCounts' => $insertCounts, // Return the updated insertion counts
                                        ];
                                    }
                                }else {
                                    $checkFailCity = FailCity::where([['name', $city], ['id_prov', $foundKeyProv]])->first();
                                    $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                    if(!$checkFailCity){
                                        FailCity::create([
                                            'name' => $city,
                                            'lat' => $latitude,
                                            'long' => $longitude,
                                            'id_prov' => $foundKeyProv,
                                            'prov' => $prov,
                                        ]);
                                        $insertCounts['failGetKeyCity']++;
                                    }
                                    if(!$checkFailLatlong){
                                        FailLatlong::create([
                                            'lat' => $latitude,
                                            'long' => $longitude
                                        ]);
                                        $insertCounts['failLatlong']++;
                                    }
                                    $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                    if(!cf){
                                        FailFull::create([
                                            'id' => $ls['id'],
                                            'lat' => $latitude,
                                            'long' => $longitude,
                                            'subdistrict' => $subdistrict,
                                            'district' => $district,
                                            'city' => $city,
                                            'prov' => $prov,
                                            'description' => 'Fail in key city',
                                        ]);
                                        $insertCounts['failFull']++;
                                    }
                                    return [
                                        'success' => true,
                                        'insertCounts' => $insertCounts, // Return the updated insertion counts
                                    ];
                                }
                            }else {
                                $insertCounts['failGetCity']++;
                                $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                if(!$checkFailLatlong){
                                    FailLatlong::create([
                                        'lat' => $latitude,
                                        'long' => $longitude
                                    ]);
                                    $insertCounts['failLatlong']++;
                                }
                                $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                if(!cf){
                                    FailFull::create([
                                        'id' => $ls['id'],
                                        'lat' => $latitude,
                                        'long' => $longitude,
                                        'subdistrict' => $subdistrict,
                                        'district' => $district,
                                        'city' => $city,
                                        'prov' => $prov,
                                        'description' => 'Fail in get city',
                                    ]);
                                    $insertCounts['failFull']++;
                                }
                                return [
                                    'success' => true,
                                    'insertCounts' => $insertCounts, // Return the updated insertion counts
                                ];
                            }
                    }else {
                        $checkFailProv = FailProv::where('name', $prov)->first();
                        $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                        if(!$checkFailProv){
                            FailProv::create([
                                'name' => $prov,
                                'lat' => $latitude,
                                'long' => $longitude
                            ]);
                            $insertCounts['failGetKeyProv']++;
                        }
                            if(!$checkFailLatlong){
                                FailLatlong::create([
                                    'lat' => $latitude,
                                    'long' => $longitude
                                ]);
                                $insertCounts['failLatlong']++;
                            }
                            $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                            if(!cf){
                                FailFull::create([
                                    'id' => $ls['id'],
                                    'lat' => $latitude,
                                    'long' => $longitude,
                                    'subdistrict' => $subdistrict,
                                    'district' => $district,
                                    'city' => $city,
                                    'prov' => $prov,
                                    'description' => 'Fail in key prov',
                                ]);
                                $insertCounts['failFull']++;
                            }
                            return [
                                'success' => true,
                                'insertCounts' => $insertCounts, // Return the updated insertion counts
                            ];
                    }
                } else {
                    // return response()->json(['error' => 'Failed to fetch data'], 500);
                    $insertCounts['failGetProv']++;
                    $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                    if(!$checkFailLatlong){
                        FailLatlong::create([
                            'lat' => $latitude,
                            'long' => $longitude
                        ]);
                        $insertCounts['failLatlong']++;
                    }
                    $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                    if(!cf){
                        FailFull::create([
                            'id' => $ls['id'],
                            'lat' => $latitude,
                            'long' => $longitude,
                            'subdistrict' => $subdistrict,
                            'district' => $district,
                            'city' => $city,
                            'prov' => $prov,
                            'description' => 'Fail in get prov',
                        ]);
                        $insertCounts['failFull']++;
                    }
                    return [
                        'success' => true,
                        'insertCounts' => $insertCounts, // Return the updated insertion counts
                    ];
                }
            } else {
                // return response()->json(['message' => 'Location not found'], 404);
                $cno = NotOk::where([['lat', $latitude], ['long', $longitude]])->first();
                if(!$cno){
                    NotOk::create([
                        'lat' => $latitude,
                        'long' => $longitude
                    ]);
                    $insertCounts['statusNotOk']++;
                }
                $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                if(!$checkFailLatlong){
                    FailLatlong::create([
                        'lat' => $latitude,
                        'long' => $longitude
                    ]);
                    $insertCounts['failLatlong']++;
                }
                $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                if(!cf){
                    FailFull::create([
                        'id' => $ls['id'],
                        'lat' => $latitude,
                        'long' => $longitude,
                        'subdistrict' => $subdistrict,
                        'district' => $district,
                        'city' => $city,
                        'prov' => $prov,
                        'description' => 'Fail in status not ok',
                    ]);
                    $insertCounts['failFull']++;
                }
                return [
                    'success' => true,
                    'insertCounts' => $insertCounts, // Return the updated insertion counts
                ];
            }
        } else {
            $insertCounts['already']++;
            return [
                'success' => true,
                'insertCounts' => $insertCounts, // Return the updated insertion counts
            ];
        }
}

public function amount(){
    $latlong = Latlong::all();
    $province = Province::all();
    $city = City::all();
    $district = District::all();
    $subdistrict = Subdistrict::all();
    $gridmap = GridMap::all();
    $faillatlong = FailLatlong::all();
    $failfull = FailFull::all();
    $strange = Strange::all();
    $FailDistrict = FailDistrict::all();
    $FailSubdistrict = FailSubdistrict::all();
    $FailCity = FailCity::all();
    $FailProv = FailProv::all();

    return response()->json([
        'message' => 'Success',
        'latlong' => $latlong->count(),
        'province' => $province->count(),
        'city' => $city->count(),
        'district' => $district->count(),
        'subdistrict' => $subdistrict->count(),
        'gridmap' => $gridmap->count(),
        'faillatlong' => $faillatlong->count(),
        'failfull' => $failfull->count(),
        'strange' => $strange->count(),
        'FailSubdistrict' => $FailSubdistrict->count(),
        'FailSubdistrict' => $FailSubdistrict->count(),
        'FailCity' => $FailCity->count(),
        'FailCity' => $FailCity->count(),
        'FailProv' => $FailProv->count(),
        'FailDistrict' => $FailDistrict->count(),
    ]);
    
}

public function getAddress(Request $request){
    // public function getDistrictSubdistrict(){
        // $ls = Latlong::where('id', $i)->first();
        $latitude = $request->lat;
        $longitude = $request->long;
        // return $latitude;
        // $checkLatLong = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude]])->first();
        // if(!$checkLatLong){
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            $client = new Client();
            $response = $client->get("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&language=id&key=$apiKey");

            $data = json_decode($response->getBody(), true);
            // return $data;
            if ($data['status'] === 'OK') {
                $addressComponents = $data['results'][0]['address_components'];
                $prov = null;
                $city = null;
                $district = null;
                $subdistrict = null;

                foreach ($addressComponents as $component) {
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $prov = strtoupper($component['long_name']);
                    }
                    if (in_array('administrative_area_level_2', $component['types'])) {
                        $city = strtoupper($component['long_name']);
                    }
                    if (in_array('administrative_area_level_3', $component['types'])) {
                        $district = strtoupper($component['long_name']);
                    }
                    if (in_array('administrative_area_level_4', $component['types'])) {
                        $subdistrict = strtoupper($component['long_name']);
                    }
                }
                return response()->json([
                    'lat' => $latitude,
                    'long' => $longitude,
                    'prov' => $prov,
                    'city' => $city,
                    'district' => $district,
                    'subdistrict' => $subdistrict,
                    'all' => $data,
                ]);
            } else {
                return response()->json(['message' => 'Location not found'], 404);
            }
        // } else {
        //     return response()->json(['message' => 'Location not found1'], 404);
        // }
}

public function similarity(){
    $city2 = "";
    $city = "KABUPATEN ADMINISTRASI KEPULAUAN SERIBU";

    $responseCity = retry(3, function () {
                        return Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=11&pro=31&lv2=12");
                    }, 1000); 
    $dataCity = $responseCity->json();
    // No need to use json_decode again since $dataCity is already an array

    $data2 = array_values($dataCity);
    $cityNameLength = strlen($city); // Count length of the city name string

    return "City Name Length: " . $cityNameLength;
    

    // return count($numericIndicesArray);
    
    $levenshteinDistance = levenshtein($data2[5], $city);
    $maxStringLength = max(strlen($data2[5]), strlen($city));

    $similarityPercentage = ((1 - $levenshteinDistance / $maxStringLength) * 100);

    return "Similarity Percentage: " . round($similarityPercentage, 2) . "%";

}

public function csert(){
    $cek = NotOk::create([
        'lat' => "652",
        'long' => "826",
    ]);
    return $cek;
}

public function checkD(){
    $amountBN = 0;
    $amountG = 0;
    $amountF = 0;
    $amountS = 0;
    $new = 0;
    for($i = 1; $i <= 14957; $i++){
        $ls = Latlong::where('id', $i)->first();
        $latitude = $ls['lat'];
        $longitude = $ls['long'];
        $gm = GridMap::where([['LATITUDE', $latitude], ['LONGITUDE', $longitude]])->first();
        $ff = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
        
        if(!$gm && !$ff){
            $cs = Strange::where([['lat', $latitude], ['long', $longitude]])->first();
            if(!$cs){
                Strange::create([
                    'id' => $ls['id'],
                    'lat' => $latitude,
                    'long' => $longitude, 
                ]);
                $new++;
            }
            $amountBN++;
        } else if($gm && !$ff){
            $amountG++;
        } else if(!$gm && $ff){
            $amountF++;
        } else {
            $amountS++;
        }
    }
    return response()->json([
        'message' => 'success',
        'amountBN' => $amountBN,
        'new' => $new,
        'amountG' => $amountG,
        'amountF' => $amountF,
        'amountS' => $amountS,
    ]);
}

}
