<?php

namespace App\Http\Controllers;

use App\Models\City;
use GuzzleHttp\Client;
use App\Models\GridMap;
use App\Models\Latlong;
use App\Models\District;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodeController extends Controller
{
    public function getData(Request $request)
{
    for($i=2501; $i<=3500; $i++){
        $result = $this->getDistrictSubdistrict($i);
    }
    return response()->json([
        'message' => 'Success'
    ]);
}

public function getDistrictSubdistrict($i){
    $ls = Latlong::where('id', $i)->first();
    $latitude = $ls['lat'];
    $longitude = $ls['long'];

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
            $searchValue = $prov; // The value you want to search for
            $foundKeyProv = $reversedData[$searchValue] ?? null; // Using null coalescing operator
            
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
                                            $checkProv = Province::where('ID', $foundKeyProv)->first();
                                            $checkCity = City::where('ID', $foundKeyCityOri)->first();
                                            $checkDistrict = District::where('ID', $foundKeyDistrictOri)->first();
                                            $checkSubdistrict = Subdistrict::where('ID', $foundKeySubdistrict)->first();
                                            $checkGridMap = GridMap::where('SUBDISTRICT', $subdistrict)->first();
                                            if(!$checkProv){
                                                Province::create([
                                                    'ID' => $foundKeyProv,
                                                    'PROVINCE_NAME' => $prov,
                                                    'REMARK' => 'FROM GRID POINT',
                                                ]);
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
                                            }
                                            if(!$checkSubdistrict){
                                                Subdistrict::create([
                                                    'ID' => $foundKeySubdistrict,
                                                    'SUBDISTRICT_NAME' => $subdistrict,
                                                    'REMARK' => 'FROM GRID POINT',
                                                    'DISTRICT_NAME' => $district,
                                                    'CPR_MST_DISTRICT_ID' => $foundKeyDistrictOri,
                                                ]);
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
                                            }
                                            return response()->json([
                                                'message' => 'Success'
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }else {
                    return response()->json([
                        'error' => 'Value not found',
                        'lat' => $latitude,
                        'long' => $longitude,
                        'prov' => $prov,
                        'city' => $city,
                        'district' => $district,
                        'subdistrict' => $subdistrict,
                        'iterasi' => $i,
                        'all' => $addressComponents,
                    ], 404);
                }
        } else {
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    } else {
        return response()->json(['message' => 'Location not found'], 404);
    }
}

}
