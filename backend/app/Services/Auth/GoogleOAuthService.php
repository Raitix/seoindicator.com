<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\AccessDeniedException;
use App\Exceptions\Auth\ConfigurationException;
use App\Exceptions\Auth\InsufficientScopeException;
use App\Exceptions\Auth\InvalidOAuthStateException;
use App\Exceptions\Auth\OAuthFailedException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

/**
 * GoogleOAuthService handles Google OAuth 2.0 authentication flow.
 *
 * This service provides methods for:
 * - Generating OAuth redirect URLs with CSRF state protection
 * - Processing OAuth callbacks and exchanging authorization codes
 * - Creating new users or linking existing accounts via Google OAuth
 * - Managing OAuth tokens with encryption at rest
 *
 * @see TASK-25-7
 * @see SPEC-025
 * @see FEAT-025
 */
class GoogleOAuthService
{
    /**
     * The OAuth state parameter cache key prefix.
     */
    private const STATE_CACHE_PREFIX = 'oauth_state_';

    /**
     * The state parameter length in characters.
     */
    private const STATE_LENGTH = 40;

    /**
     * The state parameter TTL in seconds (10 minutes).
     */
    private const STATE_TTL_SECONDS = 600;

    /**
     * The OAuth provider name.
     */
    private const PROVIDER = 'google';

    /**
     * Generate a Google OAuth redirect URL with CSRF state protection.
     *
     * @return string The Google OAuth authorization URL
     * @throws ConfigurationException If Google OAuth is not configured
     */
    public function getRedirectUrl(): string
    {
        $this->validateConfiguration();

        // Generate CSRF state token
        $state = Str::random(self::STATE_LENGTH);

        // Store state in cache with TTL
        Cache::put(
            self::STATE_CACHE_PREFIX . $state,
            true,
            now()->addSeconds(self::STATE_TTL_SECONDS)
        );

        // Build redirect URL with state parameter
        return Socialite::driver(self::PROVIDER)
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * @param string $code The authorization code from Google
     * @param string $state The state parameter for CSRF validation
     * @return array{user: User, token: string, is_new_user: bool}
     * @throws InvalidOAuthStateException If state validation fails
     * @throws AccessDeniedException If user denied authorization
     * @throws OAuthFailedException If OAuth flow fails
     * @throws InsufficientScopeException If required scopes are missing
     */
    public function handleCallback(string $code, string $state): array
    {
        // Handle access denied
        if ($code === 'access_denied') {
            throw new AccessDeniedException('User denied access to Google account');
        }

        // Validate state parameter
        $this->validateState($state);

        // Consume state (one-time use)
        $this->consumeState($state);

        try {
            // Get Google user from authorization code
            $googleUser = Socialite::driver(self::PROVIDER)
                ->stateless()
                ->user();

            // Validate required scopes
            $this->validateScopes($googleUser);

            // Find or create user
            $isNewUser = ! $this->userExists($googleUser);
            $user = $this->findOrCreateUser($googleUser);

            // Create Sanctum token
            $token = $user->createToken('google-oauth')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
                'is_new_user' => $isNewUser,
            ];
        } catch (InvalidOAuthStateException | AccessDeniedException | InsufficientScopeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Google OAuth failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new OAuthFailedException(
                'Failed to authenticate with Google: ' . $e->getMessage(),
                400,
                $e
            );
        }
    }

    /**
     * Find an existing user or create a new one from Google data.
     *
     * @param SocialiteUser $googleUser The Google user data
     * @return User The found or created user
     * @throws \InvalidArgumentException If Google ID or email is empty
     */
    public function findOrCreateUser(SocialiteUser $googleUser): User
    {
        // Validate Google ID
        if (empty($googleUser->id)) {
            throw new \InvalidArgumentException('Google ID cannot be empty');
        }

        // Validate email
        if (empty($googleUser->email)) {
            throw new \InvalidArgumentException('Google email cannot be empty');
        }

        return DB::transaction(function () use ($googleUser) {
            // Check for existing social account by provider_id
            $socialAccount = SocialAccount::query()
                ->byProvider(self::PROVIDER)
                ->where('provider_id', $googleUser->id)
                ->first();

            if ($socialAccount) {
                // Update tokens
                $this->updateSocialAccountTokens($socialAccount, $googleUser);

                return $socialAccount->user;
            }

            // Normalize email for comparison (lowercase, trimmed)
            $normalizedEmail = $this->normalizeEmail($googleUser->email);

            // Check for existing user by email (case-insensitive)
            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->first();

            if ($user) {
                // Link social account to existing user
                $this->createSocialAccount($user, $googleUser);

                // Update avatar if user doesn't have one
                $this->updateAvatarIfMissing($user, $googleUser);

                // Auto-verify email if not already verified AND Google confirms email is verified
                if (! $user->hasVerifiedEmail() && $this->isGoogleEmailVerified($googleUser)) {
                    $user->markEmailAsVerified();
                    $user->refresh(); // Ensure we have the latest timestamp
                }

                return $user;
            }

            // Create new user
            return $this->createUser($googleUser);
        });
    }

    /**
     * Validate Google OAuth configuration.
     *
     * @throws ConfigurationException If configuration is missing
     */
    protected function validateConfiguration(): void
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirect = config('services.google.redirect');

