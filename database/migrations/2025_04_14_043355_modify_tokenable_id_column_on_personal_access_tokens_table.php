<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTokenableIdColumnOnPersonalAccessTokensTable extends Migration
{
    public function up()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Ubah kolom tokenable_id menjadi string (misalnya char 36)
            $table->char('tokenable_id', 36)->change();
        });
    }

    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Kembalikan ke tipe bigint jika diperlukan
            $table->bigInteger('tokenable_id')->change();
        });
    }
};
