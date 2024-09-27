<?php


if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        return app('request')->user();
    }
}

if (!function_exists('getCurrentUserId')) {
    $user = getCurrentUser();
    return $user->id ?? 0;
}
