<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    if (!$user) {
        return false;
    }
    return (int) $user->id === (int) $userId;
});


