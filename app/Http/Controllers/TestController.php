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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    public function insAll(){
        
        $insertCounts= [
            'insertProv' => 0,
            'failInsertProv' => 0,
            'alreadyProv' => 0,
            'insertCity' => 0,
            'failInsertCity' => 0,
            'alreadyCity' => 0,
            'insertDistrict' => 0,
            'failInsertDistrict' => 0,
            'alreadyDistrict' => 0,
            'insertSubdistrict' => 0,
            'failInsertSubdistrict' => 0,
            'alreadySubdistrict' => 0,
        ];
        $responseProv = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=10&lv2=11');
        if ($responseProv->successful()) {
            $dataArrayProv = json_decode($responseProv, true);
            $provKeys = array_keys($dataArrayProv);
            for ($i=0; $i < count($provKeys); $i++) { 
                $checkProv = DB::connection('second_db')->table('cpr_mst_province')->where('ID', $provKeys[$i])->first();
                if (!$checkProv){
                    $data = [
                        'ID' => $provKeys[$i],
                        'PROVINCE_NAME' => $dataArrayProv[$provKeys[$i]],
                        'REMARKS' => 'FROM PERTANIAN',
                        'CREATED_BY' => 'SOFYAN',
                        'DATE_CREATED' => NOW(), 
                    ];
                    $instProv = DB::connection('second_db')->table('cpr_mst_province')->insert($data);
                    if($instProv){
                        $insertCounts['insertProv']++;
                    } else{
                        $insertCounts['failInsertProv']++;
                    }
                } else{
                    $insertCounts['alreadyProv']++;
                }
                $responseCity = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=11&pro=$provKeys[$i]&lv2=12");
                if ($responseCity->successful()) {
                    $dataArrayCity = json_decode($responseCity, true);
                    $cityKeys = array_keys($dataArrayCity);
                    for ($p=0; $p < count($cityKeys); $p++) { 
                        $checkCity = DB::connection('second_db')->table('cpr_mst_city')->where('ID', $cityKeys[$p])->first();
                        if (!$checkCity){
                            $data = [
                                'ID' => $cityKeys[$p],
                                'CITY_NAME' => $dataArrayCity[$cityKeys[$p]],
                                'REMARKS' => 'FROM PERTANIAN',
                                'CREATED_BY' => 'SOFYAN',
                                'DATE_CREATED' => NOW(), 
                                // 'LONGITUDE' => $cityReady[$p]['LONGITUDE'],
                                // 'LATITUDE' => $cityReady[$p]['LATITUDE'],
                                'PROVINCE' => $dataArrayProv[$provKeys[$i]],
                                'CPR_MST_PROVINCE_ID' => $provKeys[$i],
                            ];
                            $instCity = DB::connection('second_db')->table('cpr_mst_city')->insert($data);
                            if($instCity){
                                $insertCounts['insertCity']++;
                            } else {
                                $insertCounts['failInsertCity']++;
                            }
                        } else{
                            $insertCounts['alreadyCity']++;
                        }
                        // return $cityKeys[$p];
                        $cityKeysSearch = substr($cityKeys[$p], -2);
                        $responseDistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=12&pro=$provKeys[$i]&kab=$cityKeysSearch&lv2=13");
                        if ($responseDistrict->successful()) {
                            $dataArrayDistrict = json_decode($responseDistrict, true);
                            $districtKeys = array_keys($dataArrayDistrict);
                            for ($q=0; $q < count($districtKeys); $q++) { 
                                $checkDistrict = DB::connection('second_db')->table('cpr_mst_district')->where('ID', $districtKeys[$q])->first();
                                if (!$checkDistrict){
                                    $data = [
                                        'ID' => $districtKeys[$q],
                                        'DISTRICT_NAME' => $dataArrayDistrict[$districtKeys[$q]],
                                        'REMARKS' => 'FROM PERTANIAN',
                                        'CREATED_BY' => 'SOFYAN',
                                        'DATE_CREATED' => NOW(), 
                                        'CITY' => $dataArrayCity[$cityKeys[$p]],
                                        'CPR_MST_CITY_ID' => $cityKeys[$p],
                                        'CPR_MST_CITY_ID_BACUP' => $cityKeys[$p],
                                    ];
                                    $instDistrict = DB::connection('second_db')->table('cpr_mst_district')->insert($data);
                                    if($instDistrict){
                                        $insertCounts['insertDistrict']++;
                                    } else {
                                        $insertCounts['failInsertDistrict']++;
                                    }
                                } else{
                                    $insertCounts['alreadyDistrict']++;
                                }
                                $districtKeysSearch = substr($districtKeys[$q], -3);
                                $responseSubdistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=13&pro=$provKeys[$i]&kab=$cityKeysSearch&kec=$districtKeysSearch&lv2=14");
                                if ($responseSubdistrict->successful()) {
                                    $dataArraySubdistrict = json_decode($responseSubdistrict, true);
                                    $subdistrictKeys = array_keys($dataArraySubdistrict);
                                    for ($r=0; $r < count($subdistrictKeys); $r++) { 
                                        $checkSubdistrict = DB::connection('second_db')->table('cpr_mst_subdistrict')->where('ID', $subdistrictKeys[$r])->first();
                                        if (!$checkSubdistrict){
                                            $data = [
                                                'ID' => $subdistrictKeys[$r],
                                                'SUBDISTRICT_NAME' => $dataArraySubdistrict[$subdistrictKeys[$r]],
                                                'REMARKS' => 'FROM PERTANIAN',
                                                'CREATED_BY' => 'SOFYAN',
                                                'DATE_CREATED' => NOW(), 
                                                'DISTRICT_NAME' => $dataArrayDistrict[$districtKeys[$q]],
                                                'CPR_MST_DISTRICT_ID' => $districtKeys[$q],
                                            ];
                                            $instSubdistrict = DB::connection('second_db')->table('cpr_mst_subdistrict')->insert($data);
                                            if($instSubdistrict){
                                                $insertCounts['insertSubdistrict']++;
                                            } else {
                                                $insertCounts['failInsertSubdistrict']++;
                                            }
                                        } else{
                                            $insertCounts['alreadySubdistrict']++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return response()->json([
            'message' => 'success',
            'insertCounts' => $insertCounts
        ]);
    }

    public function testing(){
    // public function getDistrictSubdistrict(){
        $ls = Latlong::where('id', 1374)->first();
        // return $ls;
        $latitude = $ls['lat'];
        $longitude = $ls['long'];
        $checkLatLong = GridMap::where([['LONGITUDE', $longitude], ['LATITUDE', $latitude]])->first();
        if(!$checkLatLong){
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            $client = new Client();
            $response = $client->get("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&language=id&key=$apiKey");

            $data = json_decode($response->getBody(), true);
            // return $data;

            if ($data['status'] === 'OK') {
                $addressComponents = $data['results'][1]['address_components'];
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
                    'all' => $addressComponents,
                ]);

                
            } else {
                return response()->json(['message' => 'Location not found'], 404);
            }
        } else {
            return response()->json(['message' => 'Already'], 404);
        }
}

public function sdb(){
$prov1 = DB::table('provinces')->get();
$arr = json_decode($prov1, true);
$amount = 0;

$provready = array_values($arr);

for($i = 2; $i < count($provready); $i++){
    $data = [
        'ID' => $provready[$i]['ID'],
        'PROVINCE_NAME' => $provready[$i]['PROVINCE_NAME'],
        'REMARKS' => 'FROM GRID POINT',
        'CREATED_BY' => 'SOFYAN',
        'DATE_CREATED' => NOW(), 
    ];
    $inst = DB::connection('second_db')->table('cpr_mst_province')->insert($data);
    if($inst){
        $amount++;
    }
}
// $prov = DB::connection('second_db')->table('cpr_mst_province')->get();
// return $prov;
return response()->json([
        'message' => 'success',
        'amount' => $amount
    ]);
}

    public function insCity(){
        return count(DB::connection('second_db')->table('cpr_mst_city')->get());
        $city = DB::table('cities')->where('DESCRIPTION', 'NEW5')->get();
        // $city = DB::table('cities')->get();
        // return $city;
        $arr = json_decode($city, true);
        $amount = 0;
        $already = 0;
        
        $cityReady = array_values($arr);
        // return count($cityReady);
        // return ([
        //     $cityReady[0],
        //     $cityReady[0]['ID'],
        //     $cityReady[0]['CITY_NAME'],
        //     $cityReady[0]['LONGITUDE'],
        //     $cityReady[0]['LATITUDE'],
        //     $cityReady[0]['PROVINCE'],
        //     $cityReady[0]['CPR_MST_PROVINCE_ID'],
        // ]);
        for($i = 0; $i < count($cityReady); $i++){
            $check = DB::connection('second_db')->table('cpr_mst_city')->where('ID', $cityReady[$i]['ID'])->first();
            if(!$check){
                $data = [
                    'ID' => $cityReady[$i]['ID'],
                    'CITY_NAME' => $cityReady[$i]['CITY_NAME'],
                    'REMARKS' => 'FROM GRID POINT',
                    'CREATED_BY' => 'SOFYAN',
                    'DATE_CREATED' => NOW(), 
                    'LONGITUDE' => $cityReady[$i]['LONGITUDE'],
                    'LATITUDE' => $cityReady[$i]['LATITUDE'],
                    'PROVINCE' => $cityReady[$i]['PROVINCE'],
                    'CPR_MST_PROVINCE_ID' => $cityReady[$i]['CPR_MST_PROVINCE_ID'],
                ];
                $inst = DB::connection('second_db')->table('cpr_mst_city')->insert($data);
                if($inst){
                    $amount++;
                }
            } else {
                $already++;
            }
        }
        return response()->json([
                'message' => 'success',
                'amount' => $amount,
                'already' => $already
            ]);
    }

    public function insDistrict(){
        // return DB::connection('second_db')->table('cpr_mst_district')->where([['DISTRICT_NAME', 'LIKE', '%' . 'CIKARANG' . '%']])->get();
        return count(DB::connection('second_db')->table('cpr_mst_district')->get());
        // $district = DB::table('districts')->get();
        $district = DB::table('districts')->where('DESCRIPTION', 'NEW5')->get();
        $arr = json_decode($district, true);
        $amount = 0;
        $already = 0;
        
        $districtReady = array_values($arr);
        // return count($districtReady);
        // return ([
        //     $districtReady[0],
        //     $districtReady[0]['ID'],
        //     $districtReady[0]['DISTRICT_NAME'],
        //     // $districtReady[0]['LONGITUDE'],
        //     // $districtReady[0]['LATITUDE'],
        //     $districtReady[0]['CITY'],
        //     $districtReady[0]['CPR_MST_CITY_ID'],
        //     $districtReady[0]['CPR_MST_CITY_ID_BACKUP']
        // ]);
        for($i = 0; $i < count($districtReady); $i++){
            $check = DB::connection('second_db')->table('cpr_mst_district')->where('ID', $districtReady[$i]['ID'])->first();
            if(!$check){
                $data = [
                    'ID' => $districtReady[$i]['ID'],
                    'DISTRICT_NAME' => $districtReady[$i]['DISTRICT_NAME'],
                    'REMARKS' => 'FROM GRID POINT',
                    'CREATED_BY' => 'SOFYAN',
                    'DATE_CREATED' => NOW(), 
                    'CITY' => $districtReady[$i]['CITY'],
                    'CPR_MST_CITY_ID' => $districtReady[$i]['CPR_MST_CITY_ID'],
                    'CPR_MST_CITY_ID_BACUP' => $districtReady[$i]['CPR_MST_CITY_ID_BACKUP'],
                ];
                $inst = DB::connection('second_db')->table('cpr_mst_district')->insert($data);
                if($inst){
                    $amount++;
                }
            } else {
                $already++;
            }
        }
        return response()->json([
                'message' => 'success',
                'amount' => $amount,
                'already' => $already,
            ]);
    }

    public function insSubdistrict(){
        return count(DB::connection('second_db')->table('cpr_mst_subdistrict')->get());
        $subdistrict = DB::table('subdistricts')->where('DESCRIPTION', 'NEW5')->get();
        // $subdistrict = DB::table('subdistricts')->get();
        $arr = json_decode($subdistrict, true);
        $amount = 0;
        $already = 0;
        
        $subdistrictReady = array_values($arr);
        // return count($subdistrictReady);
        // return ([
        //     $subdistrictReady[0],
        //     $subdistrictReady[0]['ID'],
        //     $subdistrictReady[0]['SUBDISTRICT_NAME'],
        //     // $subdistrictReady[0]['LONGITUDE'],
        //     // $subdistrictReady[0]['LATITUDE'],
        //     $subdistrictReady[0]['DISTRICT_NAME'],
        //     $subdistrictReady[0]['CPR_MST_DISTRICT_ID'],
        // ]);
        for($i = 0; $i < count($subdistrictReady); $i++){
            $check = DB::connection('second_db')->table('cpr_mst_subdistrict')->where('ID', $subdistrictReady[$i]['ID'])->first();
            if(!$check){
                $data = [
                    'ID' => $subdistrictReady[$i]['ID'],
                    'SUBDISTRICT_NAME' => $subdistrictReady[$i]['SUBDISTRICT_NAME'],
                    'REMARKS' => 'FROM GRID POINT',
                    'CREATED_BY' => 'SOFYAN',
                    'DATE_CREATED' => NOW(), 
                    'DISTRICT_NAME' => $subdistrictReady[$i]['DISTRICT_NAME'],
                    'CPR_MST_DISTRICT_ID' => $subdistrictReady[$i]['CPR_MST_DISTRICT_ID'],
                ];
                $inst = DB::connection('second_db')->table('cpr_mst_subdistrict')->insert($data);
                if($inst){
                    $amount++;
                }
            } else {
                $already++;
            }
        }
        return response()->json([
                'message' => 'success',
                'amount' => $amount,
                'already' => $already
            ]);
    }

    public function insGridMap(){
        // return DB::connection('second_db')->table('cpr_mst_grid_map')->where([['CPR_MST_DISTRICT_ID', 3216061]])->get();
        return DB::connection('second_db')->table('cpr_mst_grid_map')->where([['CPR_MST_DISTRICT_ID', '!=', null], ['SUBDISTRICT', '!=' , null], ['CPR_MST_SUBDISTRICT_ID', '!=' , null], ['MODIFIED_BY', null]])->get();
        $gridMap = DB::table('grid_maps')->where('DESCRIPTION', 'NEW4_UPDATE')->get();
        $arr = json_decode($gridMap, true);
        $gridMapReady = array_values($arr);
        // return count($gridMapReady);
        $update = 0;
        $failUpdate = 0;
        $updatefull = 0;
        $failUpdatefull = 0;
        $already = 0;
        $notFound = 0;

        // return ([
        //     $gridMapReady[0]['CPR_MST_SUBDISTRICT_ID'],
        //     gettype($gridMapReady[0]['CPR_MST_SUBDISTRICT_ID']),
        // ]);
        
        for($i = 0; $i < count($gridMapReady); $i++){
            $check = DB::connection('second_db')->table('cpr_mst_grid_map')->where([['LONGITUDE', $gridMapReady[$i]['LONGITUDE']], ['LATITUDE', $gridMapReady[$i]['LATITUDE']]])->first();
            if($check){
                if($check->CPR_MST_DISTRICT_ID && !$check->SUBDISTRICT && !$check->CPR_MST_SUBDISTRICT_ID){
                    $updatedData = [
                                'SUBDISTRICT' => $gridMapReady[$i]['SUBDISTRICT'],
                                'CPR_MST_SUBDISTRICT_ID' => $gridMapReady[$i]['CPR_MST_SUBDISTRICT_ID'],
                                'DATE_MODIFIED' => NOW(), 
                            ];
                    $updt = DB::connection('second_db')->table('cpr_mst_grid_map')->where([['LONGITUDE', $gridMapReady[$i]['LONGITUDE']], ['LATITUDE', $gridMapReady[$i]['LATITUDE']]])->update($updatedData);
                    if($updt){
                        $update++;
                    }else{
                        $failUpdate++;
                    }
                } else if(!$check->CPR_MST_DISTRICT_ID && !$check->SUBDISTRICT && !$check->CPR_MST_SUBDISTRICT_ID){
                    $updatedData = [
                        'SUBDISTRICT' => $gridMapReady[$i]['SUBDISTRICT'],
                        'CPR_MST_SUBDISTRICT_ID' => $gridMapReady[$i]['CPR_MST_SUBDISTRICT_ID'],
                        'CPR_MST_DISTRICT_ID' => $gridMapReady[$i]['CPR_MST_DISTRICT_ID'],
                        'REMARKS' => 'FROM GRID POINT',
                        'MODIFIED_BY' => 'SOFYAN',
                        'DATE_MODIFIED' => NOW(), 
                    ];
                    $updt = DB::connection('second_db')->table('cpr_mst_grid_map')->where([['LONGITUDE', $gridMapReady[$i]['LONGITUDE']], ['LATITUDE', $gridMapReady[$i]['LATITUDE']]])->update($updatedData);
                    if($updt){
                        $updatefull++;
                    }else{
                        $failUpdatefull++;
                    }
                }else {
                    $already++;
                }
            }else {
                $notFound++;
            }
        }
        return response()->json([
                'message' => 'success',
                'update' => $update,
                'failUpdate' => $failUpdate,
                'update' => $updatefull,
                'failUpdate' => $failUpdatefull,
                'already' => $already,
                'notFound' => $notFound,
            ]);
    }

    public function updateGridMap(){
        // return $check = count(DB::connection('second_db')->table('cpr_mst_grid_map')->get());
        $update = 0;
        $failUpdate = 0;
        $aneh = 0;
        $gridMapNew = DB::table('gmnews')->get();
        $gmnewArray = json_decode($gridMapNew, true);
        // return count($gmnewArray);
        for ($i=0; $i < count($gmnewArray); $i++) { 
            $check = DB::connection('second_db')->table('cpr_mst_grid_map')->where([['LONGITUDE', $gmnewArray[$i]['LONGITUDE']], ['LATITUDE', $gmnewArray[$i]['LATITUDE']]])->first();
            // if($check->GRID_ID == null){
            //     $aneh++;
            // }
            if($check->GRID_ID == null){
                $updatedData = [
                    'GRID_ID' => $gmnewArray[$i]['GRID_ID'],
                    'DATE_MODIFIED' => NOW(), 
                ];
                $updt = DB::connection('second_db')->table('cpr_mst_grid_map')->where([['LONGITUDE', $gmnewArray[$i]['LONGITUDE']], ['LATITUDE', $gmnewArray[$i]['LATITUDE']]])->update($updatedData);
                if($updt){
                    $update++;
                }else{
                    $failUpdate++;
                }
            }else{
                $aneh++;
            }
        }
        return response()->json([
            'message' => 'success',
            'update' => $update,
            'failUpdate' => $failUpdate,
            'aneh' => $aneh,
        ]);
    }

}
