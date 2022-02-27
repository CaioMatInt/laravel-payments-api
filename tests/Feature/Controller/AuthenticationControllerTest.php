<?php

namespace Controller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use function route;

class AuthenticationControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $unhashedUserPassword = '123456';

    public function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser($this->unhashedUserPassword);
    }

    private function createUser(string $password)
    {
        return User::factory()->create([
            'password' => Hash::make($password)
        ]);
    }

    public function testValidUserLoginCanLogin()
    {
        $userCredentials['email'] = $this->user->email;
        $userCredentials['password'] = $this->unhashedUserPassword;

        $response = $this->post(route('authentication.login'), $userCredentials);
        $response->assertOk();
        $response->assertJsonStructure([
            'access_token',
            'name'
        ]);
    }

    public function testUserLoginWithoutSendingPassword()
    {
        $userCredentials['email'] = $this->user->email;

        $response = $this->post(route('authentication.login'), $userCredentials);

        $response->assertSessionHasErrors([
            'password' => 'The password field is required.'
        ]);
    }

    public function testUserLoginWithoutSendingEmail()
    {
        $userCredentials['password'] = $this->unhashedUserPassword;

        $response = $this->post(route('authentication.login'), $userCredentials);

        $response->assertSessionHasErrors([
            'email' => 'The email field is required.'
        ]);
    }

    public function testUserLoginWithInvalidPassword()
    {
        $userCredentials['email'] = $this->user->email;
        $userCredentials['password'] = 'invalidPassword';

        $response = $this->post(route('authentication.login'), $userCredentials);

        $response->assertSessionHasErrors([
            'email' => 'These credentials do not match our records.'
        ]);
    }

    public function testUserLoginWithInvalidEmail()
    {
        $userCredentials['email'] = 'email@mail.com';
        $userCredentials['password'] = '123456';

        $response = $this->post(route('authentication.login'), $userCredentials);

        $response->assertSessionHasErrors([
            'email' => 'These credentials do not match our records.'
        ]);
    }

    public function testCanRegisterUserWithValidData()
    {
        $userData['name'] = 'Test User';
        $userData['email'] = 'testing@mail.com';
        $userData['password'] = '123456';

        $this->post(route('authentication.register'), $userData)->assertCreated();
    }

    public function testCantRegisterUserWithoutEmail()
    {
        $userData['name'] = 'Test User';
        $userData['password'] = '123456';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'email' => 'The email field is required.'
        ]);
    }

    public function testCantRegisterUserWithoutName()
    {
        $userData['email'] = 'email@mail.com';
        $userData['password'] = '123456';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'name' => 'The name field is required.'
        ]);
    }

    public function testCantRegisterUserWithoutPassword()
    {
        $userData['name'] = 'Test User';
        $userData['email'] = 'email@mail.com';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'password' => 'The password field is required.'
        ]);
    }

    public function testCantRegisterUserWithAnEmailAlreadyInUse()
    {
        $userData['name'] = 'Test User';
        $userData['email'] = $this->user->email;
        $userData['password'] = '123456';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'email' => 'The email has already been taken.'
        ]);
    }

    public function testCantRegisterUserWithAnInvalidEmail()
    {
        $userData['name'] = 'Test User';
        $userData['email'] = 'email';
        $userData['password'] = '123456';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'email' => 'The email must be a valid email address.'
        ]);
    }

    public function testCantRegisterUserWithANameLongerThan255Characters()
    {
        $userData['name'] = str_repeat('a', 256);
        $userData['email'] = 'email@mail.com';
        $userData['password'] = '123456';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'name' => 'The name must not be greater than 255 characters.'
        ]);
    }

    public function testCantRegisterUserWithAnIntegerName()
    {
        $userData['name'] = 123;
        $userData['email'] = 'email';
        $userData['password'] = '123456';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'name' => 'The name must be a string.'
        ]);
    }

    public function testCantRegisterWithAPasswordWithLessThan6Characters()
    {
        $userData['name'] = 'Test User';
        $userData['email'] = 'email@mail.com';
        $userData['password'] = '12345';

        $response = $this->post(route('authentication.register'), $userData);

        $response->assertSessionHasErrors([
            'password' => 'The password must be at least 6 characters.'
        ]);
    }

    public function testNonAuthenticatedUserCannotLogout() {
        $response = $this->json('POST', route('authentication.logout'));

        $response->assertUnauthorized();
    }

    public function testAuthenticatedUserCanLogout()
    {
        Sanctum::actingAs(
            $this->user
        );

        $response = $this->json('POST', route('authentication.logout'));
        $response->assertOk();
    }

    public function testCanShowCurrentAuthenticatedUser()
    {
        $response = $this->actingAs($this->user)->get(route('authentication.me'));
        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'name',
            'created_at',
            'updated_at',
        ]);
    }

    public function testCantShowCurrentAuthenticatedUserWhenNotLoggedIn()
    {
       $this->json('get', route('authentication.me'))->assertUnauthorized();
    }

    public function testForgotPasswordWhenEmailDoesntExists()
    {
        $response = $this->json('POST', route('authentication.password.forgot'), [
            'email' => 'invalid@email.com'
        ]);

        $response->assertUnprocessable();
        $this->assertEquals(
            'The selected email is invalid.',
            $response->json('errors.email.0')
        );
    }

    public function testForgotPasswordResponseWhenEmailIsValid()
    {
        $user = $this->user;
        $response = $this->json('POST', route('authentication.password.forgot'), [
            'email' => $user->email
        ]);

        $response->assertStatus(200)->assertJsonStructure(['message']);
    }

    public function testPasswordResetWhenTokenIsValid()
    {
        $user = $this->user;
        $token = Password::broker()->createToken($user);
        $newPassword = '12345678';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'message'
        ]);

        $this->assertEquals("Your password has been reset!", $response[
            'message'
        ]);
    }

    public function testPasswordResetWhenTokenIsInvalid()
    {
        $user = $this->user;
        $newPassword = '12345678';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertStatus(422)->assertJsonStructure([
            'message'
        ]);

        $this->assertEquals("This password reset token is invalid.", $response[
            'message'
        ]);
    }

    public function testCantResetPasswordWhenEmailIsNotValid()
    {
        $newPassword = '12345678';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'token' => 'invalid-token',
            'email' => 'email@mail.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertUnprocessable();
        $this->assertEquals(
            'The selected email is invalid.',
            $response->json('errors.email.0')
        );
    }

    public function testCantResetPasswordWhenPasswordHas5Characters()
    {
        $user = $this->user;
        $token = Password::broker()->createToken($user);
        $newPassword = '12345';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertUnprocessable();
        $this->assertEquals(
            'The password must be at least 6 characters.',
            $response->json('errors.password.0')
        );
    }

    public function testCantResetPasswordWhenConfirmingWrongPassword()
    {
        $user = $this->user;
        $token = Password::broker()->createToken($user);
        $newPassword = '12345678';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => 'invalid-password'
        ]);

        $response->assertUnprocessable();

        $this->assertEquals(
            'The password confirmation does not match.',
            $response->json('errors.password.0')
        );
    }

    public function testCantResetPasswordWhenNotConfirmingPassword()
    {
        $user = $this->user;
        $token = Password::broker()->createToken($user);
        $newPassword = '12345678';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword
        ]);

        $response->assertUnprocessable();

        $this->assertEquals(
            'The password confirmation field is required.',
            $response->json('errors.password_confirmation.0')
        );
    }

    public function testCantResetPasswordWhenNotSendingToken()
    {
        $user = $this->user;
        $newPassword = '12345678';

        $response = $this->json('POST', route('authentication.password.reset'), [
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertUnprocessable();

        $this->assertEquals(
            'The token field is required.',
            $response->json('errors.token.0')
        );
    }
}
