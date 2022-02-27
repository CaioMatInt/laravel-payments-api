<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) { }

    public function login(LoginRequest $request): Response
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password
        ];

        $authAttemptWasSuccessful = Auth::attempt($credentials);

        if ($authAttemptWasSuccessful) {
            $user = auth()->user();
            $responseData['name'] =  $user->name;
            $responseData['access_token'] =  $user->createToken('LaravelSanctumAuth')->plainTextToken;

            return response($responseData);
        }

        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }

    public function register(RegisterRequest $request): Response
    {
        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        $this->userRepository->create($data);
        return response()->success(Response::HTTP_CREATED);
    }

    public function getAuthenticatedUser(): Response
    {
        $userResource = new UserResource(auth()->user());
        return response($userResource);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();
        return response()->success();
    }

    public function sendPasswordResetLinkEmail(Request $request): Response
    {
        $status = Password::sendResetLink(
            $request->only('email'),
            function () {
                route('authentication.password.reset');
            }
        );

        if($status === Password::RESET_LINK_SENT) {
            return response()->success(200, [], __($status));
        }

        throw ValidationException::withMessages([
            'email' => __($status)
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->success(200, [], __($status));
        }

        throw ValidationException::withMessages([
            'email' => __($status)
        ]);
    }
}
