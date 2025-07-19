<?php

declare(strict_types=1);

namespace LaravelS3Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * S3 access credential model.
 *
 * This model is used to store the S3 access credentials
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
class S3AccessCredential extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 's3_access_credentials';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'access_key_id',
        'secret_access_key',
        'description',
    ];

    /**
     * Hide secret_access_key from JSON serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret_access_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'secret_access_key' => 'encrypted',
    ];
}
