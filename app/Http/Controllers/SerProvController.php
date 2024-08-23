<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SerProvController extends Controller
{
    public function insertSerprovL(Request $request){
        // return 'masuk';
        try{
            DB::connection('second_db')->beginTransaction();
            // return NOW()->format('d-m-Y H:i:s');
            $data = DB::connection('second_db')->table('CPR_TRX_SUBSCRIBER as SUBS')
                    ->join('CPR_TRX_SUBSCRIBER_IP as IPV4', function($join){
                        $join->on('SUBS.ID', 'IPV4.CPR_TRX_SUBSCRIBER_ID')
                        ->where([['IPV4.VALID_UNTIL', NULL], ['IPV4.IP_LABEL','IPv4']]);
                    })
                    ->join('CPR_TRX_SUBSCRIBER_IP as IPV6', function($join){
                        $join->on('SUBS.ID', 'IPV6.CPR_TRX_SUBSCRIBER_ID')
                        ->where([['IPV6.VALID_UNTIL', NULL], ['IPV6.IP_LABEL','IPv6']]);
                    })
                    ->where([['SUBS.VALID_UNTIL', NULL], ['SUBS.TERMINAL_TYPE', 1], ['SUBS.TIMEZONE_ID', 2]])
                    ->select([
                        'SUBS.ID',
                        'SUBS.SUBSCRIBER_NUMBER',
                        'IPV4.IP_ADDRESS as IPV4_Address',
                        'IPV6.IP_ADDRESS as IPV6_Address',
                        ])
                    ->get();

            $user = 'sofyan.afandy';
            $dateTime = NOW();
            // $requestDatas = [];
            foreach($data as $index => $item){
                $dataSERPROV = [
                    'CODE'  => 1000,
                    'TERMINAL_ID'  => $item->SUBSCRIBER_NUMBER,
                    'STATE' => 1,
                    'REASON'    => 1000,
                    'DIRECT_TO_HUB' => 1,
                    'DATE_CREATED'  => $dateTime,
                    'CREATED_BY' => $user,
                    'REMARKS' => "partial comissioning"
                ];
                $SERPROVID  = DB::connection('second_db')->table('SPR_TRX_REQUESTS')->insertGetId($dataSERPROV);
                $requestData = [
                    ['NAME' => 'TERMINAL_ID', 'VALUE' => $item->SUBSCRIBER_NUMBER],
                    ['NAME' => 'SERVICE_PLAN_ID', 'VALUE' => 'BAKTI_INST'],
                    ['NAME' => 'MODE', 'VALUE' => 4],
                    ['NAME' => 'IP4', 'VALUE' => $item->IPV4_Address],
                    ['NAME' => 'IP6', 'VALUE' => $item->IPV6_Address],
                    ['NAME' => 'VLAN_NAME', 'VALUE' => 'native'],
                    ['NAME' => 'SERVICE_PROVIDER', 'VALUE' => 'SP05'],
                ];
        
                $requestDatas = array_map(function ($item) use ($dateTime, $user, $SERPROVID) {
                    return [
                        'REQ_ID' => $SERPROVID,
                        'DATE_CREATED' => $dateTime,
                        'CREATED_BY' => $user,
                        'VALUE' => $item['VALUE'],
                        'NAME' => $item['NAME'],
                    ];
                }, $requestData);
                $instm = DB::connection('second_db')->table('SPR_TRX_REQUESTS_PARAMS')->insert($requestDatas);
                if($instm){
                    $updtSBC  = DB::connection('second_db')->table('CPR_TRX_SUBSCRIBER')->where([['SUBSCRIBER_NUMBER', $item->SUBSCRIBER_NUMBER],['VALID_UNTIL', NULL], ['TERMINAL_TYPE', 1], ['TIMEZONE_ID', 2]])->update([
                        'TIMEZONE_ID' => NULL,
                        'DATE_MODIFIED' => $dateTime,
                        'MODIFIED_BY' => $user,
                    ]);
                }
            }
            // return $datam = Arr::collapse($requestDatas);
            // $instm = DB::connection('second_db')->table('SPR_TRX_REQUESTS_PARAMS')->insert($datam);
            // DB::commit();
            if($instm){
                DB::connection('second_db')->commit();
                return 'yeay';
            }else{
                DB::connection('second_db')->rollback();
                return 'yahh';
            }

            return true;
        }catch(Exception $e) {
            return $e->getMessage();;
        }
    }
}
