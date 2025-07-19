<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create S3 access credentials table.
 *
 * This migration is used to create the s3_access_credentials table
 */
class CreateS3AccessCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('s3_access_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('access_key_id')->unique();
            $table->text('secret_access_key');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s3_access_credentials');
    }
}
