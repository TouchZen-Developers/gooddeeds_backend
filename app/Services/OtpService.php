<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\PasswordResetOtp;
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
        $existingOtp = PasswordResetOtp::where('email', $email)
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
        PasswordResetOtp::create([
            'email' => $email,
            'otp' => $otp,
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
            PasswordResetOtp::where('email', $email)
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
        $otpRecord = PasswordResetOtp::where('email', $email)
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
        $otpRecord = PasswordResetOtp::where('email', $email)
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
        return PasswordResetOtp::where('email', $email)
            ->expired()
            ->delete();
    }

    /**
     * Clean up all expired OTPs
     */
    public function cleanupAllExpiredOtps(): int
    {
        return PasswordResetOtp::cleanupExpired();
    }
}
