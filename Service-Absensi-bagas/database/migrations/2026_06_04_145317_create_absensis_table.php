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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('karyawan_id')->comment('ID karyawan dari Service Data Karyawan');
            $table->string('nama_karyawan');
            $table->integer('bulan')->comment('Bulan 1-12');
            $table->integer('tahun')->comment('Tahun, contoh: 2026');
            $table->integer('total_hadir')->default(0);
            $table->integer('total_sakit')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_alpha')->default(0);
            $table->integer('total_hari_kerja')->default(22);
            $table->timestamps();

            // Unique constraint: satu karyawan hanya punya 1 rekap per bulan per tahun
            $table->unique(['karyawan_id', 'bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
