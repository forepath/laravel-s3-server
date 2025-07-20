<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add bucket to S3 access credentials table.
 *
 * This migration is used to add a bucket field to restrict credentials to specific buckets
 */
class AddBucketToS3AccessCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('s3_access_credentials', function (Blueprint $table) {
            $table->string('bucket')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('s3_access_credentials', function (Blueprint $table) {
            $table->dropColumn('bucket');
        });
    }
}
