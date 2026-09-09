<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            "Registrar's Office",
            'Student Affairs',
            'Facilities Office',
            'Guidance Office',
            'Accounting Office',
        ])->each(fn (string $name) => Office::firstOrCreate(['name' => $name]));
    }
}
