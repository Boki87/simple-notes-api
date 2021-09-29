<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Folder;
use App\Models\Note;
use GuzzleHttp\Exception\ClientException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{


    public function me(Request $request)
    {
        $user = auth()->user();
        $token = $user->createToken(env('TOKEN_SECRET'))->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])
        ]);



        ///create first folder
        $firstFolder = Folder::create([
            'name' => 'My Notes',
            'user_id' => $user['id']
        ]);

        ///create first note in folder
        Note::create([
            'title' => 'Untitled Note',
            'user_id' => $user['id'],
            'folder_id' => $firstFolder['id']
        ]);

        $token = $user->createToken(env('TOKEN_SECRET'))->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }


    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check if user with email exists
        $user = User::where('email', $fields['email'])->first();

        //Check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'bad credentials'
            ], 401);
        }

        $token = $user->createToken(env('TOKEN_SECRET'))->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }


    public function updateName(Request $request)
    {

        $request->validate([
            'name' => 'required|min:4'
        ]);

        $user = User::find(auth()->id());

        $user->name = $request['name'];

        $user->save();

        return response([
            'user' => $user
        ]);
    }


    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged out'
        ];
    }



    ////social login methods

    public function redirectToProvider($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        try {
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception) {
            return response(['error' => 'Invalid credentials provided']);
        }

        $userCreated = User::firstOrCreate(
            ['email' => $user->getEmail()],
            [
                'email_verified_at' => now(),
                'name' => $user->getName(),
                'status' => true
            ],
            [
                'avatar' => $user->getAvatar()
            ]
        );

        $userCreated->providers()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $user->getId(),
            ]
        );

        $token = $userCreated->createToken(env('TOKEN_SECRET'))->plainTextToken;

        return redirect(env('CLIENT_URL') . '/login/' . $provider . '?token=' . $token);

        // $response = [
        //     'user' => $userCreated,
        //     'token' => $token
        // ];

        // return response($response, 200);
    }


    protected function validateProvider($provider)
    {
        if (!in_array($provider, ['google'])) {
            return response(['error' => 'Please login using google'], 422);
        }
    }
}
