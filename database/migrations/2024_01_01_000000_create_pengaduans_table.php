<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel log pengaduan lokal — digunakan sebagai cadangan audit trail
 * jika SOAP backend tidak dapat dijangkau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduans', function (Blueprint $table) {
            $table->id();
            $table->string('id_pengaduan')->nullable()->comment('ID pengaduan dari SOAP backend');
            $table->string('nama_pelapor', 100);
            $table->string('nomor_telepon', 20);
            $table->string('nomor_pelanggan', 20)->nullable();
            $table->string('alamat', 250);
            $table->unsignedSmallInteger('kecamatan_id');
            $table->unsignedSmallInteger('desa_id');
            $table->unsignedTinyInteger('jenis_pengaduan');
            $table->text('deskripsi');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 50)->default('Inputed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduans');
    }
};
