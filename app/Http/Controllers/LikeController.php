<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(['post_id' => 'required']);
        $user = $request->user();
        $like = Like::where('user_id', $user->id)->where('post_id', $request->post_id)->first();
        if ($like) {
            $like->delete();
            return response()->json(['massage' => 'you unlike this post'], 200);
        } else {
            $like = new Like();
            $like->user_id = $user->id;
            $like->post_id = $request->post_id;
            if ($like->save()) {
                return response()->json([
                "massage" =>'you like this post', 
                'like' => $like->load('user')], 201);
            } else {
                return response()->json(['massage' => 'some error occurred, please try again'], 500);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Like $like)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Like $like)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Like $like)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Like $like)
    {
        //
    }
}
