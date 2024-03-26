<?php

namespace App\Http\Controllers;

use App\Mail\NewPostNotification;
use App\Models\Follower;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class FollowerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $feeds = Post::with('user', 'user.followers')->withCount('likes', 'comments')->whereHas('user.followers', function ($q) use ($user) {
            $q->where('following_id', $user->id);
        })
            ->orWhere('visibility', 'public')->paginate(5);
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
    public function store(Request $request)
    {
        $request->validate(['user_id' => 'required']);

        $user = $request->user();

        $follower = Follower::where('user_id', $request->user_id)
            ->where('following_id', $user->id)
            ->first();

        if (!$follower) {
            $follower = new Follower();
            $follower->user_id = $request->user_id;
            $follower->following_id = $user->id;

            if ($follower->save()) {
                $userToFollow = User::find($request->user_id);
                $this->sendPostsToFollowersEmail($userToFollow->id);
                return response()->json(['message' => 'You followed this user.'], 200);
            } else {
                return response()->json(['message' => 'Something went wrong following this user, try again'], 500);
            }
        } else {
            if ($follower->delete()) {
                $this->sendPostsToFollowersEmail($request->user_id);
                return response()->json(['message' => 'You unfollowed this user.'], 200);
            } else {
                return response()->json(['message' => 'Something went wrong unfollowing this user, try again'], 500);
            }
        }
    }

    public function sendPostsToFollowersEmail(User $user)
    {
        // Retrieve all posts of the user
        $posts = Post::where('user_id', $user->id)->get();
    
        // Get all followers' IDs of the user
        $followerIds = $user->followers()->pluck('user_id');
    
        // Retrieve the email addresses of followers
        $followerEmails = User::whereIn('id', $followerIds)->pluck('email');
    
        // Iterate through each follower's email
        foreach ($followerEmails as $followerEmail) {
            // Check if the follower's email address is valid
            if (!empty($followerEmail)) {
                // Send email to the follower with all posts of the user
                Mail::to($followerEmail)->send(new NewPostNotification($user, $posts));
            }
        }
    
        // Return success response
        return response()->json(['message' => 'Posts sent to followers successfully'], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Follower $follower)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Follower $follower)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Follower $follower)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Follower $follower)
    {
        //
    }
}
