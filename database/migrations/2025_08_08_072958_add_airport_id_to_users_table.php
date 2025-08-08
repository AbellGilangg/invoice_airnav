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
            // Tambahkan foreign key ke tabel airports
            // Dibuat nullable() agar akun master tidak wajib punya bandara
            $table->foreignId('airport_id')->nullable()->after('role')->constrained('airports')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['airport_id']);
            $table->dropColumn('airport_id');
        });
    }
};