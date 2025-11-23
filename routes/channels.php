<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Market data channels
Broadcast::channel('market-data.{symbol}', function ($user, $symbol) {
    return $user->is_active;
});

// User-specific channels
Broadcast::channel('user.{userId}.analyses', function ($user, $userId) {
    return $user->id === $userId;
});

Broadcast::channel('user.{userId}.alerts', function ($user, $userId) {
    return $user->id === $userId;
});