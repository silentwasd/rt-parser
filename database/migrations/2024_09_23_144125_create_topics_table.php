<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('original_id')->unsigned();

            $table->string('name');

            $table->foreignId('author_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->bigInteger('size')->unsigned()->default(0);
            $table->integer('seeds')->default(0);
            $table->integer('leeches')->default(0);
            $table->bigInteger('downloads')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
