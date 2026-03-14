<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link to the given email.
     *
     * This endpoint uses rate limiting to prevent abuse.
     * Rate limit: 1 request per email per 60 seconds.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Check rate limiting manually (1 request per email per 60 seconds)
        $rateLimitKey = 'password-reset:' . $request->email;
        
        if (cache()->has($rateLimitKey)) {
            $ttlKey = $rateLimitKey . ':ttl';
            $retryAfter = cache()->get($ttlKey, 60);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many reset attempts. Please try again later.',
                    'retry_after' => (int) $retryAfter,
                ],
            ], 429)->withHeaders(['Retry-After' => (string) (int) $retryAfter]);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Set rate limit after attempting to send (prevents enumeration via timing)
        cache()->put($rateLimitKey, true, 60);
        cache()->put($rateLimitKey . ':ttl', 60, 60);

        // Always return the same generic success message
        // This prevents email enumeration attacks
        return response()->json([
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the user's password.
     *
     * Validates the token, email, and password, then updates the user's password.
     * All existing tokens are revoked for security.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => null, // Clear remember token to invalidate "remember me" sessions
                ])->save();

                // Revoke all existing Sanctum tokens for security
                $user->tokens()->delete();

                // Fire the password reset event
                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status === Password::PASSWORD_RESET) {
            // Get the user to create a new token
            $user = \App\Models\User::where('email', $request->email)->first();
            $token = $user->createToken(
                'auth-token',
                ['*'],
                now()->addDays(7)
            );

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
                'data' => [
                    'token' => $token->plainTextToken,
                ],
            ]);
        }

        // Invalid or expired token
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
