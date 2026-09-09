<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Office;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OfficeSeeder::class,
            RequestTypeSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Alex Santos',
            'student_id' => '2023-04521',
            'email' => 'alex.santos@example.edu',
        ]);

        User::firstOrCreate(
            ['email' => 'staff@example.edu'],
            ['name' => 'Jordan Cruz', 'student_id' => 'STAFF-0001', 'role' => 'staff', 'password' => $user->password]
        );

        User::firstOrCreate(
            ['email' => 'admin@example.edu'],
            ['name' => 'Dana Reyes', 'student_id' => 'ADMIN-0001', 'role' => 'admin', 'password' => $user->password]
        );

        $registrar = Office::where('name', "Registrar's Office")->first();
        $studentAffairs = Office::where('name', 'Student Affairs')->first();
        $facilities = Office::where('name', 'Facilities Office')->first();

        $transcript = RequestType::where('name', 'Transcript Request')->first();
        $enrollment = RequestType::where('name', 'Certificate of Enrollment')->first();
        $idReplacement = RequestType::where('name', 'ID Replacement')->first();
        $facilityReservation = RequestType::where('name', 'Facility Reservation')->first();

        $pending = $user->serviceRequests()->create([
            'office_id' => $registrar->id,
            'request_type_id' => $transcript->id,
            'subject' => 'Transcript for scholarship application',
            'details' => 'Official transcript of records for scholarship application, 2 copies.',
            'status' => 'pending',
        ]);

        $completed = $user->serviceRequests()->create([
            'office_id' => $registrar->id,
            'request_type_id' => $enrollment->id,
            'subject' => 'Certificate of enrollment for part-time job',
            'details' => 'Certificate of enrollment for the current semester, for a part-time job application.',
            'status' => 'completed',
        ]);

        $inReview = $user->serviceRequests()->create([
            'office_id' => $studentAffairs->id,
            'request_type_id' => $idReplacement->id,
            'subject' => 'Lost student ID replacement',
            'details' => 'Lost student ID, requesting replacement. Affidavit of loss attached.',
            'status' => 'in_review',
        ]);

        $user->serviceRequests()->create([
            'office_id' => $facilities->id,
            'request_type_id' => $facilityReservation->id,
            'subject' => 'AVR reservation for org assembly',
            'details' => 'Reserve the AVR for an org general assembly, Aug 20, 1-4 PM.',
            'status' => 'completed',
        ]);

        Notification::create([
            'user_id' => $user->id,
            'service_request_id' => $completed->id,
            'title' => 'Certificate of Enrollment is ready',
            'detail' => "Your request has been marked completed by the Registrar's Office.",
            'tone' => 'success',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'service_request_id' => $inReview->id,
            'title' => 'ID Replacement moved to In Review',
            'detail' => 'Student Affairs picked up your request and is verifying your affidavit.',
            'tone' => 'info',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'service_request_id' => $pending->id,
            'title' => 'Transcript Request received',
            'detail' => "Registrar's Office has received your transcript request.",
            'tone' => 'info',
            'read_at' => now(),
        ]);
    }
}
