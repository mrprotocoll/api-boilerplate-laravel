<?php

namespace Shared\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Shared\Helpers\GlobalHelper;

trait UserService
{
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
        $token = mb_substr(str_shuffle('0123456789'), 0, 6); // Adjust the length as needed

        // Set token expiry time (e.g., 24 hours from now)
        $expiry = \Illuminate\Support\Carbon::now()->addHours($hours);
        $this->verification_token = $token;
        $this->verification_token_expiry = $expiry;
        $this->save();

        return $token;
    }

    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = Carbon::now();
        $this->verification_token = null;
        $this->verification_token_expiry = null;

        return $this->save();
    }
}
