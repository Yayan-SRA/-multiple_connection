<?php

namespace App\Http\Controllers;

use App\Models\Aneh;
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

class GeocodesController extends Controller
{
    public function getData(Request $request)
    {
        $insertCounts = [
            'provinces' => 0,
            'cities' => 0,
            'districts' => 0,
            'subdistricts' => 0,
            'gridMaps' => 0,
            'gridMapInDistrict' => 0,
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
            'failStrange' => 0,
            'strange' => 0,
            'solveStrange' => 0,
            'aneh' => 0,
            'delFailCity' => 0,
            'delFailDistrict' => 0,
            'delFailSubdistrict' => 0,
            'delFailLatlong' => 0,
            'delFailFull' => 0,
            'updateFailFull' => 0,
            'idNotFound' => 0,
            'districtTypoFixed' => 0,
            'failDistrictTypoFixed' => 0,
            'subdistrictTypoFixed' => 0,
            'failSubdistrictTypoFixed' => 0,
            'result1' => 0,
            'reason' => 0,
            'alreadyDistrict' => 0,
            'generateNewIdDistrict' => 0,
            'generateNewIdSubdistrict' => 0,
        ];
        
        $promises = [];
        
        for ($i = 2242; $i <= 2242; $i++) {
        // for ($i = 1; $i <= 1; $i++) {
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
        $ls = FailLatlong::where('id', $i)->first();
        if($ls){

        $latitude = $ls['lat'];
        $longitude = $ls['long'];
        // $ls = Strange::all();
        // $arr = json_decode($ls, true);
        // $lsReady = array_values($arr);
        // $ls = Latlong::where('id', $i)->first();
        // return Strange::destroy(1190);
        // return $lsReady[0];
        // $latitude = '-7.15';
        // $longitude = '138.95';
        // $latitude = $lsReady[$i]['lat'];
        // $longitude = $lsReady[$i]['long'];
        $checkLatLong = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude], ['CPR_MST_DISTRICT_ID', '!=', null], ['CPR_MST_SUBDISTRICT_ID', '!=', null], ['SUBDISTRICT', '!=', null]])->first();
        if(!$checkLatLong){
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            $client = new Client();
            $response = $client->get("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&language=id&key=$apiKey");

            $data = json_decode($response->getBody(), true);
            
            if ($data['status'] === 'OK') {
                $prov = null;
                $city = null;
                $district = null;
                $subdistrict = null;
                $tes = 0;
                for ($p = 0; $p < count( $data['results']); $p++){
                    $addressComponents = $data['results'][$p]['address_components'];
                    // return $addressComponents;
                    if(count($addressComponents) > 4){
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
                        if($prov && $city && $district && $subdistrict){
                            // return ([
                            //         'prov' => $prov,
                            //         'city' => $city,
                            //         'district' => $district,
                            //         'subdistrict' => $subdistrict,
                            //         'latitude' => $latitude,
                            //         'longitude' => $longitude,
                            //         'iterasi' => $p,
                            //     ]);
                            $tes = $p;
                            // return $tes;
                            // continue;
                            break;
                        }
                    }
                }
                // return ([
                //         'prov' => $prov,
                //         'city' => $city,
                //         'district' => $district,
                //         'subdistrict' => $subdistrict,
                //         'latitude' => $latitude,
                //         'longitude' => $longitude,
                //         'iterasi' => $tes,
                //     ]);
                if(!$prov || !$city || !$district || !$subdistrict){
                    // return 'oi';
                    // nanti masuk ke table strange
                    $cs = Strange::where([['lat', $latitude], ['long', $longitude]])->first();
                    if(!$cs){
                        Strange::create([
                            'lat' => $latitude,
                            'long' => $longitude, 
                        ]);
                        $insertCounts['strange']++;
                    } else{
                        $insertCounts['failStrange']++;
                    }
                    // return 'yahh';
                } else {
                // return $prov;
                    if($prov == 'SOUTH SUMATRA'){
                        $prov = 'SUMATERA SELATAN';
                    }
                    if(strpos($city, "REGENCY") !== false){
                        $city = str_replace("REGENCY", "", $city);
                        $city = trim($city);
                        $city = 'KABUPATEN ' . $city;
                    } else if(strpos($city, "KABUPATéN") !== false){
                        $city = str_replace("KABUPATéN", "", $city);
                        $city = trim($city);
                        $city = 'KABUPATEN ' . $city;
                    } 

                    if($city == 'KUTAI BARAT' || $city == 'MERAUKE'){
                        $city1 = 'KABUPATEN ' . $city;         
                        $checkFailDistrict = $getFailDistrict = FailDistrict::where([['name', $district],['city', $city1],['prov', $prov]])->first();
                    } else {          
                        $checkFailDistrict = $getFailDistrict = FailDistrict::where([['name', $district],['city', $city],['prov', $prov]])->first();
                    }
                    if($checkFailDistrict){
                        // return 'masuk sini';
                        $reason = $checkFailDistrict->reason;
                        if($reason == 'result1'){
                            $addressComponents = $data['results'][1]['address_components'];
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
                            $insertCounts['result1']++;
                            $checkFailDistrict->update([
                                'status' => 'done'
                            ]);
                            // return ([
                            //     'prov' => $prov,
                            //     'city' => $city,
                            //     'district' => $district,
                            //     'subdistrict' => $subdistrict,
                            //     'latitude' => $latitude,
                            //     'longitude' => $longitude,
                            //     'iterasi' => $tes,
                            // ]);
                        }
                        // return 'gk masuk';
                        // else if(strpos($reason, "Kabupaten")  !== false || strpos($reason, "Kota")  !== false){
                        //     $city = strtoupper($reason);
                        //     $insertCounts['reason']++;
                        // }
                    }
                    // return 'udh';
                    $responseProv = Province::where('PROVINCE_NAME', $prov)->first();
                    // return $responseProv;
                    if ($responseProv) {
                        $foundKeyProv = $responseProv->ID;
                        if ($foundKeyProv !== null) {
                            $checkProv = Province::where('ID', $foundKeyProv)->first();
                            if(!$checkProv){
                                Province::create([
                                    'ID' => $foundKeyProv,
                                    'PROVINCE_NAME' => $prov,
                                    'REMARK' => 'FROM GRID POINT',
                                ]);
                                $insertCounts['provinces']++;
                            }
                            // if(strpos($city, "REGENCY") !== false){
                            //     $city = str_replace("REGENCY", "", $city);
                            //     $city = trim($city);
                            //     $city = 'KABUPATEN ' . $city;
                            // } else if(strpos($city, "KABUPATéN") !== false){
                            //     $city = str_replace("KABUPATéN", "", $city);
                            //     $city = trim($city);
                            //     $city = 'KABUPATEN ' . $city;
                            // } else if($city == 'KUTAI BARAT' || $city == 'MERAUKE'){
                            //     $city = 'KABUPATEN ' . $city;
                            // }
                            if($city == 'KUTAI BARAT' || $city == 'MERAUKE'){
                                $city1 = 'KABUPATEN ' . $city;
                                $responseCity = City::where('CITY_NAME', $city1)->first();           
                            } else {
                                $responseCity = City::where('CITY_NAME', $city)->first();           
                            } 
                            
                            if ($responseCity) {
                                $foundKeyCityOri = $responseCity->ID;
                                if ($foundKeyCityOri !== null) {
                                    $checkCity = City::where('ID', $foundKeyCityOri)->first();
                                    if(!$checkCity){
                                        $insertCity = City::create([
                                                        'ID' => $foundKeyCityOri,
                                                        'CITY_NAME' => $city,
                                                        'REMARK' => 'FROM GRID POINT',
                                                        'LONGITUDE' => $longitude,
                                                        'LATITUDE' => $latitude,
                                                        'PROVINCE' => $prov,
                                                        'CPR_MST_PROVINCE_ID' => $foundKeyProv,
                                                        'DESCRIPTION' => 'NEW5',
                                                    ]);
                                        if($insertCity) {
                                            $insertCounts['cities']++;
                                            $ckFailCity = FailCity::where([['name', $city], ['id_prov', $foundKeyProv]])->first();
                                            if($ckFailCity){
                                                $delCity = $ckFailCity->delete();
                                                if($delCity){
                                                    $insertCounts['delFailCity']++;
                                                }
                                            }
                                        }
                                    }
                                    // return 'masuk';
                                    $foundKeyCity = substr($foundKeyCityOri, -2);
                                    $responseDistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=12&pro=$foundKeyProv&kab=$foundKeyCity&lv2=13");
                                    if ($responseDistrict->successful()) {
                                        $dataDistrict = $responseDistrict->json();
                                        // if($district == 'BANGKINANG BARAT'){
                                        //     $district = 'KECAMATAN KUOK';
                                        // }
                                        // return $dataDistrict;
                                        $reversedDataDistrict = array_flip($dataDistrict);
                                        if (strpos($district, "KECAMATAN") !== false) {
                                            $districtN = str_replace("KECAMATAN", "", $district);
                                            $districtN = trim($districtN);
                                        } else if(strpos($district, "DISTRIK") !== false){
                                            $districtN = str_replace("DISTRIK", "", $district);
                                            $districtN = trim($districtN);
                                        } else {
                                            $districtN = $district;
                                        }
                                        // return response()->json([
                                        //     $district,
                                        //     $districtN,
                                        // ]); 
                                        $searchValueDistrict = $districtN; // The value you want to search for
                                        $foundKeyDistrictOri = $reversedDataDistrict[$searchValueDistrict] ?? null;
                                        if ($foundKeyDistrictOri == null) {
                                            $minDistance = PHP_INT_MAX;
                                            $closestMatchDistrict = "";
                                        
                                            foreach ($dataDistrict as $key => $value) {
                                                $distance = levenshtein($districtN, $value);
                                                if ($distance < $minDistance) {
                                                    $minDistance = $distance;
                                                    $closestMatchDistrict = $value;
                                                }
                                            }
                                        
                                            if ($minDistance <= 2) {
                                                $foundKeyDistrictOri = $reversedDataDistrict[$closestMatchDistrict] ?? null;
                                                $insertCounts['districtTypoFixed']++;
                                            } else {
                                                $foundKeyDistrictOri = null;
                                                $insertCounts['failDistrictTypoFixed']++;
                                            }
                                        }
                                        // return 'masuk ges';
                                        if($foundKeyDistrictOri == null){
                                            // return [
                                            //     $district, 
                                            //     $foundKeyCityOri
                                            // ];
                                            $checkFailDistrict = $getFailDistrict = FailDistrict::where([['name', $district],['city', $city],['prov', $prov]])->first();
                                            if($checkFailDistrict){
                                                $reason = $checkFailDistrict->reason;
                                                if($reason == 'already'){
                                                    $responseDistrictN = District::where([['DISTRICT_NAME', $district], ['CPR_MST_CITY_ID', $foundKeyCityOri]])->first(); 
                                                    if($responseDistrictN){
                                                        $foundKeyDistrictOri = $responseDistrictN->ID;
                                                        $insertCounts['alreadyDistrict']++;
                                                        $checkFailDistrict->update([
                                                            'status' => 'done'
                                                        ]);
                                                    }
                                                } else if($reason == 'NO'){
                                                    
                                                }
                                            }
                                        }
                                        if($foundKeyDistrictOri == null){
                                            $dataArray = json_decode($responseDistrict, true);
                                            $keysD = array_keys($dataArray);
                                            $lastKeyD = $keysD[count($keysD)-1];
                                            $latsKeyD = strval($lastKeyD);
                                            $lastThreeDigitsD = substr($latsKeyD, -3);
                                            $q = 0;
                                            do {
                                                $q++;
                                                $newValD = str_pad((intval($lastThreeDigitsD) + $q), strlen($lastThreeDigitsD), "0", STR_PAD_LEFT);
                                                $resNewDistrictKey = $foundKeyCityOri . $newValD;
                                                $search_valueD = array_search($resNewDistrictKey, $keysD);
                                            } while ($search_valueD);
                                            $foundKeyDistrictOri = $resNewDistrictKey;
                                            $insertCounts['generateNewIdDistrict']++;
                                        }
                                        // return $foundKeyDistrictOri;
                                        // return ([
                                        //     'district' => $district,
                                        //     'searchValue' => $searchValueDistrict,
                                        //     'key' => $foundKeyDistrictOri,
                                        //     'data' => $dataDistrict
                                        // ]);
                                        if ($foundKeyDistrictOri !== null) {
                                            // return ([
                                            //     'district' => $foundKeyDistrictOri,
                                            //     'district2' => $districtN
                                            // ]);
                                            $checkDistrict = District::where('ID', $foundKeyDistrictOri)->first();
                                            // return $checkDistrict;
                                            if(!$checkDistrict){
                                                $insertDistrict = District::create([
                                                                    'ID' => $foundKeyDistrictOri,
                                                                    'DISTRICT_NAME' => $district,
                                                                    'REMARK' => 'FROM GRID POINT',
                                                                    'CITY' => $city,
                                                                    'CPR_MST_CITY_ID' => $foundKeyCityOri,
                                                                    'CPR_MST_CITY_ID_BACKUP' => $foundKeyCityOri,
                                                                    'DESCRIPTION' => 'NEW5',
                                                                ]);
                                                if($insertDistrict){
                                                    $insertCounts['districts']++;
                                                    // $ckFailDistrict = FailDistrict::where([['name', $district], ['id_city', $foundKeyCityOri]])->first();
                                                    // if($ckFailDistrict){
                                                    //     $delDistrict = $ckFailDistrict->delete();
                                                    //     if($delDistrict){
                                                    //         $insertCounts['delFailDistrict']++;
                                                    //     }
                                                    // }
                                                }
                                            }
                                            $checkGridMap = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude]])->first();
                                            if(!$checkGridMap){
                                                $insertGridMap = GridMap::create([
                                                    'LONGITUDE' => $longitude,
                                                    'LATITUDE' => $latitude,
                                                    'REMARK' => 'FROM GRID POINT',
                                                    'CPR_MST_DISTRICT_ID' => $foundKeyDistrictOri,
                                                    'DESCRIPTION' => 'NEW4',
                                                ]);
                                                $insertCounts['gridMapInDistrict']++;
                                            }
                                            $foundKeyDistrict = substr($foundKeyDistrictOri, -3);
                                            $responseSubdistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=13&pro=$foundKeyProv&kab=$foundKeyCity&kec=$foundKeyDistrict&lv2=14");                                                
                                            if ($responseSubdistrict->successful()) {
                                                $dataSubdistrict = $responseSubdistrict->json();
                                                $reversedDataSubdistrict = array_flip($dataSubdistrict);
                                                $searchValueSubdistrict = $subdistrict; // The value you want to search for
                                                $foundKeySubdistrict = $reversedDataSubdistrict[$searchValueSubdistrict] ?? null;
                                                if ($foundKeySubdistrict == null) {
                                                    $minDistance = PHP_INT_MAX;
                                                    $closestMatch = "";
                                                
                                                    foreach ($dataSubdistrict as $key => $value) {
                                                        $distance = levenshtein($subdistrict, $value);
                                                        if ($distance < $minDistance) {
                                                            $minDistance = $distance;
                                                            $closestMatch = $value;
                                                        }
                                                    }
                                                
                                                    if ($minDistance <= 2) {
                                                        $foundKeySubdistrict = $reversedDataSubdistrict[$closestMatch] ?? null;
                                                        $insertCounts['subdistrictTypoFixed']++;
                                                    } else {
                                                        $foundKeySubdistrict = null;
                                                        $insertCounts['failSubdistrictTypoFixed']++;
                                                    }
                                                }
                                                if ($foundKeySubdistrict == null){
                                                    $dataArray = json_decode($responseSubdistrict, true);
                                                    if(count($dataArray) > 0){
                                                        $keys = array_keys($dataArray);
                                                        $lastKey = $keys[count($keys)-1];
                                                        $latsKey = strval($lastKey);
                                                        $lastThreeDigits = substr($latsKey, -3);
                                                        $q = 0;
                                                        do {
                                                            $q++;
                                                            $newVal = str_pad((intval($lastThreeDigits) + $q), strlen($lastThreeDigits), "0", STR_PAD_LEFT);
                                                            $resNewSubdistrictKey = $foundKeyDistrictOri . $newVal;
                                                            $search_value = array_search($resNewSubdistrictKey, $keys);
                                                        } while ($search_value);
                                                    }else{
                                                        $lastThreeDigits = '000';
                                                        $q = 0;
                                                        do {
                                                            $q++;
                                                            $newVal = str_pad((intval($lastThreeDigits) + $q), strlen($lastThreeDigits), "0", STR_PAD_LEFT);
                                                            $resNewSubdistrictKey = $foundKeyDistrictOri . $newVal;
                                                            $srchS = Subdistrict::where('ID', $resNewSubdistrictKey)->first();
                                                        } while ($srchS);
                                                    }
                                                    $foundKeySubdistrict = $resNewSubdistrictKey;
                                                    $insertCounts['generateNewIdSubdistrict']++;
                                                }
                                                if ($foundKeySubdistrict !== null) {
                                                        // $insertCounts['aneh']++;
                                                    $checkSubdistrict = Subdistrict::where('ID', $foundKeySubdistrict)->first();
                                                    if(!$checkSubdistrict){
                                                        $insertSubdistrict = Subdistrict::create([
                                                                                'ID' => $foundKeySubdistrict,
                                                                                'SUBDISTRICT_NAME' => $subdistrict,
                                                                                'REMARK' => 'FROM GRID POINT',
                                                                                'DISTRICT_NAME' => $district,
                                                                                'CPR_MST_DISTRICT_ID' => $foundKeyDistrictOri,
                                                                                'DESCRIPTION' => 'NEW5',
                                                                                ]);
                                                        if($insertSubdistrict){
                                                            $insertCounts['subdistricts']++;
                                                            $ckFailSubdistrict = FailSubdistrict::where([['name', $subdistrict], ['id_district', $foundKeyDistrictOri]])->first();
                                                            if($ckFailSubdistrict){
                                                                $delSubdistrict = $ckFailSubdistrict->delete();
                                                                if($delSubdistrict){
                                                                    $insertCounts['delFailSubdistrict']++;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    // return ([
                                                    //     'prov' => $prov,
                                                    //     'key' => $foundKeyProv,
                                                    //     'city' => $city,
                                                    //     'district' => $district,
                                                    //     'subdistrict' => $subdistrict,
                                                    //     'latitude' => $latitude,
                                                    //     'longitude' => $longitude,
                                                    //     'iterasi' => $tes,
                                                    // ]);
                                                    $checkGridMap = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude], ['SUBDISTRICT', null], ['CPR_MST_SUBDISTRICT_ID', null]])->first();
                                                    // return $checkGridMap;
                                                    if($checkGridMap){
                                                        $updateGridMap = $checkGridMap->update([
                                                                            'CPR_MST_SUBDISTRICT_ID' => $foundKeySubdistrict,
                                                                            'SUBDISTRICT' => $subdistrict,
                                                                            'DESCRIPTION' => 'NEW4_UPDATE',
                                                                        ]);
                                                        if($updateGridMap){
                                                            $insertCounts['gridMaps']++;
                                                            $ckFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                                            if($ckFailLatlong){
                                                                $delLatlong = $ckFailLatlong->delete();
                                                                if($delLatlong){
                                                                    $insertCounts['delFailLatlong']++;
                                                                }
                                                            }
                                                            $ckFailFull = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                                            if($ckFailFull){
                                                                $delFull = $ckFailFull->delete();
                                                                if($delFull){
                                                                    $insertCounts['delFailFull']++;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    // return response()->json([
                                                    //     'insert' => $insertGridMap,
                                                    //     'ckll' => $ckFailLatlong,
                                                    //     'cdll' => $delLatlong,
                                                    //     'ckff' => $ckFailFull,
                                                    //     'cdff' => $delFull
                                                    // ]);
                                                    // if($checkSubdistrict){
                                                    //     Aneh::create([
                                                    //         'long' => $longitude,
                                                    //         'lat' => $latitude,
                                                    //         'id_subdistrict' => $foundKeySubdistrict,
                                                    //         'subdistrict' => $subdistrict,
                                                    //         'id_district' => $foundKeyDistrictOri,
                                                    //         'district' => $district,
                                                    //         'id_city' => $foundKeyCityOri,
                                                    //         'city' => $city,
                                                    //         'id_prov' => $foundKeyprov,
                                                    //         'prov' => $prov,
                                                    //         'description' => 'tidak lolos subdistrict',
                                                    //     ]);
                                                    //     $insertCounts['aneh']++;
                                                    // }
                                                    // if(!$checkGridMap){
                                                    //     Aneh::create([
                                                    //         'long' => $longitude,
                                                    //         'lat' => $latitude,
                                                    //         'id_subdistrict' => $foundKeySubdistrict,
                                                    //         'subdistrict' => $subdistrict,
                                                    //         'id_district' => $foundKeyDistrictOri,
                                                    //         'district' => $district,
                                                    //         'id_city' => $foundKeyCityOri,
                                                    //         'city' => $city,
                                                    //         'id_prov' => $foundKeyprov,
                                                    //         'prov' => $prov,
                                                    //         'description' => 'tidak lolos grid map',
                                                    //     ]);
                                                    //     $insertCounts['aneh']++;
                                                    // }
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
                                                    if(!$cf){
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
                                                    } else if($cf && $cf->description !== 'Fail in key subdistrict'){
                                                        $cf->update([
                                                            'description' => 'Fail in key subdistrict'
                                                        ]);
                                                        $insertCounts['updateFailFull']++;
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
                                                if(!$cf){
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
                                            // return 'masuk sini';
                                            $checkFailDistrict = FailDistrict::where([['name', $district], ['id_city',$foundKeyCityOri]])->first();
                                            $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long',$longitude]])->first();
                                            // return ([
                                            //     'name'=> $district,
                                            //     'id_city'=> $foundKeyCityOri
                                            // ]);
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
                                            // return $cf;
                                            if(!$cf){
                                                // return 'masuk sini';
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
                                            } else if($cf && $cf->description !== 'Fail in key district'){
                                                $cf->update([
                                                    'description' => 'Fail in key district'
                                                ]);
                                                $insertCounts['updateFailFull']++;
                                            }
                                            return [
                                                'success' => true,
                                                'insertCounts' => $insertCounts, // Return the updated insertion counts
                                            ];
                                        }
                                    }else {
                                        // return 'masuk sini';
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
                                        if(!$cf){
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
                                    if(!$cf){
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
                                    } else if($cf && $cf->description !== 'Fail in key city'){
                                        $cf->update([
                                            'description' => 'Fail in key city'
                                        ]);
                                        $insertCounts['updateFailFull']++;
                                    }
                                    return [
                                        'success' => true,
                                        'insertCounts' => $insertCounts, // Return the updated insertion counts
                                    ];
                                }
                            }else {
                                $insertCounts['failGetCity']++;
                                Aneh::create([
                                    'long' => $longitude,
                                    'lat' => $latitude,
                                    'subdistrict' => $subdistrict,
                                    'district' => $district,
                                    'city' => $city,
                                    'prov' => $prov,
                                    'description' => 'fail city',
                                ]);
                                $checkFailLatlong = FailLatlong::where([['lat', $latitude], ['long', $longitude]])->first();
                                if(!$checkFailLatlong){
                                    FailLatlong::create([
                                        'lat' => $latitude,
                                        'long' => $longitude
                                    ]);
                                    $insertCounts['failLatlong']++;
                                }
                                $cf = FailFull::where([['lat', $latitude], ['long', $longitude]])->first();
                                if(!$cf){
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
                                if(!$cf){
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
                        if(!$cf){
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
                if(!$cf){
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
    } else {
        $insertCounts['failStrange']++;
    }
}

}
