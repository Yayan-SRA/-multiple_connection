<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Models\Approval;
use App\Models\Terminal;
use Illuminate\Http\Request;
use App\Http\Traits\CrudTrait;
use App\Http\Traits\GlobalTrait;
use Illuminate\Support\BigInteger;
use Illuminate\Support\Facades\DB;

class SunOutageController extends Controller
{
    private $outageData = [];

    public function category(Request $request){
        $url  = $request->header('xURL');
        $crud = $this->cekCrud($url, 'R');

        if ($crud == 1) {
            $userID = Auth::user()->ID;
            $getID = DB::table('ADM_TRX_USERROLE')->where('ADM_MST_USER_ID', $userID)->select('ADM_MST_ROLE_ID')->first();

            if($getID->ADM_MST_ROLE_ID == 104 || $getID->ADM_MST_ROLE_ID == 132){
                $data = [[
                    'CATEGORY' => 'SITE'
                    ]];
                return response()->json([
                    'message' => 'Success',
                    'status' => true,
                    'data' => $data,
                ], 200);
            }else{
                $data = [[
                    'CATEGORY' => 'GATEWAY'
                    ],[
                    'CATEGORY' => 'SITE'
                    ],[
                    'CATEGORY' => 'TERMINAL REFERENCE'
                    ]];
                return response()->json([
                    'message' => 'Success',
                    'status' => true,
                    'data' => $data,
                ], 200);
            }
        } else {
            return $this->unauthorize();
        }
    }
    
    public function index(Request $request)
    {
        $url  = $request->header('xURL');
        $crud = $this->cekCrud($url, 'R');

        if ($crud == 1) {
            // return $request->CATEGORY;
            $userID = Auth::user()->ID;
            $getID = DB::table('ADM_TRX_USERROLE')->where('ADM_MST_USER_ID', $userID)->select('ADM_MST_ROLE_ID', 'ADM_MST_SITE_ID')->first();
            $category = $request->CATEGORY;
            // return $getID->ADM_MST_ROLE_ID;
            // $getID = DB::table('ADM_MST_USER')
            // ->join('ADM_TRX_USERROLE', 'ADM_TRX_USERROLE.ADM_MST_USER_ID', 'ADM_MST_USER.ID')
            // ->join('ADM_MST_ROLE', 'ADM_TRX_USERROLE.ADM_MST_ROLE_ID', 'ADM_MST_ROLE.ID')
            // ->select([
            //     'ADM_MST_ROLE.ID as ROLE_ID',
            // ])
            // ->where('ADM_MST_USER.ID', $userID)
            // ->first();
            // return $category == 'TERMINAL REFERENCE';
            if($category == 'SITE'){
                $terminalType = 1;
                if($getID->ADM_MST_ROLE_ID == 104  || $getID->ADM_MST_ROLE_ID == 132){
                    $data = DB::table('CPR_TRX_SUBSCRIBER')->where([['ADM_MST_SITE_ID', $getID->ADM_MST_SITE_ID], ['VALID_UNTIL',null]])->select(['ID','SUBSCRIBER_NAME  as NAME'])->get();
                } else{
                    $data = DB::table('CPR_TRX_SUBSCRIBER')->where([['TERMINAL_TYPE', $terminalType], ['VALID_UNTIL',null]])->select(['ID','SUBSCRIBER_NAME  as NAME'])->get();
                }
            } elseif($category === 'TERMINAL REFERENCE' && ($getID->ADM_MST_ROLE_ID != 104 || $getID->ADM_MST_ROLE_ID != 132)){
                $data = DB::table('CPR_TRX_SUBSCRIBER')->where([['TERMINAL_TYPE', 2], ['VALID_UNTIL',null]])->select(['ID','SUBSCRIBER_NAME  as NAME'])->get();
            } elseif($category === 'GATEWAY' && ($getID->ADM_MST_ROLE_ID != 104  || $getID->ADM_MST_ROLE_ID != 132)){
                $data = DB::table('CPR_MST_GATEWAY')->where('VALID_UNTIL',null)->select(['ID','GATEWAY_NAME as NAME'])->get();
            }

            if($data){
                return response()->json([
                    'message' => 'success',
                    'status' => 200,
                    'data' => $data,
                ]);
            }else if(!$data){
                return response()->json([
                    'message' => 'success',
                    'status' => 200,
                    'data' => 'Data not found',
                ]);
            }else{
                return response()->json([
                    'message' => 'failed',
                    'status' => 404
                ]);
            }
        } else {
            return $this->unauthorize();
        }
    }