        if (empty($clientId) || empty($clientSecret) || empty($redirect)) {
            throw new ConfigurationException(
                'Google OAuth is not configured. Please set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI in your environment.'
            );
        }
    }

    /**
     * Validate the OAuth state parameter.
     *
     * @param string $state The state parameter to validate
     * @throws InvalidOAuthStateException If state is invalid or expired
     */
    protected function validateState(string $state): void
    {
        if (empty($state)) {
            throw new InvalidOAuthStateException('OAuth state parameter is missing');
        }

        if (! Cache::has(self::STATE_CACHE_PREFIX . $state)) {
            throw new InvalidOAuthStateException(
                'Invalid or expired OAuth state parameter. Please try again.'
            );
        }
    }

    /**
     * Consume (remove) the state parameter from cache.
     * This ensures one-time use of the state token.
     *
     * @param string $state The state parameter to consume
     */
    protected function consumeState(string $state): void
    {
        Cache::forget(self::STATE_CACHE_PREFIX . $state);
    }

    /**
     * Validate that required scopes were granted.
     *
     * @param SocialiteUser $googleUser The Google user data
     * @throws InsufficientScopeException If email scope is missing
     */
    protected function validateScopes(SocialiteUser $googleUser): void
    {
        if (empty($googleUser->email)) {
            throw new InsufficientScopeException(
                'Email scope is required. Please grant access to your email address.'
            );
        }
    }

    /**
     * Check if a user already exists for this Google account.
     *
     * @param SocialiteUser $googleUser The Google user data
     * @return bool
     */
    protected function userExists(SocialiteUser $googleUser): bool
    {
        return SocialAccount::query()
                ->byProvider(self::PROVIDER)
                ->where('provider_id', $googleUser->id)
                ->exists()
            || User::query()
                ->where('email', $googleUser->email)
                ->exists();
    }

    /**
     * Create a new user from Google data.
     *
     * @param SocialiteUser $googleUser The Google user data
     * @return User The created user
     */
    protected function createUser(SocialiteUser $googleUser): User
    {
        $name = $googleUser->name ?? $this->extractNameFromEmail($googleUser->email);

        // Truncate name to 255 characters
        $name = Str::limit($name, 255, '');

        $user = User::create([
            'name' => $name,
            'email' => $googleUser->email,
            'password' => null, // OAuth-only user
            'avatar' => $googleUser->avatar,
            'email_verified_at' => now(), // Google email is verified
        ]);

        $this->createSocialAccount($user, $googleUser);

        return $user;
    }

    /**
     * Create a social account record for a user.
     *
     * @param User $user The user
     * @param SocialiteUser $googleUser The Google user data
     * @return SocialAccount
     */
    protected function createSocialAccount(User $user, SocialiteUser $googleUser): SocialAccount
    {
        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => self::PROVIDER,
            'provider_id' => $googleUser->id,
            'provider_token' => $googleUser->token,
            'provider_refresh_token' => $googleUser->refreshToken,
            'provider_token_expires_at' => $this->calculateTokenExpiration($googleUser),
        ]);
    }

    /**
     * Update tokens on an existing social account.
     *
     * @param SocialAccount $socialAccount The social account
     * @param SocialiteUser $googleUser The Google user data
     */
    protected function updateSocialAccountTokens(SocialAccount $socialAccount, SocialiteUser $googleUser): void
    {
        $socialAccount->update([
            'provider_token' => $googleUser->token,
            'provider_refresh_token' => $googleUser->refreshToken ?? $socialAccount->provider_refresh_token,
            'provider_token_expires_at' => $this->calculateTokenExpiration($googleUser),
        ]);
    }

    /**
     * Calculate the token expiration time.
     *
     * @param SocialiteUser $googleUser The Google user data
     * @return \Illuminate\Support\Carbon|null
     */
    protected function calculateTokenExpiration(SocialiteUser $googleUser): ?\Illuminate\Support\Carbon
    {
        $expiresIn = $googleUser->expiresIn;

        if (empty($expiresIn)) {
            return null;
        }

        return now()->addSeconds($expiresIn);
    }

    /**
     * Extract a name from an email address.
     *
     * @param string $email The email address
     * @return string The extracted name
     */
    protected function extractNameFromEmail(string $email): string
    {
        $localPart = Str::before($email, '@');

        // Replace dots and underscores with spaces
        $name = str_replace(['.', '_', '-'], ' ', $localPart);

        // Capitalize words
        return Str::title($name);
    }

    /**
     * Normalize an email address for comparison.
     *
     * @param string $email The email address
     * @return string The normalized email
     */
    protected function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Update the user's avatar if they don't have one.
     *
     * @param User $user The user
     * @param SocialiteUser $googleUser The Google user data
     */
    protected function updateAvatarIfMissing(User $user, SocialiteUser $googleUser): void
    {
        // Only update avatar if user doesn't have one and Google provides one
        if (empty($user->avatar) && !empty($googleUser->avatar)) {
            $user->update(['avatar' => $googleUser->avatar]);
        }
    }

    /**
     * Check if Google has verified the email address.
     *
     * Google OAuth typically only returns verified emails, but we check
     * the email_verified flag from the user data for defensive coding.
     *
     * @param SocialiteUser $googleUser The Google user data
     * @return bool
     */
    protected function isGoogleEmailVerified(SocialiteUser $googleUser): bool
    {
        // Check the user array for email_verified flag
        // Socialite stores raw user data in $googleUser->user
        if (isset($googleUser->user['email_verified'])) {
            return (bool) $googleUser->user['email_verified'];
        }

        // Default to true since Google OAuth typically only returns verified emails
        // This maintains backward compatibility with existing behavior
        return true;
    }
}
