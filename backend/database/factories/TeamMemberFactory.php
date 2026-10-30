<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TeamMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'role' => TeamMember::ROLE_VIEWER,
            'invited_by' => User::factory(),
            'invited_at' => now(),
            'accepted_at' => null,
            'invitation_token' => null,
            'invitation_expires_at' => null,
        ];
    }

    /**
     * Indicate the member is an owner.
     */
    public function owner(): static
    {
        return $this->state([
            'role' => TeamMember::ROLE_OWNER,
            'accepted_at' => now(),
            'invited_at' => now()->subDays(30),
        ]);
    }

    /**
     * Indicate the member is an admin.
     */
    public function admin(): static
    {
        return $this->state([
            'role' => TeamMember::ROLE_ADMIN,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate the member is an editor.
     */
    public function editor(): static
    {
        return $this->state([
            'role' => TeamMember::ROLE_EDITOR,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate the member is a viewer.
     */
    public function viewer(): static
    {
        return $this->state([
            'role' => TeamMember::ROLE_VIEWER,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate the invitation is pending.
     */
    public function pending(): static
    {
        return $this->state([
            'accepted_at' => null,
            'invitation_token' => TeamMember::generateToken(),
            'invitation_expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Indicate the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state([
            'accepted_at' => null,
            'invitation_token' => TeamMember::generateToken(),
            'invitation_expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Set a specific account.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(['account_id' => $account->id]);
    }

    /**
     * Set a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    /**
     * Set a specific inviter.
     */
    public function invitedBy(User $user): static
    {
        return $this->state(['invited_by' => $user->id]);
    }
}
