<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id')->unique()->nullable()->after('name');
            $table->string('program')->nullable()->after('email');
            $table->string('year_level')->nullable()->after('program');
            $table->string('contact_number')->nullable()->after('year_level');
            $table->boolean('notify_email')->default(true)->after('contact_number');
            $table->boolean('notify_sms')->default(false)->after('notify_email');
            $table->boolean('notify_weekly_digest')->default(true)->after('notify_sms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'student_id',
                'program',
                'year_level',
                'contact_number',
                'notify_email',
                'notify_sms',
                'notify_weekly_digest',
            ]);
        });
    }
};
