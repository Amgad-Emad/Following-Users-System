<?php

namespace App\Console\Commands;

use App\Mail\NewPostNotification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPostsToFollowersEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-posts-to-followers-email {user}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send posts to followers via email';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $userId = $this->argument('user');
        if ($this->confirm('Do you want to send posts to followers?')) {
            // Retrieve the user
            $user = User::findOrFail($userId);

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
            $this->info('Posts sent to followers successfully');
        } else {
            $this->info('Command canceled.');
        }
    }
}
