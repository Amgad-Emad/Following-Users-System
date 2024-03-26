<?php

namespace App\Jobs;

use App\Mail\NewPostNotification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPostsToFollowersEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Retrieve the user
        $user = User::findOrFail($this->userId);
    
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
    }
    
}
