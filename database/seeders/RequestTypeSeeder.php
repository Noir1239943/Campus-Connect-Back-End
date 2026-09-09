<?php

namespace Database\Seeders;

use App\Models\RequestType;
use Illuminate\Database\Seeder;

class RequestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            'Transcript Request',
            'Certificate of Enrollment',
            'Good Moral Certificate',
            'ID Replacement',
            'Grade Correction',
            'Facility Reservation',
            'Maintenance Report',
        ])->each(fn (string $name) => RequestType::firstOrCreate(['name' => $name]));
    }
}
