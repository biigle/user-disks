<?php

namespace Biigle\Modules\UserDisks;

use Biigle\Modules\UserDisks\Database\Factories\UserDiskFactory;
use Biigle\User;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class UserDisk extends Model
{
    use HasFactory;

    /**
     * Endpoint URL for dCache token exchange and refresh.
     */
    const DCACHE_TOKEN_ENDPOINT = "https://keycloak.desy.de/auth/realms/production/protocol/openid-connect/token";

    /**
     * Map of type key to type name/description.
     */
    const TYPES = [
        's3' => 'S3',
        'webdav' => 'WebDAV',
        'elements' => 'Elements',
        'aruna' => 'Aruna',
        'dcache' => 'dCache',
        'azure' => 'Azure Blob Storage',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'user_id',
        'options',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'encrypted:array',
        'expires_at' => 'datetime',
    ];

    /**
     * Return the storage disk config template associated with the disk type,
     *
     * @param string $type
     *
     * @return array
     */
    public static function getConfigTemplate($type)
    {
        return config("user_disks.templates.{$type}");
    }

    /**
     * Return the validation rules to create a disk with a specific type.
     *
     * @param string $type
     *
     * @return array
     */
    public static function getStoreValidationRules($type)
    {
        return config("user_disks.store_validation.{$type}");
    }

    /**
     * Return the validation rules to update a disk with a specific type.
     *
     * @param string $type
     *
     * @return array
     */
    public static function getUpdateValidationRules($type)
    {
        return config("user_disks.update_validation.{$type}");
    }

    /**
     * Check whether the disk is about to expire.
     *
     * @return boolean
     */
    public function isAboutToExpire()
    {
        return $this->expires_at < now()->addWeeks(config('user_disks.about_to_expire_weeks'));
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserDiskFactory::new();
    }

    /**
     * The user who owns the disk.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the filesystem disk configuration array of this user disk.
     *
     * @return array
     */
    public function getConfig()
    {
        return array_merge(static::getConfigTemplate($this->type), $this->options, [
            'read-only' => true,
        ]);
    }

    /**
     * Extend the expiration date of the disk.
     */
    public function extend()
    {
        $this->update(['expires_at' => now()->addMonths(config('user_disks.expires_months'))]);
    }

    /**
     * Check if the dcache access token is about to expire (within 1 minute) or already
     * expired.
     *
     * @return bool
     */
    public function isDCacheAccessTokenExpiring()
    {
        if ($this->type !== 'dcache') {
            return false;
        }

        $tokenExpiresAt = $this->options['token_expires_at'] ?? null;

        if (is_null($tokenExpiresAt)) {
            return false;
        }

        return Carbon::parse($tokenExpiresAt) <= now()->addMinute();
    }

    /**
     * Check if the dcache refresh token is about to expire (within 2 hours).
     *
     * @return bool
     */
    public function isDCacheRefreshTokenExpiring()
    {
        if ($this->type !== 'dcache') {
            return false;
        }

        $refreshTokenExpiresAt = $this->options['refresh_token_expires_at'] ?? null;

        if (is_null($refreshTokenExpiresAt)) {
            return false;
        }

        return Carbon::parse($refreshTokenExpiresAt) <= now()->addHours(2);
    }

    /**
     * Refresh the dcache access and refresh tokens.
     *
     * @return bool True if the refresh was successful, false otherwise
     */
    public function refreshDCacheToken()
    {
        $refreshToken = $this->options['refresh_token'] ?? null;

        if (!$refreshToken) {
            return false;
        }

        $postData = [
            'client_id' => config('user_disks.dcache-token-exchange.client_id'),
            'client_secret' => config('user_disks.dcache-token-exchange.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            // This has to exactly match the scope of the refresh token!
            // TODO Auth still fails after token refresh. Missing scopes?
            'scope' => 'acr roles entitlements groups web-origins openid basic token-exchange profile email',
        ];

        try {
            $response = Http::asForm()->post(static::DCACHE_TOKEN_ENDPOINT, $postData);
        } catch (Exception $e) {
            return false;
        }

        if (!$response->successful()) {
            return false;
        }

        $data = $response->json();

        $options = $this->options;
        $options['token'] = $data['access_token'];
        $options['refresh_token'] = $data['refresh_token'];
        $options['token_expires_at'] = now()->addSeconds($data['expires_in']);
        $options['refresh_token_expires_at'] = now()->addSeconds($data['refresh_expires_in']);

        $this->update(['options' => $options]);

        return true;
    }
}
