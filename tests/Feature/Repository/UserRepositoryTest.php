<?php

namespace Repository;

use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use function app;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private $userRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->userRepository = app(UserRepository::class);
    }

    public function testCanInstantiateUserRepository()
    {
        $userRepository = new UserRepository(app(User::class));
        $this->assertInstanceOf(UserRepository::class, $userRepository);
    }

    public function testCantInstantiateUserRepositoryWithWrongClass()
    {
        $this->expectException(\TypeError::class);
        new UserRepository(app(UserRepository::class));
    }

    public function testCanCreateUser()
    {
        $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'testing@mail.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'testing@mail.com'
        ]);
    }

    public function testCantCreateUserWithoutData()
    {
        $this->expectException(QueryException::class);
        $this->userRepository->create([]);
    }

    public function testCantCreateUserWithoutName()
    {
        $this->expectException(QueryException::class);
        $this->userRepository->create([
            'email' => 'johnDoe@gmail.com',
            'password' => Hash::make('password')
        ]);
    }

    public function testCantCreateUserWithoutEmail()
    {
        $this->expectException(QueryException::class);
        $this->userRepository->create([
            'name' => 'John Doe',
            'password' => Hash::make('password')
        ]);
    }

    public function testCantCreateUserWithoutPassword()
    {
        $this->expectException(QueryException::class);
        $this->userRepository->create([
            'name' => 'John Doe',
            'email' => 'johnDoe@gmail.com'
        ]);
    }
}
