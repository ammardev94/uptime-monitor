<?php

namespace Tests\Feature;

use App\Mail\WelcomeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::admin()->id]);
    }

    private function newUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jamie New',
            'email' => 'jamie@example.test',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'role_id' => Role::where('is_admin', false)->value('id'),
        ], $overrides);
    }

    public function test_new_user_gets_welcome_email_with_the_admin_set_password(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->newUserPayload())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail) {
            return $mail->hasTo('jamie@example.test')
                && $mail->password === 'secret-pass-123'
                && str_contains($mail->render(), 'secret-pass-123');
        });
    }

    public function test_updating_a_user_does_not_send_a_welcome_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role_id' => Role::where('is_admin', false)->value('id')]);

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), $this->newUserPayload(['email' => $user->email]))
            ->assertRedirect(route('admin.users.index'));

        Mail::assertNothingSent();
    }

    public function test_user_is_still_created_when_the_email_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->newUserPayload())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('mail');

        $this->assertDatabaseHas('users', ['email' => 'jamie@example.test']);
    }
}
