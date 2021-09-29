<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'avatar' => 'required|mimes:jpg,png|max:9999'
        ]);

        $base_location = 'user_avatars';

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store($base_location, 's3');
        } else {
            return response([
                'message' => 'No file uploaded'
            ], 400);
        }

        $user = User::find(auth()->id());

        $user->avatar = $avatarPath;

        $user->save();

        return response([
            'user' => $user
        ]);
    }

    public function destroy(Request $request)
    {
        $user = User::find(auth()->id());
        $avatarPath = $user->avatar;

        if ($avatarPath && $avatarPath != '') {
            Storage::disk('s3')->delete($avatarPath);
            $user->avatar = '';
            $user->save();
            return response([
                'message' => 'Avatar deleted'
            ]);
        }
    }
}
