<?php

namespace cores\provider;

interface UserProvider
{

    public function retrieveById($identifier);

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param string $token
     */
    public function retrieveByToken(String $token);

}