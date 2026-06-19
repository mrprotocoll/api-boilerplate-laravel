<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Model;

use Illuminate\Database\Eloquent\Model;

final class AccessToken extends Model
{
    protected $table = "personal_access_tokens";

    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'token',
        'refresh_token',
        'abilities',
        'expires_at',
        'refresh_token_expires_at',
    ];

    protected $dates = [
        'expires_at',
        'refresh_token_expires_at',
    ];

    public function tokenable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
