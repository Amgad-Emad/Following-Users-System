<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $feeds = Post::with('user', 'user.followers')
            ->withCount('likes', 'comments')
            ->whereHas('user.followers', function ($q) use ($user) {
                $q->where('following_id', $user->id);
            })
            ->orWhere('visibility', 'public')
            ->paginate(5);
        return response()->json(['feeds' => $feeds], 200);
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


    /**
     * Display the specified resource.
     */
    /**
     * 
     * Store a new post.
     *
     * Validates incoming post data, stores the post along with media (if any), and returns the created post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'media_type' => 'required|string|in:image,video',
            // 'media_link' => 'required',
            'media_thmbnail' => 'required_if:media_type,video',
            'visibility' => 'required|in:public,followers'
        ]);

        // Create a new post instance
        $post = new Post();

        //** */ Check if the request contains a file for media link
        if ($request->hasFile('media_link')) {
            // Validate media link based on media type
            if ($request->media_type == 'image') {
                $request->validate([
                    'media_link' => 'image|mimes:jpg,jpeg,png,gif|max:5120',
                ]);
            } else {
                $request->validate([
                    'media_link' => 'mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4|max:5120',
                ]);

                // Validate media thumbnail if it's a video
                if ($request->file('media_thmbnail')) {
                    $request->validate([
                        'media_thmbnail' => 'image|mimes:jpg,jpeg,png,gif|max:5120'
                    ]);
                    $post->media_thmbnail = $request->file('media_thmbnail')->store('media_link');
                }
            }
            // Store the media link
            $post->media_link = $request->file('media_link')->store('media_link');
        }

        // Assign user id, media type, visibility, and body to the post
        $post->user_id = $request->user()->id;
        $post->media_type = $request->media_type;
        $post->visibility = $request->visibility;
        $post->body = $request->body;

        // Save the post
        if ($post->save()) {
            // Return the created post
            return response()->json($post, 200);
        } else {
            // Return error response if saving failed
            return response()->json(['message' => 'Some error occurred, please try again'], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
