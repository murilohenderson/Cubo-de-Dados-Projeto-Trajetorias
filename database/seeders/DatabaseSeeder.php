<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Region;
use App\Models\MatrixCell;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DataCubeSeeder::class);
    }
}
