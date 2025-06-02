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
        Schema::create('blobs', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->string('type')->index()->nullable();
            $table->double('size')->nullable();
            $table->string('ext')->nullable();
            $table->string('name');
            $table->string('directory')->nullable();
            $table->string("base_url")->nullable();
            $table->unsignedInteger('sort_number')->default(0);
            $table->timestamps();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blobs');
    }
};
