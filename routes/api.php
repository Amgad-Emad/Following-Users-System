<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Livewire\store;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('reset-password-request', [UserController::class, 'resetPasswordRequest']);
    Route::post('reset-password', [UserController::class, 'resetPassword']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::group(['middleware' => 'ability:user,admin'], function () {
            Route::get('profile', [UserController::class, 'profile']);
            Route::post('change-password', [UserController::class, 'changePassword']);
            Route::post('update-profile', [UserController::class, 'updateProfile']);
            Route::get('logout', [UserController::class, 'logout']);
        });
    });
});
Route::group(['prefix' => 'user'], function () {
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::group(['middleware' => 'ability:user'], function () {
            Route::resource('posts', PostController::class);
            Route::resource('comments', CommentController::class);
            Route::post('like-unlike', [LikeController::class, 'store']);
            Route::post('follow-unfollow-user', [FollowerController::class, 'store']);
            Route::get('feeds', [PostController::class, 'index']);
        });
    });
});
