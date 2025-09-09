<?php

declare(strict_types=1);

namespace Modules\V1\User\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Modules\V1\Auth\Notifications\ResetPassword;
use Modules\V1\Auth\Notifications\VerifyEmailAddress;
use Shared\Helpers\GlobalHelper;
use Shared\Services\UserService;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, UserService, SoftDeletes;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    public string $prefix = 'HOA';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'provider_id',
        'provider_type',
        'oauth',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the currently authenticated user.
     */
    public static function active(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return Auth::user();
    }

    public function sendEmailVerificationNotification(): void
    {
        $verificationToken = $this->createEmailVerificationToken();
        $this->notify(new VerifyEmailAddress($this, $verificationToken));
    }

    public function sendPasswordResetNotification($token = ''): void
    {
        $token = $this->createVerificationToken();
        $link = config('constants.reset_password') . $token;
        $this->notify(new ResetPassword($this, $link));

    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
