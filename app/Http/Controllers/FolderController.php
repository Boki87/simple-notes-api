<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Folder;

class FolderController extends Controller
{
    public function index()
    {
        return Folder::where('user_id', auth()->id())->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        return Folder::create([
            'name' => $request['name'],
            'user_id' => auth()->id()
        ]);
    }

    public function update(Request $request, $id)
    {
        $folder = Folder::find($id);

        //check if folder exists
        if (!$folder) {
            return response([
                'message' => 'Folder Not Found'
            ], 404);
        }

        //check if folder is owned by user
        if ($folder['user_id'] != auth()->id()) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }

        $folder->update($request->all());
        return $folder;
    }

    public function destroy(Request $request, $id)
    {

        $folder = Folder::find($id);


        //check if folder exists
        if (!$folder) {
            return response([
                'message' => 'Folder Not Found'
            ], 404);
        }

        //check if folder is owned by user
        if ($folder['user_id'] != auth()->id()) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }
        return $folder->destroy($id);
    }
}
