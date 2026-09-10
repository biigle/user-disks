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
     * Check if the HAAI refresh token is about to expire (within 2 hours).
     *
     * @return bool
     */
    public function isDCacheRefreshTokenExpiring()
    {
        if ($this->type !== 'dcache') {
            return false;
        }

        $refreshTokenExpiresAt = $this->options['haai_refresh_token_expires_at'] ?? null;

        // Offline tokens have no expiration date.
        if (is_null($refreshTokenExpiresAt)) {
            return false;
        }

        return Carbon::parse($refreshTokenExpiresAt) <= now()->addHours(2);
    }

    /**
     * Obtain a new dCache access token with a HAAI access token.
     *
     * @param string $haaiAccessToken
     * @throws Exception If any step of the token chain fails
     * @return array Disk options for the new dCache access token
     */
    public static function getDCacheTokenOptions($haaiAccessToken)
    {
        // Step 1: Exchange the HAAI token for one addressed to dCache Keycloak.
        // The audience claim in the resulting token must match the Keycloak
        // realm so Keycloak accepts it in the JWT Authorization Grant (step 2).
        // The HAAI token endpoint expects the client credentials in the
        // Authorization header (see the biigle/laravel-socialite-haai provider).
        $response = Http::asForm()
            ->withBasicAuth(
                config('services.haai.client_id'),
                config('services.haai.client_secret')
            )
            ->post(config('user_disks.dcache-token-exchange.helmholtz_token_endpoint'), [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
                'subject_token' => $haaiAccessToken,
                'subject_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
                'requested_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
                'audience' => config('user_disks.dcache-token-exchange.keycloak_audience'),
                'scope' => 'openid profile email token-exchange',
            ])->throw();

        $intermediateToken = $response->json('access_token');

        if (!$intermediateToken) {
            // Don't include the response body because it may contain other tokens.
            throw new Exception('The HAAI token exchange response contained no access token.');
        }

        // Step 2: JWT Authorization Grant: present the Keycloak-addressed token to
        // dCache Keycloak to obtain the dCache access token. This grant never issues
        // a refresh token (it always creates a transient session), so the HAAI
        // offline token is kept to run this chain again.
        $response = Http::asForm()
            ->post(config('user_disks.dcache-token-exchange.token_endpoint'), [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $intermediateToken,
                'client_id' => config('user_disks.dcache-token-exchange.client_id'),
                'client_secret' => config('user_disks.dcache-token-exchange.client_secret'),
                // Setting the scope here is critical, otherwise the scope will be reset
                // to the default scope (and the token will no longer work for dCache).
                'scope' => 'openid profile email',
            ])->throw();

        $data = $response->json();

        return [
            'token' => $data['access_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in']),
        ];
    }

    /**
     * Refresh the dcache access token with the HAAI offline token.
     *
     * @return bool True if the refresh was successful, false otherwise
     */
    public function refreshDCacheToken()
    {
        $refreshToken = $this->options['haai_refresh_token'] ?? null;

        if (!$refreshToken) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.haai.client_id'),
                    config('services.haai.client_secret')
                )
                ->post(config('user_disks.dcache-token-exchange.helmholtz_token_endpoint'), [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ])->throw();

            $data = $response->json();
            $options = array_merge(
                $this->options,
                static::getDCacheTokenOptions($data['access_token'])
            );
        } catch (Exception $e) {
            return false;
        }

        $options['haai_refresh_token'] = $data['refresh_token'];
        // Offline tokens have no expiration date, in which case refresh_expires_in is 0.
        $options['haai_refresh_token_expires_at'] = ($data['refresh_expires_in'] ?? 0) > 0
            ? now()->addSeconds($data['refresh_expires_in'])
            : null;

        $this->update(['options' => $options]);

        return true;
    }
}
