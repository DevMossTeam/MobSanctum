<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user', function ($table) {
            $table->string('uid', 28)->primary();
            $table->string('nama_pengguna', 60);
            $table->string('password', 100);
            $table->string('email', 100);
            $table->enum('role', ['Penulis', 'Pembaca', 'Admin']);
            $table->string('nama_lengkap', 100);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE user ADD profile_pic MEDIUMBLOB NULL");
    }

    public function down()
    {
        Schema::dropIfExists('user');
    }
};
