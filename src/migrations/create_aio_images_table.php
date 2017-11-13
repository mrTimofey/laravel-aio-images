<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAioImagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('aio_images', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->jsonb('props')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aio_images');
    }
}