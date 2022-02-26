<?php

namespace Tests\Unit\Controller;

use App\Models\User;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

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
        $response->assertStatus(Response::HTTP_OK);
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

        $this->post(route('authentication.register'), $userData)->assertStatus(Response::HTTP_CREATED);
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

        $response->assertStatus(Response::HTTP_OK);
    }


    public function testCanShowCurrentAuthenticatedUser()
    {
        $response = $this->actingAs($this->user)->get(route('authentication.me'));
        $response->assertStatus(Response::HTTP_OK);
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
}
