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
            $table->string('phone', 20)->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('address_line_1')->nullable()->after('date_of_birth');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip_code', 10)->nullable()->after('state');
            $table->string('emergency_contact_name')->nullable()->after('zip_code');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'zip_code',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
