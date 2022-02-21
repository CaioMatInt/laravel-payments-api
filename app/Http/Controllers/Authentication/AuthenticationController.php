<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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
            $auth = Auth::user();
            $responseData['token'] =  $auth->createToken('LaravelSanctumAuth')->plainTextToken;

            return response()->json([
                $responseData
            ]);
        }

        $responseData['message'] = 'Invalid Credentials';

        return response()->json([
            $responseData
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        $this->userRepository->create($data);
        return response()->success(Response::HTTP_CREATED);
    }
}
