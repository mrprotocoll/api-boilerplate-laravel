<?php

declare(strict_types=1);

namespace Modules\V1\User\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Modules\V1\Auth\Notifications\ResetPassword;
use Modules\V1\Auth\Notifications\VerifyEmailAddress;
use Shared\Helpers\GlobalHelper;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

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

    /**
     * Create a verification token with an optional expiry time.
     *
     * @param  int  $hours  The number of hours until the token expires (default is 24 hours).
     * @return string The encrypted verification token.
     */
    public function createVerificationToken(int $hours = 24): string
    {
        // Generate a unique verification token
        $token = Str::random(64); // Adjust the length as needed

        // Set token expiry time (e.g., 24 hours from now)
        $expiry = Carbon::now()->addHours($hours);

        // Store the token and expiry timestamp in the database
        $this->verification_token = $token;
        $this->verification_token_expiry = $expiry;
        $this->save();

        return GlobalHelper::encrypt($token);
    }

    public function createEmailVerificationToken($hours = 24): string
    {
        // Generate a unique verification token
        $token = substr(str_shuffle("0123456789"), 0, 5); // Adjust the length as needed

        // Set token expiry time (e.g., 24 hours from now)
        $expiry = \Illuminate\Support\Carbon::now()->addHours($hours);
        $this->verification_token = $token;
        $this->verification_token_expiry = $expiry;
        $this->save();

        return $token;
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

    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = Carbon::now();
        $this->verification_token = null;
        $this->verification_token_expiry = null;

        return $this->save();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
