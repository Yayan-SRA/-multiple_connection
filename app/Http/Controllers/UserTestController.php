<?php

namespace App\Http\Controllers;

use Faker\Factory;
use App\Models\UserTest;
use App\Jobs\UserTestJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus; // Add this line

class UserTestController extends Controller
{
    public function index(){
        // $faker = Factory::create();
        // $jumlahData = 10000;
        // for ($i=1; $i < $jumlahData; $i++) { 
        //     $data = [
        //         'NAME' => $faker->name(),
        //         'EMAIL' => $faker->unique()->email()
        //     ];
        //     UserTest::Create($data);
        // }
        $job = new UserTestJobs();
        // $this->dispatch($job);
        Bus::dispatch($job); // Use the Bus facade
        return 'yeay done';
    }

}
