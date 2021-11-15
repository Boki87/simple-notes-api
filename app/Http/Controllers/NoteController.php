<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\Folder;
use Carbon\Carbon;

class NoteController extends Controller
{
    public function getNotesInFolder($id)
    {
        return Note::where(['user_id' => auth()->user()->id, 'folder_id' => $id])->get();
    }


    public function store(Request $request, $folderId)
    {



        //check if folder exists
        $folder = Folder::find($folderId);

        if (!$folder) {
            return response(['message' => 'folder does not exist'], 404);
        }


        return Note::create([
            'title' => 'Untitled Note',
            'body' => 'Edit me...',
            'user_id' => auth()->user()->id,
            'folder_id' => $folderId
        ]);
    }



    public function getNote(Request $request, $id)
    {
        $note = Note::find($id);

        //check if note exists
        if (!$note) {
            return response([
                'message' => 'Note Not Found'
            ], 404);
        }

        //check if folder is owned by user
        if ($note['user_id'] != auth()->user()->id) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }
        return $note;
    }


    public function update(Request $request, $id)
    {
        $note = Note::find($id);

        //check if note exists
        if (!$note) {
            return response([
                'message' => 'Note Not Found'
            ], 404);
        }

        //check if folder is owned by user
        if ($note['user_id'] != auth()->user()->id) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }

        $note->update($request->all());
        $note->updated_at = Carbon::now();
        return $note;
    }

    public function destroy(Request $request, $id)
    {

        $note = Note::find($id);


        //check if note exists
        if (!$note) {
            return response([
                'message' => 'Note Not Found'
            ], 404);
        }

        //check if note is owned by user
        if ($note['user_id'] != auth()->user()->id) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }
        return $note->destroy($id);
    }


    public function search(Request $request, $query)
    {

        return Note::join('folders', 'notes.folder_id', '=', 'folders.id')->where('notes.user_id', '=', auth()->user()->id)->where('notes.title', 'like', '%' . $query . '%')->get(['notes.*', 'folders.name as folder_name', 'folders.id as folder_id']);
    }
}
