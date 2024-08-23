<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesController;
use App\Http\Controllers\TryController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\SerProvController;
use App\Http\Controllers\GeocodesController;
use App\Http\Controllers\UserTestController;
use App\Http\Controllers\SunOutageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::get('/updateGridMap', [TestController::class, 'updateGridMap']);
Route::get('/mistake', [DesController::class, 'mistake']);
Route::get('/delDup', [DesController::class, 'delDup']);
Route::get('/testCreate', [DesController::class, 'testCreate']);
Route::get('/testDestroy', [DesController::class, 'testDestroy']);
Route::get('/insGridMap', [TestController::class, 'insGridMap']);
Route::get('/insSubdistrict', [TestController::class, 'insSubdistrict']);
Route::get('/insDistrict', [TestController::class, 'insDistrict']);
Route::get('/insCity', [TestController::class, 'insCity']);
Route::get('/insAll', [TestController::class, 'insAll']);
Route::get('/sdb', [TestController::class, 'sdb']);
Route::get('/testing', [TestController::class, 'testing']);
Route::get('/checkD', [GeocodeController::class, 'checkD']);
Route::get('/csert', [GeocodeController::class, 'csert']);
Route::get('/amount', [GeocodeController::class, 'amount']);
Route::get('/similarity', [GeocodeController::class, 'similarity']);
Route::post('/getAddress', [GeocodeController::class, 'getAddress']);
Route::get('/get-district-subdistrict', [GeocodeController::class, 'getData']);
// Route::get('/get-district-subdistrict', [GeocodeController::class, 'getDistrictSubdistrict']);
Route::get('/solveStrange', [GeocodesController::class, 'getData']);
// Route::get('/solveStrange', [GeocodesController::class, 'getDistrictSubdistrict']);
Route::post('/try', [TryController::class, 'index']);
Route::post('sun-outage-prediction', [SunOutageController::class, 'calculate']);
Route::post('insertSerprovL', [SerProvController::class, 'insertSerprovL']);
Route::get('createUserTest', [UserTestController::class, 'index']);
