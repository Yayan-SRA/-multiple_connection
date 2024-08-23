<?php

namespace App\Jobs;

use Faker\Factory;
use App\Models\UserTest;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UserTestJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $faker = Factory::create();
        $jumlahData = 10000;
        for ($i=1; $i <= $jumlahData; $i++) { 
            $data = [
                'NAME' => $faker->name(),
                'EMAIL' => $faker->unique()->email()
            ];
            UserTest::Create($data);
        }
    }
}
