<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Mail\SignupOtpMail;
use App\Models\Beneficiary;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Generate a 6-digit OTP
     */
    public function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP to admin email
     */
    public function sendOtp(string $email): array
    {
        // Check if user exists and is admin
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found with this email address.',
            ];
        }

        if (!$user->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Password reset is only available for admin users.',
            ];
        }

        // Clean up expired OTPs for this email
        $this->cleanupExpiredOtps($email);

        // Check if there's already a valid OTP (rate limiting)
        $existingOtp = Otp::where('email', $email)
            ->valid()
            ->first();

        if ($existingOtp) {
            return [
                'success' => false,
                'message' => 'An OTP has already been sent. Please wait before requesting another one.',
            ];
        }

        // Generate new OTP
        $otp = $this->generateOtp();
        $expiresAt = now()->addMinutes(10); // OTP expires in 10 minutes

        // Store OTP in database
        Otp::create([
            'email' => $email,
            'otp' => $otp,
            'context' => 'password_reset',
            'expires_at' => $expiresAt,
            'is_used' => false,
        ]);

        try {
            // Send OTP via email using SendGrid
            Mail::to($email)->send(new OtpMail($otp, $email));

            return [
                'success' => true,
                'message' => 'OTP sent to your email address successfully.',
            ];
        } catch (\Exception $e) {
            // If email fails, remove the OTP from database
            Otp::where('email', $email)
                ->where('otp', $otp)
                ->delete();

            return [
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.',
            ];
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $email, string $otp): array
    {
        $otpRecord = Otp::where('email', $email)
            ->where('otp', $otp)
            ->valid()
            ->first();

        if (!$otpRecord) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Mark OTP as used
        $otpRecord->markAsUsed();

        // Generate a temporary verification token
        $verificationToken = Str::random(64);

        // Store verification token (you might want to store this in cache or database)
        // For now, we'll return it directly
        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
            'verification_token' => $verificationToken,
        ];
    }

    /**
     * Reset password after OTP verification
     */
    public function resetPassword(string $email, string $otp, string $newPassword): array
    {
        // Verify OTP again
        $otpRecord = Otp::where('email', $email)
            ->where('otp', $otp)
            ->valid()
            ->first();

        if (!$otpRecord) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Get user
        $user = User::where('email', $email)->first();
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        // Update password
        $user->update([
            'password' => bcrypt($newPassword),
        ]);

        // Mark OTP as used
        $otpRecord->markAsUsed();

        // Clean up all OTPs for this email
        $this->cleanupExpiredOtps($email);

        return [
            'success' => true,
            'message' => 'Password reset successfully.',
        ];
    }

    /**
     * Clean up expired OTPs for a specific email
     */
    public function cleanupExpiredOtps(string $email): int
    {
        return Otp::where('email', $email)
            ->expired()
            ->delete();
    }

    /**
     * Clean up all expired OTPs
     */
    public function cleanupAllExpiredOtps(): int
    {
        return Otp::cleanupExpired();
    }

    /* ===================== Signup helpers (donor) ===================== */

    /** Generate a 4-digit OTP for signup */
    public function generateSignupOtp(): string
    {
        return str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /** Send signup OTP, storing all user data in metadata */
    public function sendSignupOtpWithData(string $email, array $userData): array
    {
        // Ensure not already registered
        if (User::where('email', $email)->exists()) {
            return [
                'success' => false,
                'message' => 'Email already in use.',
            ];
        }

        $this->cleanupExpiredOtps($email);

        $existing = Otp::where('email', $email)->forContext('signup')->valid()->first();
        if ($existing) {
            return [
                'success' => false,
                'message' => 'An OTP has already been sent. Please wait before requesting another one.',
            ];
        }

        $otp = $this->generateSignupOtp();
        $expiresAt = now()->addMinutes(10);

        Otp::create([
            'email' => $email,
            'otp' => $otp,
            'context' => 'signup',
            'metadata' => $userData,
            'expires_at' => $expiresAt,
            'is_used' => false,
        ]);

        try {
            Mail::to($email)->send(new SignupOtpMail($otp, $email));
            return [ 'success' => true, 'message' => 'OTP sent to your email address successfully.' ];
        } catch (\Exception $e) {
            Otp::where('email', $email)->where('otp', $otp)->forContext('signup')->delete();
            return [ 'success' => false, 'message' => 'Failed to send OTP. Please try again later.' ];
        }
    }

    /** Verify signup OTP and create user account */
    public function verifySignupOtpAndCreateUser(string $email, string $otp): array
    {
        $record = Otp::where('email', $email)->where('otp', $otp)->forContext('signup')->valid()->first();
        if (!$record) {
            return [ 'success' => false, 'message' => 'Invalid or expired OTP.' ];
        }

        // Get user data from metadata
        $userData = $record->metadata;
        
        // Create user
        $user = User::create([
            'name' => $userData['first_name'] . ' ' . $userData['last_name'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $email,
            'phone_number' => $userData['phone_number'],
            'password' => $userData['password_hash'],
            'role' => User::ROLE_DONOR,
            'email_verified_at' => now(),
        ]);

        // Mark OTP as used and cleanup
        $record->markAsUsed();
        $this->cleanupExpiredOtps($email);

        return [
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role,
                ],
            ],
        ];
    }

    /**
     * Unified: Verify OTP and create account based on context (donor or beneficiary)
     */
    public function verifyOtpAndCreateAccount(string $email, string $otp): array
    {
        $record = Otp::where('email', $email)
            ->where('otp', $otp)
            ->valid()
            ->first();

        if (!$record) {
            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        $context = $record->context;
        if ($context === 'signup') {
            return $this->verifySignupOtpAndCreateUser($email, $otp);
        }

        if ($context === 'signup_beneficiary') {
            return $this->verifyOtpAndCreateBeneficiary($email, $otp);
        }

        return [
            'success' => false,
            'message' => 'Unsupported OTP context.',
        ];
    }

    /* ===================== Generic OTP helpers ===================== */

    /** Send OTP with custom context and metadata */
    public function sendOtpWithData(string $email, string $context, array $metadata): array
    {
        // Ensure not already registered (for signup contexts)
        if (in_array($context, ['signup', 'signup_beneficiary']) && User::where('email', $email)->exists()) {
            return [
                'success' => false,
                'message' => 'Email already in use.',
            ];
        }

        $this->cleanupExpiredOtps($email);

        $existing = Otp::where('email', $email)->forContext($context)->valid()->first();
        if ($existing) {
            return [
                'success' => false,
                'message' => 'An OTP has already been sent. Please wait before requesting another one.',
            ];
        }

        $otp = $this->generateSignupOtp(); // 4-digit OTP for signup flows
        $expiresAt = now()->addMinutes(10);

        Otp::create([
            'email' => $email,
            'otp' => $otp,
            'context' => $context,
            'metadata' => $metadata,
            'expires_at' => $expiresAt,
            'is_used' => false,
        ]);

        try {
            Mail::to($email)->send(new SignupOtpMail($otp, $email));
            return ['success' => true, 'message' => 'OTP sent to your email address successfully.'];
        } catch (\Exception $e) {
            Otp::where('email', $email)->where('otp', $otp)->forContext($context)->delete();
            return ['success' => false, 'message' => 'Failed to send OTP. Please try again later.'];
        }
    }

    /* ===================== Beneficiary signup helpers ===================== */

    /** Verify OTP and create beneficiary account */
    public function verifyOtpAndCreateBeneficiary(string $email, string $otp): array
    {
        $record = Otp::where('email', $email)->where('otp', $otp)->forContext('signup_beneficiary')->valid()->first();
        if (!$record) {
            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        // Get user data from metadata
        $metadata = $record->metadata;

        // Create user
        $user = User::create([
            'name' => $metadata['first_name'] . ' ' . $metadata['last_name'],
            'first_name' => $metadata['first_name'],
            'last_name' => $metadata['last_name'],
            'email' => $email,
            'phone_number' => $metadata['phone_number'],
            'password' => $metadata['password_hash'],
            'role' => $metadata['role'],
            'email_verified_at' => now(),
        ]);

        // Create beneficiary profile
        $beneficiary = Beneficiary::create([
            'user_id' => $user->id,
            'family_size' => $metadata['family_size'] ?? null,
            'address' => $metadata['address'] ?? null,
            'city' => $metadata['city'] ?? null,
            'state' => $metadata['state'] ?? null,
            'zip_code' => $metadata['zip_code'] ?? null,
            'affected_event' => $metadata['affected_event'] ?? null,
            'statement' => $metadata['statement'] ?? null,
            'family_photo_url' => $metadata['family_photo_url'] ?? null,
        ]);

        // Mark OTP as used and cleanup
        $record->markAsUsed();
        $this->cleanupExpiredOtps($email);

        return [
            'success' => true,
            'message' => 'Beneficiary account created successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role,
                ],
                'beneficiary' => [
                    'id' => $beneficiary->id,
                    'family_size' => $beneficiary->family_size,
                    'address' => $beneficiary->address,
                    'city' => $beneficiary->city,
                    'state' => $beneficiary->state,
                    'zip_code' => $beneficiary->zip_code,
                    'affected_event' => $beneficiary->affected_event,
                    'statement' => $beneficiary->statement,
                    'family_photo_url' => $beneficiary->family_photo_url,
                ],
            ],
        ];
    }
}
