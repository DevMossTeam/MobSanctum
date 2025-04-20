<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePesanTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesan', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 28)->nullable();
            $table->text('pesan');
            $table->dateTime('created_at');
            $table->enum('status_read', ['sudah', 'belum']);
            $table->enum('status', ['laporan', 'masukan']);
            $table->text('detail_pesan')->nullable();
            $table->string('pesan_type', 255)->nullable();
            $table->string('item_id', 255)->nullable();
            $table->string('nama', 100)->nullable();
            $table->string('email', 100)->nullable();

            // Foreign key ke table user
            $table->foreign('user_id')->references('uid')->on('user')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesan');
    }
}