    public function select(Request $request)
    {
        $url  = $request->header('xURL');
        $crud = $this->cekCrud($url, 'R');
        // return $crud;
        if ($crud == 1) {
            // return $request;
            $category = $request->CATEGORY;
            $id = $request->ID;
            if($category == 'SITE' || $category == 'TERMINAL REFERENCE'){
                $data = DB::table('CPR_TRX_SUBSCRIBER AS SS')
                    ->leftJoin('TOP_TRX_TMO as TMO', 'SS.ID', 'TMO.CPR_TRX_SUBSCRIBER_ID')
                    ->where('SS.ID', $id)
                    ->orderBy('SS.ID', 'asc')
                    ->select([
                        'SS.SUBSCRIBER_NAME as NAME',
                        'TMO.ANTENNA_SIZE',
                        'TMO.LONGITUDE',
                        'TMO.LATTITUDE',
                    ])
                    ->first();
            } else if($category == 'GATEWAY'){
                $data = DB::table('CPR_MST_GATEWAY')->where('ID',$id)->select(['GATEWAY_NAME as NAME','ANTENNA_SIZE','LONGITUDE','LATITTUDE as LATTITUDE'])->first();
                $data->LONGITUDE = str_replace(",",".",$data->LONGITUDE);
                $data->LATTITUDE = str_replace(",",".",$data->LATTITUDE);
            }
            if ($data) {
                return response()->json([
                    'message' => 'success',
                    'status' => 200,
                    'data' => $data,
                ]);
            }else {
                return response()->json([
                    'message' => 'failed',
                    'status' => 404
                ]);
            }
        } else {
            return $this->unauthorize();
        }
    }

    public function calculate(Request $request){
        // return $instCity = DB::connection('second_db')->table('cpr_trx_sun_outage')->get();
        $terminal = Terminal::all();
        $terminalArray = json_decode($terminal, true);
        $year = 2024;
        $insert =[
            'musim' => 0,
            'maret' => 0,
            'september' => 0,
            'aneh' => 0
        ];
        for ($y = 0; $y < 5; $y++) { 
            for ($i=0; $i < count($terminalArray); $i++) { 
            // for ($i=0; $i < 1; $i++) { 
                for ($p=0; $p < 2; $p++) { 
                    // $season = 'March';
                    if($p == 0){
                        $season = 'March';
                        $insert['maret']++;
                    }elseif($p == 1){
                        $season = 'September';
                        $insert['september']++;
                    } else{
                        $insert['aneh']++;
                    }
                    $diameters = 0.98;
                    $timezone = 'WIB';
                    // $Lati = '-6.2249106';
                    // $Longi = '106.6984266';
                    $Lati = $terminalArray[$i]['LATITUDE'];
                    $Longi = $terminalArray[$i]['LONGITUDE'];
                    $remarks = $terminalArray[$i]['BEAM'];
                    $Sat_Longi = -146;
                    $Out_Num = 0;
                    $Counter = 0;
                    $Epoch_Cons = 0;
                    $TZ = "gmt";
                    $TZOffset = 0;
                    // return $year;
        
                    if($season == 'March'){
                        $season = 'Spring';
                    }else if($season == 'September'){
                        $season = 'Fall';
                    }
        
                    if ($timezone == 'WIB') {
                        $TZ = "WIB";
                        $TZOffset = -7;
                    }
                    if ($timezone == 'WITA') {
                        $TZ = "WITA";
                        $TZOffset = -8;
                    }
                    if ($timezone == 'WIT') {
                        $TZ = "WIT";
                        $TZOffset = -9;
                    }
                    
                    $GS_Lat = $Lati;
                    $GS_Lon = $Longi;
                    $Sat_Longi = $Sat_Longi;
                    
                    $Out_Num = 0;
                    $Counter = 0;
                    $Epoch_Cons = 0;
                    $dayInMils = 60 * 60 * 24 * 1000;
                    $hrInMils = 60 * 60 * 1000;
                    $minInMils = 60 * 1000;
                    $Epoch_date = 0;
                    // return gettype($year);
                    $checkInputData = $this->Check_Inputs($diameters, $year);
                    if($checkInputData !== true) {
                        return response()->json([
                            'status' => 404,
                            'message' => $checkInputData
                        ]);
                    }
                    // return 'masuk';
                    $epochData = $this->Find_Epoch($year, $season, $minInMils);
                    $Epoch_Cons = $epochData['Epoch_Cons'];
                    $Epoch = $epochData['Epoch'];
                    $Epoch_date = $epochData['Epoch_date'];
        
                    $Find_Outage_Angle_Data = $this->Find_Outage_Angle($diameters);
                    if(!$Find_Outage_Angle_Data){
                        return response()->json([
                            'status' => 404,
                            'message' => 'Please check antenna size'
                        ]);
                    } else{
                        $Outage_Angle = $Find_Outage_Angle_Data;
                    }
                    $Find_All_Data = $this->Find_All($Out_Num, $Epoch, $Epoch_Cons, $GS_Lon, $Sat_Longi, $GS_Lat, $Outage_Angle, $Epoch_date, $minInMils, $hrInMils, $dayInMils, $TZOffset, $TZ, $remarks, $season);
                    if($Find_All_Data){
                        $insert['musim']++;
                    }
                }
            }
            $year++;
        }
        return response()->json([
            'message' => 'success',
            'status' => 200,
            'year' => $y,
            'datas' => $insert,
            'datacount' => count($this->outageData),
            'data' => $this->outageData,
        ]);
            
    }

