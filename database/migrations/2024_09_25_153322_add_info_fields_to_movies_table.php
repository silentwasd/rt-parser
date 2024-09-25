<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->bigInteger('year_from')->nullable()->unsigned()->after('cover_id');
            $table->bigInteger('year_to')->nullable()->unsigned()->after('year_from');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['year_from', 'year_to']);
        });
    }
};
