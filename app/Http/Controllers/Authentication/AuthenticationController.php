<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) { }

    public function login(LoginRequest $request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password
        ];

        $authAttempt = Auth::attempt($credentials);

        if ($authAttempt) {
            $user = auth()->user();
            $responseData['name'] =  $user->name;
            $responseData['access_token'] =  $user->createToken('LaravelSanctumAuth')->plainTextToken;

            return response()->json($responseData);
        }

        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        $this->userRepository->create($data);
        return response()->success(Response::HTTP_CREATED);
    }

    public function getAuthenticatedUser() {
        $userResource = new UserResource(auth()->user());
        return response()->json($userResource);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->success();
    }
}
