<?php

namespace Tests\Unit\Controller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class AuthenticationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCanRegisterUserWithValidData()
    {
        $userData['name'] = $this->faker->name();
        $userData['email'] = $this->faker->unique()->safeEmail();
        $userData['password'] = '123456';

        $this->post(route('authentication.register'), $userData)->assertStatus(Response::HTTP_CREATED);
    }

    public function testValidUserLoginCanLogin()
    {
        $userPassword = '123456';
        $user = User::factory()->create([
            'password' => Hash::make($userPassword)
        ]);
        $credentials['email'] = $user->email;
        $credentials['password'] = $userPassword;

        $this->post(route('authentication.login'), $credentials)->assertStatus(Response::HTTP_OK);
    }
}
