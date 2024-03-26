<?php

namespace App\Http\Controllers;

use App\Mail\MailResetPasswordRequest;
use App\Mail\NewPostNotification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as RulesPassword;

class UserController extends Controller
{
    //  * Validates incoming registration request data, creates a new user instance, and saves it.
    public function register(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|email|unique:users',
            'password' => [
                'required', 'confirmed',
                // Define password rules using Laravel's Password rule builder
                RulesPassword::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ]
        ]);

        // Create a new user instance
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role = 'user';

        // Save the user
        if ($user->save()) {
            return response()->json(['massage' => 'Registration successful, please try to login'], 201);
        } else {
            return response()->json(['massage' => 'Some error, please try again'], 500);
        }
    }

    // * Validates incoming login request data, attempts authentication, and returns an access token upon success.
    public function login(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt authentication
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json(['massage' => 'invalid username/password'], 401);
        }

        // Retrieve the authenticated user
        $user = $request->user();

        // Delete existing tokens
        $user->tokens()->delete();

        // Create and return a new access token
        if ($user->role == 'admin') {
            $token = $user->createToken('Personal Access Token', ['adimn']);
        } else {
            $token = $user->createToken('Personal Access Token', ['user']);
        }

        return response()->json([
            'user' => $user,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $token->accessToken->abilities,
        ], 200);
    }
    //  * Validates incoming reset password request data, generates a verification code, and sends it via email.
    public function resetPasswordRequest(Request $request)
    {

        // Validate incoming request data
        $request->validate([
            'email' => 'required|string|email',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Handle cases when user not found
        if (!$user) {
            return response()->json(['massage' => 'we have sent a verification code to your profuded email'], 200);
        }
        // Generate a verification code
        $user->verification_code = NULL;
        $code = rand(111111, 999999);
        $user->verification_code = $code;

        // Send email with verification code
        if ($user->save()) {
            $emailData = array(
                'heading' => 'Reset Password Request',
                'name' => $user->name,
                'email' => $user->email,
                'code' => $code
            );
            Mail::to($emailData['email'])->queue(new MailResetPasswordRequest($emailData));
            return response()->json(['massage' => 'we have sent a verification code to your profuded email'], 200);
        } else {
            return response()->json(['massage' => 'some error occurred, please try again'], 500);
        }
    }

    //  * Validates incoming reset password request data, updates user password, and clears verification code.
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'new_password' => [
                'required',
                'confirmed',
                RulesPassword::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ]
        ]);

        // Find user by email and verification code
        $user = User::where('email', $request->email)->where('verification_code', $request->verification_code)->first();

        // Handle cases when user not found or invalid verification code
        if (!$user) {
            return response()->json(['massage' => 'user not found/ invalid verification code'], 404);
        }

        // Update user password and clear verification code
        $user->password = bcrypt($request->new_password);
        $user->verification_code = NULL;

        // Save the updated user
        if ($user->save()) {
            return response()->json(['massage' => 'password updated successfully'], 200);
        } else {
            return response()->json(['massage' => 'some error occurred, please try again'], 500);
        }
    }
    //  * Retrieves user profile.
    public function profile(Request $request)
    {
        // Retrieve user profile
        $user = $request->user();

        // Return user profile if found, otherwise return error message
        if ($user) {
            return response()->json($user, 200);
        } else {
            return response()->json(['massage' => 'user not found'], 404);
        }
    }
    // * Validates incoming change password request data, updates user password.
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required', 'confirmed', 'max:150',
                RulesPassword::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ]
        ]);

        // Retrieve authenticated user
        $user = $request->user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['massage' => 'Current password is wrong'], 401);
        }

        // Update user password
        $user->password = bcrypt($request->new_password);
        if ($user->save()) {
            return response()->json(['massage' => 'password changed successfully'], 200);
        } else {
            return response()->json(['massage' => 'some error occurred, please try again'], 500);
        }
    }

    //  * Validates incoming update profile request data, updates user profile, including name, photo, and about section.
    public function updateProfile(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Retrieve the authenticated user
        $user = $request->user();

        // Store the current photo for potential deletion
        $oldPhoto = $user->photo;

        // Check if request has a photo file
        if ($request->hasFile('photo')) {
            // Validate the photo file
            $request->validate([
                'photo' => 'image|mimes:jpg,png,jpeg|max:5120',
            ]);
            // Store the photo file and update user's photo path
            $path = $request->file('photo')->store('profile');
            $user->photo = $path;
        }

        // Update user's name and about section
        $user->name = $request->name;
        $user->about = $request->about;

        // Save the updated user profile
        if ($user->save()) {
            // Delete the old photo if it's different from the updated one
            if ($oldPhoto != $user->photo) {
                Storage::delete($oldPhoto);
            }
            // Return success response with updated user profile
            return response()->json($user, 200);
        } else {
            // Return error response if saving failed
            return response()->json(['message' => 'Some error occurred, please try again'], 500);
        }
    }

    //  * Revokes all of the user's tokens.

    public function logout(Request $request)
    {
        // Delete all tokens associated with the user
        if ($request->user()->tokens()->delete()) {
            // Return success response upon successful logout
            return response()->json(["message" => "Logout successfully"], 200);
        } else {
            // Return error response if deletion failed
            return response()->json(['message' => 'Some error occurred, please try again'], 500);
        }
    }

    
    // public function sendPostsToFollowersEmail(User $user)
    // {
    //     // Retrieve all posts of the user
    //     $posts = Post::where('user_id', $user->id)->get();
    
    //     // Get all followers' IDs of the user
    //     $followerIds = $user->followers()->pluck('user_id');
    
    //     // Retrieve the email addresses of followers
    //     $followerEmails = User::whereIn('id', $followerIds)->pluck('email');
    
    //     // Iterate through each follower's email
    //     foreach ($followerEmails as $followerEmail) {
    //         // Check if the follower's email address is valid
    //         if (!empty($followerEmail)) {
    //             // Send email to the follower with all posts of the user
    //             Mail::to($followerEmail)->send(new NewPostNotification($user, $posts));
    //         }
    //     }
    
    //     // Return success response
    //     return response()->json(['message' => 'Posts sent to followers successfully'], 200);
    // }
}
