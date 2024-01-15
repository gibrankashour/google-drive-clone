<?php

namespace Database\Seeders;

use App\Models\Download;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DownloadsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Download::create([
            'public_file_id' => md5(random_int(1,10)),
            'user_id' => User::first()->id
        ]);
    }
}