    public function Check_Inputs($diameters, $year) {
    
        if ($diameters < 0.5 || $diameters > 15) {
            return 'Please check antenna size';
        }
        // dd($year < 1980 || $year > 2030);
        if ($year < 1980 || $year > 2030) {
            return 'Please check your Year input';
        }
        return true;
    }
    
    public function Find_Epoch($year, $season, $minInMils) {
        $Epoch_date = Carbon::create(1904, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $now = Carbon::now('UTC'); // Create a UTC Carbon instance
        $timezoneOffsetMinutes = round($now->offsetMinutes - $Epoch_date->offsetMinutes);
        $utcOffsetMilliseconds = $timezoneOffsetMinutes * $minInMils;
        // dd($Epoch_date);
        $mili = (($Epoch_date->timestamp * 1000) - $utcOffsetMilliseconds);
        // dd($mili);
        $Epoch_date->setTimestamp($mili / 1000);
        // dd($Epoch_date);
        // $Epoch_date = $Epoch_date->format('D M d Y H:i:s \G\M\TO');
        $Epoch_date = $Epoch_date;

        $Epoch = ($year - 1904) * 365.25;
        if ($season == 'Spring') {
            $Epoch = $Epoch + 45;
        } else {
            $Epoch = $Epoch + 229;
        }
        $Epoch = floor($Epoch);
        $Epoch_Cons = floor($Epoch);
        return [
            'Epoch_date' => $Epoch_date,
            'Epoch' => $Epoch,
            'Epoch_Cons' => $Epoch_Cons
        ];
    }

    public function Find_Outage_Angle($diameters){
        $freq = 'C';
        $Outage_angle = '';

        // Calculate Outage Angle
        if ($freq == 'C') {
            $Outage_Angle = 11 / 3.95 / $diameters + 0.25;
        }
        if ($freq == 'Ku') {
            $Outage_Angle = 11 / 11.95 / $diameters + 0.25;
        }
        if ($freq == 'Ka') {
            $Outage_Angle = 11 / 20.75 / $diameters + 0.25;
        } else if ($Outage_Angle < 0.27 || $Outage_Angle > 4) {
            return false;
        }
        return $Outage_Angle;
    }

    public function Find_All($Out_Num, $Epoch, $Epoch_Cons, $GS_Lon, $Sat_Longi, $GS_Lat, $Outage_Angle, $Epoch_date, $minInMils, $hrInMils, $dayInMils, $TZOffset, $TZ, $remarks, $season){
        $Found1 = false;
        // strange
        $i = 0;
        while (($Out_Num == 0 || $Found1 == true)) {
            $i++;
            $Find_Out_Data = $this->Find_Out($Epoch, $Epoch_Cons, $GS_Lon, $Sat_Longi, $GS_Lat, $Outage_Angle, $Out_Num, $i, $Epoch_date, $minInMils, $hrInMils, $dayInMils, $TZOffset, $TZ, $remarks, $season);
            $Found1 = $Find_Out_Data['Found1'];
            $Epoch = $Find_Out_Data['Epoch'];
            $Out_Num = $Find_Out_Data['Out_Num'];
        }
        return true;
    }
    
    public function Find_Out($Epoch, $Epoch_Cons, $GS_Lon, $Sat_Longi, $GS_Lat, $Outage_Angle, $Out_Num, $i, $Epoch_date, $minInMils, $hrInMils, $dayInMils, $TZOffset, $TZ, $remarks, $season){
        $Find_Start_Data = $this->Find_Start($Epoch, $GS_Lon, $Sat_Longi, $GS_Lat, $Outage_Angle, $i);
        $Found1 = $Find_Start_Data['Found1'];
        $Epoch = $Find_Start_Data['Epoch'];
        $Begin_Outage = $Find_Start_Data['Begin_Outage'];
        // dd($Epoch);
        if ($Found1) {
            // dd($i);
            $Out_Num = $Out_Num + 1;
            $Find_End_Data = $this->Find_End($Begin_Outage, $Sat_Longi, $GS_Lon, $GS_Lat, $Outage_Angle);
            $End_Outage = $Find_End_Data['End_Outage'];
            // dd($End_Outage);
            $Write_Out_Data = $this->Write_Out($Begin_Outage, $End_Outage, $Epoch_date, $minInMils, $hrInMils, $dayInMils, $TZOffset, $TZ, $remarks, $season);
            $Epoch = $End_Outage + 0.9;
            // $Find_Begin_Angle();
            $Find_Begin_Angle_Data = $this->Find_Begin_Angle($Begin_Outage, $GS_Lon, $Sat_Longi, $GS_Lat);
            $Sun_Dec = $Find_Begin_Angle_Data['Sun_Dec'];
            $SC_Dec = $Find_Begin_Angle_Data['SC_Dec'];
            $Begin_Angle = $Find_Begin_Angle_Data['Begin_Angle'];
        }
        if ($Epoch - $Epoch_Cons > 15 && $Out_Num == 0) {
            //alert("Something may be wrong... No Outages Found...")
            // $fl = "Something may be wrong... No Outages Found... $i";
            // $fl = "Something may be wrong... No Outages Found... $i, $Epoch";
            // dd($fl);
            // buat apa ada ini?
            return [
                'Epoch' => $Epoch,
                'Found1' => $Found1,
                'Out_Num' => $Out_Num,
            ];
        }
        return [
            'Epoch' => $Epoch,
            'Found1' => $Found1,
            'Out_Num' => $Out_Num,
        ];

    }

    public function Find_Start($Epoch, $GS_Lon, $Sat_Longi, $GS_Lat, $Outage_Angle, $i){
        $Begin_Outage = $Epoch;
        $Find_Begin_Angle_Data = $this->Find_Begin_Angle($Begin_Outage, $GS_Lon, $Sat_Longi, $GS_Lat);
        $Sun_Dec = $Find_Begin_Angle_Data['Sun_Dec'];
        $SC_Dec = $Find_Begin_Angle_Data['SC_Dec'];
        $Begin_Angle = $Find_Begin_Angle_Data['Begin_Angle'];
        $ref_Epoch = $Epoch;
        $Found1 = false;
        while ($Begin_Angle > $Outage_Angle && ($Begin_Outage - $ref_Epoch) < 1) {
            $Y_Angle = abs($SC_Dec - $Sun_Dec);
            // dd($Y_Angle);
            if (($Y_Angle) > (1 + $Outage_Angle / 2)) {
                // dd($Outage_Angle);
                $Begin_Outage = $Begin_Outage + ($Y_Angle * 0.5);
                $Find_Begin_Angle_Data = $this->Find_Begin_Angle($Begin_Outage, $GS_Lon, $Sat_Longi, $GS_Lat);
                // dd($Find_Begin_Angle_Data);
                $Sun_Dec = $Find_Begin_Angle_Data['Sun_Dec'];
                $SC_Dec = $Find_Begin_Angle_Data['SC_Dec'];
                $Begin_Angle = $Find_Begin_Angle_Data['Begin_Angle'];
            }
            if ($Begin_Angle > ($Outage_Angle * 4)) {
                $Begin_Outage = $Begin_Outage + ($Begin_Angle / 540);
                $Find_Begin_Angle_Data = $this->Find_Begin_Angle($Begin_Outage, $GS_Lon, $Sat_Longi, $GS_Lat);
                // dd($Find_Begin_Angle_Data);
                $Sun_Dec = $Find_Begin_Angle_Data['Sun_Dec'];
                $SC_Dec = $Find_Begin_Angle_Data['SC_Dec'];
                $Begin_Angle = $Find_Begin_Angle_Data['Begin_Angle'];
            }
            $Begin_Outage = $Begin_Outage + 0.00005787;
            $Find_Begin_Angle_Data = $this->Find_Begin_Angle($Begin_Outage, $GS_Lon, $Sat_Longi, $GS_Lat);
            // dd($Find_Begin_Angle_Data);
            $Sun_Dec = $Find_Begin_Angle_Data['Sun_Dec'];
            $SC_Dec = $Find_Begin_Angle_Data['SC_Dec'];
            $Begin_Angle = $Find_Begin_Angle_Data['Begin_Angle'];
        }
        $Epoch = $Begin_Outage;
        // dd($Begin_Angle < $Outage_Angle);
        if ($Begin_Angle < $Outage_Angle) {
            $Found1 = true;
            // $tes = "$Found1, udah bisa, $i, $Epoch";
            // dd($tes);
        }
        return [
            'Epoch' => $Epoch,
            'Found1' => $Found1,
            'Begin_Outage' => $Begin_Outage,
            // 'Epoch' => $Epoch,
        ];
    }

    public function Find_Begin_Angle($Begin_Outage, $GS_Lon, $Sat_Longi, $GS_Lat){
        $A26 = $Begin_Outage;
        // dd($A26);
        // debug("F_B_A: Begin_Outage: " . $Begin_Outage);
        $D26 = $A26 + 1460.5;
        $E26 = ($D26) / 36525;
        $F26 = fmod(99.81355 + 0.9856474 * ($A26 - 27759) + 360 * (($A26 - 27759) - floor($A26 - 27759)), 360);
        // dd($F26);
        $G26 = 0.016751 - (0.000042 * $E26);
        $H26 = 358.4759 + (35999.0498 * $E26);
        $I26 = 279.6964 + (36000.769 * $E26);
        $L26 = 23.4523 - (0.013 * $E26);
        // ini beda
        $J26 = (2 * $G26 * sin($H26 * pi() / 180) + 1.25 * $G26 * $G26 * sin($H26 * pi() / 90)) * (180 / pi());
        $K26 = $I26 + $J26;
        // ini beda
        $O26 = 149504200 * (1 - $G26 * cos($H26 * pi() / 180) - $G26 * $G26 / 2 * (cos($H26 * pi() / 90) - 1));
        
        // This formula is broken up because of possible errors with atan2...
        
        $M26b = cos($K26 * (pi() / 180));
        $M26c = (cos($L26 * pi() / 180) * sin($K26 * (pi() / 180)));
        $M26a = atan2($M26c, $M26b);
        $M26 = fmod(($M26a * 180 / pi() + 360), 360);
        $N26 = atan(tan($L26 * pi() / 180) * sin($M26 * pi() / 180)) * 180 / pi();
        $Q26 = fmod(($F26 - $GS_Lon + 360), 360);
        $P26 = fmod(($F26 - $Sat_Longi + 360), 360);
        $S26 = sin($M26 * (pi() / 180)) * cos($N26 * pi() / 180) * $O26 - sin($Q26 * pi() / 180) * cos($GS_Lat * pi() / 180) * 6380;
        $T26 = sin($N26 * (pi() / 180)) * $O26 - sin($GS_Lat * pi() / 180) * 6380;
        $R26 = cos($M26 * (pi() / 180)) * cos($N26 * pi() / 180) * $O26 - cos($Q26 * pi() / 180) * cos($GS_Lat * pi() / 180) * 6380;
        $X26 = (sin($P26 * (pi() / 180)) * 42164) - (sin($Q26 * (pi() / 180)) * cos($GS_Lat * pi() / 180) * 6380);
        $W26 = cos($P26 * pi() / 180) * 42165 - cos($Q26 * pi() / 180) * cos($GS_Lat * pi() / 180) * 6380;
        $Y26 = -sin($GS_Lat * pi() / 180) * 6380;
        $U26 = fmod((atan2($S26, $R26) * 180 / pi() + 360), 360);
        $Z26 = fmod((atan2($X26, $W26) * 180 / pi() + 360), 360);
        $V26 = atan($T26 / sqrt($R26 * $R26 + $S26 * $S26)) * 180 / pi();
        $AA26 = atan($Y26 / sqrt($W26 * $W26 + $X26 * $X26)) * (180 / pi());
        $Sun_Dec = $V26;
        $SC_Dec = $AA26;
        // dd($SC_Dec);
    
        $Begin_Angle = acos(sin($AA26 * pi() / 180) * sin($V26 * pi() / 180) + cos($AA26 * pi() / 180) * cos($V26 * pi() / 180) * cos(($Z26 - $U26) * pi() / 180)) * 180 / pi();
        return [
            'Sun_Dec' => $Sun_Dec,
            'SC_Dec' => $SC_Dec,
            'Begin_Angle' => $Begin_Angle,
        ];
        // dd($Begin_Angle);
    
    }

    function Find_End($Begin_Outage, $Sat_Longi, $GS_Lon, $GS_Lat, $Outage_Angle) {
        $End_Outage = $Begin_Outage;
        // dd($End_Outage);
        $Find_End_Angle_Data = $this->Find_End_Angle($End_Outage, $Sat_Longi, $GS_Lon, $GS_Lat);
        $End_Angle = $Find_End_Angle_Data['End_Angle'];
        // dd($Outage_Angle);
        $p = 0;
        while ($End_Angle < $Outage_Angle) {
            $p++;
            $End_Outage = $End_Outage + 0.00005787;
            $Find_End_Angle_Data = $this->Find_End_Angle($End_Outage, $Sat_Longi, $GS_Lon, $GS_Lat);
            $End_Angle = $Find_End_Angle_Data['End_Angle'];
        }
        $End_Outage = $End_Outage - 0.00005787;
        $Find_End_Angle_Data = $this->Find_End_Angle($End_Outage, $Sat_Longi, $GS_Lon, $GS_Lat);
        $End_Angle = $Find_End_Angle_Data['End_Angle'];
        // $te = "berhasil baru, $p, $End_Outage";
        // dd($te);
        return [
            'End_Outage' => $End_Outage,
        ];
    }

    function Find_End_Angle($End_Outage, $Sat_Longi, $GS_Lon, $GS_Lat) {
        $A27 = $End_Outage;
        $D27 = $A27 + 1460.5;
        $E27 = ($D27) / 36525;
        $F27 = fmod((99.81355 + 0.9856474 * ($A27 - 27759) + 360 * (($A27 - 27759) - floor($A27 - 27759))), 360);
        $G27 = 0.016751 - (0.000042 * $E27);
        $H27 = 358.4759 + (35999.0498 * $E27);
        $I27 = 279.6964 + (36000.769 * $E27);
        $L27 = 23.4523 - (0.013 * $E27);
        $J27 = (2 * $G27 * sin($H27 * pi() / 180) + 1.25 * $G27 * $G27 * sin($H27 * pi() / 90)) * (180 / pi());
        $K27 = $I27 + $J27;
        $O27 = 149504200 * (1 - $G27 * cos($H27 * pi() / 180) - $G27 * $G27 / 2 * (cos($H27 * pi() / 90) - 1));

        // This formula is broken up because of possible errors with atan2...

        $M27b = cos($K27 * (pi() / 180));
        $M27c = (cos($L27 * pi() / 180) * sin($K27 * pi() / 180));
        $M27a = atan2($M27c, $M27b);
        $M27 = fmod(($M27a * 180 / pi() + 360), 360);
        $N27 = atan(tan($L27 * pi() / 180) * sin($M27 * pi() / 180)) * 180 / pi();
        $Q27 = fmod(($F27 - $GS_Lon + 360), 360);
        $P27 = fmod(($F27 - $Sat_Longi + 360), 360);
        $S27 = sin($M27 * (pi() / 180)) * cos($N27 * pi() / 180) * $O27 - sin($Q27 * pi() / 180) * cos($GS_Lat * pi() / 180) * 6380;
        $T27 = sin($N27 * (pi() / 180)) * $O27 - sin($GS_Lat * pi() / 180) * 6380;
        $R27 = cos($M27 * (pi() / 180)) * cos($N27 * pi() / 180) * $O27 - cos($Q27 * pi() / 180) * cos($GS_Lat * pi() / 180) * 6380;
        $X27 = (sin($P27 * (pi() / 180)) * 42164) - (sin($Q27 * (pi() / 180)) * cos($GS_Lat * pi() / 180) * 6380);
        $W27 = cos($P27 * pi() / 180) * 42165 - cos($Q27 * pi() / 180) * cos($GS_Lat * pi() / 180) * 6380;
        $Y27 = -sin($GS_Lat * pi() / 180) * 6380;
        $U27 = fmod((atan2($S27, $R27) * 180 / pi() + 360), 360);
        $Z27 = fmod((atan2($X27, $W27) * 180 / pi() + 360), 360);
        $V27 = atan($T27 / sqrt($R27 * $R27 + $S27 * $S27)) * 180 / pi();
        $AA27 = atan($Y27 / sqrt($W27 * $W27 + $X27 * $X27)) * 180 / pi();

        $End_Angle = acos(sin($AA27 * pi() / 180) * sin($V27 * pi() / 180) + cos($AA27 * pi() / 180) * cos($V27 * pi() / 180) * cos(($Z27 - $U27) * pi() / 180)) * 180 / pi();
        // $tess = "ini, $End_Angle";
        // dd($tess);

        return [
            'End_Angle' => $End_Angle
        ];
    }

    function Write_Out($Begin_Outage, $End_Outage, $Epoch_date, $minInMils, $hrInMils, $dayInMils, $TZOffset, $TZ, $remarks, $season) {

        $Begin_date = Carbon::now();
        $resultBD = (($Epoch_date->timestamp * 1000) + ($Begin_Outage * $dayInMils));
        $Begin_date->setTimestamp($resultBD/1000);
        $Begin_date = $Begin_date;
        
        $End_date = Carbon::now();
        $resultED = (($Epoch_date->timestamp * 1000) + ($End_Outage * $dayInMils));
        $End_date->setTimestamp($resultED/1000);
        $End_date = $End_date;
        
        $Date_Text = $this->formatDate($Begin_date, $minInMils);
        $Begin_Time = $this->formatTime($Begin_date);
        
        $End_Time = $this->formatTime($End_date);
        
        $Duration_Time = $this->formatDuration($Begin_date, $End_date);
        
        $TZBegin_date = Carbon::now();
        $TZEnd_date = Carbon::now();
        $resultTZBD = (($Begin_date->timestamp * 1000) - ($TZOffset * $hrInMils));
        $TZBegin_date->setTimestamp($resultTZBD/1000);
        $resultTZED = (($End_date->timestamp * 1000) - ($TZOffset * $hrInMils));
        $TZEnd_date->setTimestamp($resultTZED/1000);

        $TZBegin_Time = $this->formatTime($TZBegin_date);

        $TZEnd_Time = $this->formatTime($TZEnd_date);

        // $Date_Text = Carbon::createFromFormat('m/d/Y', $Date_Text)->format('Y-m-d');
        // $temp = $Date_Text . ' ' . $Begin_Time;
        // $Begin_Time = Carbon::createFromFormat('Y-m-d H:i:s', $temp)->format('Y-m-d H:i:s');
        // $temp1 = $Date_Text . ' ' . $End_Time;
        // $End_Time = Carbon::createFromFormat('Y-m-d H:i:s', $temp1)->format('Y-m-d H:i:s');
        // $temp2 = $Date_Text . ' ' . $TZBegin_Time;
        // $TZBegin_Time = Carbon::createFromFormat('Y-m-d H:i:s', $temp2)->format('Y-m-d H:i:s');
        // $temp3 = $Date_Text . ' ' . $TZEnd_Time;
        // $TZEnd_Time = Carbon::createFromFormat('Y-m-d H:i:s', $temp3)->format('Y-m-d H:i:s');

        // $data = [
        //     // 'CPR_MST_SUBSCRIBER_ID' => $Date_Text,
        //     'PRED_OUTAGE_DATE' => $Date_Text,
        //     'START_WIB' => $TZBegin_Time,
        //     'END_WIB' => $TZEnd_Time,
        //     'DURATION' => $Duration_Time,
        //     'REMARKS' => $remarks,
        //     'CREATED_BY' => 'SOFYAN',
        //     'DATE_CREATED' => NOW(),
        //     'MODIFIED_BY' => 'SOFYAN',
        //     'DATE_MODIFIED' => NOW(),
        //     'START_GMT' => $Begin_Time,
        //     'END_GMT' => $End_Time,
        // ];
        // $instCity = DB::connection('second_db')->table('cpr_trx_sun_outage')->insert($data);
        
        $data = [
            'Season' => $season,
            'Predicted_Outage_Date' => $Date_Text,
            'Start_GMT' => $Begin_Time,
            'End_GMT' => $End_Time,
            'Duration' => $Duration_Time,
            'Start' => $TZBegin_Time . ' ' . $TZ,
            'End' => $TZEnd_Time . ' ' . $TZ,
        ];
        $this->outageData[] = $data;
        // return $data;
        // DD($data);
        // $tes = $this->outageData[] = $data;
        // dd($tes);
    }

    function formatDate($Begin_date, $minInMils) {
        $thisDate = Carbon::now();
        $now = Carbon::now('UTC');
        $timezoneOffsetMinutes = round($now->offsetMinutes - $Begin_date->offsetMinutes);
        $resultTD = (($Begin_date->timestamp * 1000) + ($timezoneOffsetMinutes * $minInMils));
        $thisDate = $thisDate->setTimestamp($resultTD/1000);
        
        $currYear = $thisDate->year;
        $currMonth = strval($thisDate->month);
        $currDay = strval($thisDate->day);

        $monthOut = (strlen($currMonth) == 1) ? "0" . $currMonth . "/" : $currMonth . "/";
        $dayOut = (strlen($currDay) == 1) ? "0" . $currDay . "/" : $currDay . "/";
        if($currYear < 1000){
            $currYear += 1900;
        }
        $yrOut = strval($currYear);
        return $monthOut . $dayOut . $yrOut;
    }

    function formatTime($timeIn) {
        $thisTime = Carbon::now();
        $thisTime->setTimestamp($timeIn->timestamp);
        $currHrs = intval($thisTime->format('H'));
        $currMins = intval($thisTime->format('i'));
        $currSecs = $thisTime->format('s');
    
        $isNeg = false;
        $hrsFromUTC = 0;
        $minsFromUTC = 0;

        $now = Carbon::now('UTC');
        $minsFromUTC = round($now->offsetMinutes - $timeIn->offsetMinutes);
        if ($minsFromUTC != 0) {
            $hrsFromUTC = floor($minsFromUTC / 60);
            $minsFromUTC = fmod($minsFromUTC, 60);
        }
    
        $currMins += $minsFromUTC;
        if ($currMins >= 60) {
            $currMins -= 60;
            $currHrs += 1;
        }
        if ($currMins < 0) {
            $currMins += 60;
            $currHrs -= 1;
        }
        
        $currHrs += $hrsFromUTC;
        if ($currHrs >= 24){
            $currHrs -= 24;
        }
        if ($currHrs < 0){
            $currHrs += 24;
        }
        
        $currHrs = strval($currHrs);
        $currMins = strval($currMins);
        $hrOut = (strlen($currHrs) == 1) ? "0" . $currHrs . ":" : $currHrs . ":";
        $minOut = (strlen($currMins) == 1) ? "0" . $currMins . ":" : $currMins . ":";
        $secOut = (strlen($currSecs) == 1) ? "0" . $currSecs : $currSecs;
        
        return $hrOut . $minOut . $secOut;
    }

    function formatDuration($startTime, $endTime) {
        $startMins = intval($startTime->format('i'));
        $startSecs = intval($startTime->format('s'));
        $endMins = intval($endTime->format('i'));
        $endSecs = intval($endTime->format('s'));
        $minOffset = 0;
        // dd($startMins, $startSecs, $endMins, $endSecs, $minOffset);

        $durationSecs = $endSecs - $startSecs;
        if ($durationSecs < 0) {
            $durationSecs += 60;
            $minOffset = 1;
        }
        $durationMins = $endMins - $startMins - $minOffset;
        if ($durationMins < 0) {
            $durationMins += 60;
        }
    
        $durationMins = strval($durationMins);
        $durationSecs = strval($durationSecs);
        $durationMins = (strlen($durationMins) == 1) ? "0" . $durationMins . ":" : $durationMins . ":";
        $durationSecs = (strlen($durationSecs) == 1) ? "0" . $durationSecs : $durationSecs;

        return $durationMins . $durationSecs;
    }
    
    
    
}
