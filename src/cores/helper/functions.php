<?php


if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        return app('request')->user();
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId()
    {
        $user = getCurrentUser();
        if ($user) {
            return $user->id;
        }
        return 0;
    }
}
