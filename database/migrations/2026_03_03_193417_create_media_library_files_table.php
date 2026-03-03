<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_library_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->unique();         // relative path từ public/
            $table->string('url');                    // full asset URL
            $table->string('extension', 10);
            $table->string('mime_type', 80)->nullable();
            $table->string('context', 20)->default('product'); // product | post
            $table->unsignedBigInteger('size')->default(0);    // bytes
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamp('file_modified_at')->nullable();  // mtime thực của file
            $table->timestamps();

            $table->index(['context', 'created_at']);
            $table->index(['context', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_library_files');
    }
};
