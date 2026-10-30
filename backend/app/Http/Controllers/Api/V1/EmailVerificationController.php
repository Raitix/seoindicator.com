<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationController extends Controller
{
    /**
     * Verify the user's email address.
     *
     * @param Request $request
     * @param int $id
     * @param string $hash
     * @return JsonResponse
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        // Find the user
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
        }

        // Verify the hash matches the user's email
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link.',
            ], 403);
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
    }

    /**
     * Resend the email verification notification.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.',
            ], 400);
        }

        // Rate limiting: 1 request per minute per user
        $key = 'verification-email:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => 1,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => time() + $seconds,
                'Retry-After' => $seconds,
            ]);
        }

        // Hit the rate limiter
        RateLimiter::hit($key, 60);

        // Send verification email
        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent.',
        ])->withHeaders([
            'X-RateLimit-Limit' => 1,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + 60,
        ]);
    }
}
