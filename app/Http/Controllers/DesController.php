<?php

namespace App\Http\Controllers;

use App\Models\City;
use GuzzleHttp\Client;
use App\Models\GridMap;
use App\Models\Latlong;
use App\Models\Strange;
use App\Models\District;
use App\Models\FailCity;
use App\Models\FailFull;
use App\Models\Province;
use App\Models\FailLatlong;
use App\Models\Subdistrict;
use App\Models\FailDistrict;
use Illuminate\Http\Request;
use App\Models\FailSubdistrict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DesController extends Controller
{
    public function testCreate(){
        return $checkCity = DB::connection('second_db')->table('cpr_mst_city')->where('CITY_NAME', 'LIKE', '%' . 'JAKARTA' . '%')->get();
        return City::where('CITY_NAME', 'LIKE', '%' . 'JAKARTA' . '%')->get();
        return GridMap::where('DESCRIPTION', 'NEW4_UPDATE')->get();
        return Subdistrict::where('SUBDISTRICT_NAME', 'LIKE', '%' . 'Metun' . '%')->get();
        $already = 0;
        $notyet = 0;
        for ($i=13635; $i <= 14957 ; $i++) { 
            $data = Latlong::where('id', $i)->first();
            $GM = GridMap::where([['LATITUDE', $data->lat],['LONGITUDE', $data->long]])->first(); 
            if(!$GM){
                return $data;
                $notyet++;
            }else {
                $already++;
            }
            
        }
        return 'yeay';
        return $GM = GridMap::where([['LATITUDE', -1.55],['LONGITUDE', 112.85]])->get(); 
        return $city = City::where('ID', 1372)->get(); 
        $foundKeyDistrictOri = 3523200;
        $foundKeyDistrict = substr($foundKeyDistrictOri, -3);
        // return $foundKeyCity;
        $responseDistrictN = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=13&pro=35&kab=23&kec=$foundKeyDistrict&lv2=14");
        $dataArray = json_decode($responseDistrictN, true);
        // return count($dataArray);
        if(count($dataArray) > 0){
            $keysD = array_keys($dataArray);
            $lastKeyD = $keysD[count($keysD)-1];
            $latsKeyD = strval($lastKeyD);
            $lastThreeDigitsD = substr($latsKeyD, -3);
            $q = 0;
            do {
                $q++;
                $newValD = str_pad((intval($lastThreeDigitsD) + $q), strlen($lastThreeDigitsD), "0", STR_PAD_LEFT);
                $resNewSubdistrictKey = $foundKeyDistrictOri . $newValD;
                $search_valueD = array_search($resNewSubdistrictKey, $keysD);
            } while ($search_valueD);
        }else{
            $lastThreeDigitsD = '000';
            $q = 0;
            do {
                $q++;
                $newValD = str_pad((intval($lastThreeDigitsD) + $q), strlen($lastThreeDigitsD), "0", STR_PAD_LEFT);
                $resNewSubdistrictKey = $foundKeyDistrictOri . $newValD;
                $aswd = Subdistrict::where('ID', $resNewSubdistrictKey)->first();
            } while ($aswd);
        }
        return $foundKeyDistrict = $resNewSubdistrictKey;

        return Subdistrict::where('SUBDISTRICT_NAME', 'LIKE', '%' . 'DANAU' . '%')->get();
        $data = City::all();
                        // $arr = json_decode($datac, true);
                // $dataReady = array_values($arr);
        $dataArray = json_decode($data, true);
        $keys = array_values($dataArray);
        // return $keys;
        for ($i=0; $i < count($keys); $i++) { 
            $find = City::where('CITY_NAME', $keys[$i]['CITY_NAME'])->get();
            $dataArray = json_decode($find, true);
            $finded = array_values($dataArray);
            if(count($finded) > 1){
                return [
                    $find,
                    $i
                ];
            }
        }
        return 'yeay nggk ada';
        return FailLatlong::where([['lat', 4.15], ['long', 117.85]])->first();

            $responseDistrict = District::where('DISTRICT_NAME', 'LIKE', '%' . 'WAY KRUI' . '%')->first(); 
            $foundKeyDistrictOri = $responseDistrict->ID;
            $foundKeyDistrict = substr($foundKeyDistrictOri, -3);
            $responseSubdistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=13&pro=18&kab=13&kec=$foundKeyDistrict&lv2=14");
            // Decode the JSON data into an associative array
            $dataArray = json_decode($responseSubdistrict, true);
            $keys = array_keys($dataArray);
            // return $keys[8];
            $lastKey = $keys[count($keys)-9];
            $latsKey = strval($lastKey);
            $lastThreeDigits = substr($latsKey, -3);
            // $newVal = str_pad((intval($lastThreeDigits) + 1), strlen($lastThreeDigits), "0", STR_PAD_LEFT);
            // $res = $foundKeyDistrictOri . $newVal;
            // return gettype($val);
            // $search_value = array_search($res, $keys); 
            // return response()->json([
            //     $val,
            //     $keys[0]
            // ]);
            
            $i = 0;
            do {
                $i++;
                $newVal = str_pad((intval($lastThreeDigits) + $i), strlen($lastThreeDigits), "0", STR_PAD_LEFT);
                $res = $foundKeyDistrictOri . $newVal;
                $search_value = array_search($res, $keys);
            } while ($search_value);
            return response()->json([
                $res,
                $i
            ]);
            for ($i=0; $i < count($keys); $i++) { 
                # code...
            }
            $check = District::where('ID', $dataReady[$i]['real_id'])->first();
            if(!$check){
                $insertDistrict = District::create([
                    'ID' => $dataReady[$i]['real_id'],
                    'DISTRICT_NAME' => $dataReady[$i]['name'],
                    'REMARK' => 'FROM GRID POINT',
                    'CITY' => $dataReady[$i]['city'],
                    'CPR_MST_CITY_ID' => $dataReady[$i]['id_city'],
                    'CPR_MST_CITY_ID_BACKUP' => $dataReady[$i]['id_city'],
                    'DESCRIPTION' => 'NEW5',
                ]);
                if($insertDistrict){
                    $success++;
                    $fd = FailDistrict::where('real_id', $dataReady[$i]['real_id'])->first();
                    $del = $fd->delete();
                    if($del){
                        $successDel++;
                    }else{
                        $failDel++;
                    }
                } else {
                    $fail++;
                }
            }

        // return $as = District::where([['DISTRICT_NAME', 'LIKE', '%' . 'CIKARANG' . '%']])->get();
        // return $as;
        // $uptd = $as->update([
        //     'ID' => 7102240013
        // ]);
        // $ow = GridMap::where([['SUBDISTRICT', 'LIKE', '%' . 'RERER I'], ['DESCRIPTION', 'NEW3']])->get();
        // return $ow;
        // $uptd = $ow->update([
        //     'CPR_MST_SUBDISTRICT_ID' => 7102240013
        // ]);
        // return $uptd;
        // return District::where([['DISTRICT_NAME', 'LIKE', '%' . ' 2'], ['DESCRIPTION', 'NEW3']])->get();
    // $data = "LE WA";
    // $responseDistrict = Http::get("https://sipedas.pertanian.go.id/api/wilayah/list_wilayah?thn=2023&lvl=12&pro=53&kab=02&lv2=13");
    // $dataDistrict = $responseDistrict->json();

    // $minDistance = PHP_INT_MAX;
    // $closestMatch = "";

    // foreach ($dataDistrict as $key => $value) {
    //     $distance = levenshtein($data, $value);
    //     if ($distance < $minDistance) {
    //         $minDistance = $distance;
    //         $closestMatch = $value;
    //     }
    // }

    // if ($minDistance <= 2) {
    //     $reversedDataDistrict = array_flip($dataDistrict);
    //     $districtN = $closestMatch;
    //     $searchValueDistrict = $districtN; // The value you want to search for
    //     $foundKeyDistrictOri = $reversedDataDistrict[$searchValueDistrict] ?? null;
    //     return $foundKeyDistrictOri;
    // } else {
    //     $foundKeyDistrictOri = null;
    // }
        // return count(City::where('DESCRIPTION', 'NEW2')->get());
        // return District::where('CPR_MST_CITY_ID', 6402)->get();
        // return $resource = FailFull::where('city', 'LIKE' , '%' . 'PASURUAN' . '%')->get();
        // $key = $resource->ID;
        // return $key;
        // return gettype(substr($key, -2));
        // return FailDistrict::where('prov', 'LIKE', '%' . 'PASURUAN' . '%')->get();
        // return City::where('ID', 6504)->get();
        // return GridMap::where([['LATITUDE', '3.45'], ['LONGITUDE', '115.65']])->first();
        // return FailFull::where([['lat', 3.45], ['long', 115.65]])->first();
        // return 
        // $latitude = '-10.25';
        // $longitude = '120.45';
        // if($cf && $cf->description !== 'Fail in key district'){
        //     // return 'yeay';
        //     $uptd = $cf->update([
        //         'description' => 'Fail in key district'
        //     ]);
        //     return $uptd;
        // }
        // return 'hmm';
        // return GridMap::where([['LATITUDE', '-7.25'], ['LONGITUDE', '138.95']])->first();
        // return FailLatlong::where([['lat', '-1.25'], ['long', '132.35']])->first();
        // $district = 'DISTRIK AIFAT';
        // return ('KABUPATEN' == 'KABUPATéN');
        // $district = "DISTRIK "
        // if(strpos($district, "DISTRIK") !== false){
        //     $districtN = str_replace("DISTRIK", "", $district);
        //     $districtN = trim($districtN);
        //     return response()->json([
        //         $districtN,
        //     ]); 
        // }else{
        //     return 'yahh';
        // }
        // return City::where('CITY_NAME', 'LIKE', '%' . 'KARANGANYAR')->first();
        // return GridMap::where([['LATITUDE', '-7.65'], ['LONGITUDE', '111.05']])->first();
        // $upt->update([
        //     'reason' => 'Tidak ada data subdistrict yang serupa di web pertanian'
        // ]);
        // $res = FailFull::where('district', 'LIKE' , '%' . 'DISTRIK' . '%')->get();
        // return count($res);
        // if (strpos($res->district, "DISTRIK") !== false) {
        //     $districtN = str_replace("DISTRIK", "", $res->district);
        //     $districtN = trim($districtN);
        //     // $districtN = 'KABUPATEN ' . $districtN;
        //     return response()->json([
        //         $districtN,
        //     ]) ;
        // } else {
        //     return 'yah';
        // }
        // $tid = 11111;
        // $cr = City::create([
        //     'ID' => $tid,
        //     'CITY_NAME' => "TUBANAN",
        //     'REMARK' => 'FROM GRID POINT',
        //     'LONGITUDE' => '3.25',
        //     'LATITUDE' => '1.25',
        //     'PROVINCE' => 'JJJ',
        //     'CPR_MST_PROVINCE_ID' => 0,
        //     'DESCRIPTION' => 'NEW',
        // ]);
        // $cr = District::create([
        //     'ID' => $tid,
        //     'DISTRICT_NAME' => 'asem',
        //     'REMARK' => 'FROM GRID POINT',
        //     'CITY' => 'asemmm',
        //     'CPR_MST_CITY_ID' => 0,
        //     'CPR_MST_CITY_ID_BACKUP' => 0,
        //     'DESCRIPTION' => 'NEW',
        // ]);
        // $cr = Subdistrict::create([
        //     'ID' => $tid,
        //     'SUBDISTRICT_NAME' => 'asemmm',
        //     'REMARK' => 'FROM GRID POINT',
        //     'DISTRICT_NAME' => 'asemmm',
        //     'CPR_MST_DISTRICT_ID' => 1111111,
        //     'DESCRIPTION' => 'NEW',
        // ]);

        // return $cr;
    }

    public function testDestroy(){
        return FailFull::where([['lat', -5.15], ['long', 103.95]])->first();
        
        // $lat = "-8.45";
        // $long = "115.15";
        // $ck = FailCity::where([['lat', $lat], ['long', $long]])->first();
        // if($ck){
        //     $delCity = $ck->delete();
        //     return $delCity;
        // }
        // return FailLatlong::where('id', 3520)->delete();
        $deleted = 0;
        $tkda = 0;
        for($i = 1; $i <= 495; $i++){
            $data = FailDistrict::where('id', $i)->first();
            // return $data->id_city;
            if($data){
                // return 'masuk';
                // $sdistrict = District::where([['DISTRICT_NAME', "KECAMATAN TEUPAH SELATAN"]])->first();
                // return $sdistrict;
                // return District::where('ID', 3316090)->first();
                // "CPR_MST_DISTRICT_ID": 3316090,
                // "CPR_MST_SUBDISTRICT_ID": 3316090020,
                $search = GridMap::where([['LATITUDE', $data->lat], ['LONGITUDE', $data->long]])->first();
                // return $search;
                if($search ){
                    return $data;
                    // FailCity::where('id', $data->id)->delete();
                    // $deleted++;
                } else{
                    $tkda++;
                }
            }
        }
        return response()->json([
            'message' => 'success',
            'deleted'=> $deleted,
            'tkda'=> $tkda,
        ]);
    }

    // public function delDup(){
    //     $deleted = 0;
    //     // return GridMap::where('LATITUDE', null)->first();
    //     for($i = 1; $i <= 11577; $i++){
    //         // return 'masuk';
    //         $data = GridMap::where('ID', $i)->first();
    //         // return $data->LATITUDE;
    //         if($data){
    //             $datac = GridMap::where([['LATITUDE', $data->LATITUDE], ['LONGITUDE', $data->LONGITUDE], ['CPR_MST_DISTRICT_ID', $data->CPR_MST_DISTRICT_ID], ['CPR_MST_SUBDISTRICT_ID', $data->CPR_MST_SUBDISTRICT_ID], ['SUBDISTRICT', $data->SUBDISTRICT]])->get();
    //             $arr = json_decode($datac, true);
    //             $dataReady = array_values($arr);
    //             if(count($dataReady) > 1){
    //                 return ($dataReady);
    //                 for($p = 1; $p < count($dataReady); $p++){
    //                     GridMap::where('ID', $dataReady[$p]['ID'])->delete();
    //                     $deleted++;
    //                 }
    //             }
    //         }
    //     }
    //     return response()->json([
    //         'message' => 'success',
    //         'deleted'=> $deleted
    //     ]);
    // }
    public function delDup(){
        $deleted = 0;
        $ck=0;
        // return GridMap::where('LATITUDE', null)->first();
        // return count(FailFull::all());
        for($i = 3520; $i <= 3588; $i++){
            // return 'masuk';
            $data = FailLatlong::where('id', $i)->first();
            // return $data->LATITUDE;
            if($data){
                $datac = FailFull::where([['lat', $data->lat], ['long', $data->long]])->first();
                if(!$datac){
                    return $data;
                }else{
                    $ck++;
                }
                // $arr = json_decode($datac, true);
                // $dataReady = array_values($arr);
                // if(count($dataReady) > 2){
                //     return ($dataReady);
                    // for($p = 1; $p < count($dataReady); $p++){
                    //     Latlong::where('id', $dataReady[$p]['id'])->delete();
                    //     $deleted++;
                    // }
                // }
            }
        }
        return response()->json([
            'message' => 'success',
            'deleted'=> $deleted,
            'ck'=> $ck
        ]);
    }

    public function mistake(){
        // for($i = 1; $i <= 1; $i++){
        for($i = 5825; $i <= 7000; $i++){
            $ls = Latlong::where('id', $i)->first();
            // return $ls = Latlong::where([['lat', -2.85], ['long', 112.35]])->first();
            $latitude = $ls['lat'];
            // return gettype($latitude);
            $longitude = $ls['long'];
            $mistake = 0;
            $notOk = 0;
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            $client = new Client();
            $response = $client->get("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&language=id&key=$apiKey");

            $data = json_decode($response->getBody(), true);
            return $data;
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
                if($prov && $city && $district && $subdistrict){
                    if($prov == "SOUTH SUMATRA"){
                        $crt = Mistake::create([
                            'lat' => $latitude,
                            'long' => $longitude,
                        ]);
                    }
                    $mistake++;
                }
            } else {
                $notOk++;

            }
        }
    }
}
