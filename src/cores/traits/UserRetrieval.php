<?php

namespace cores\traits;

use think\Model;

trait UserRetrieval
{
    protected $userResolver = null;

    protected $tokenResolver = null;

    public function getUserResolver()
    {
        return $this->userResolver ?: function () {
            //
        };
    }

    public function setUserResolver($userResolver)
    {
        return $this->userResolver = $userResolver;
    }

    /**
     * @return mixed | Model | null
     */
    public function user()
    {
        return call_user_func($this->getUserResolver());
    }

    public function setTokenResolver($tokenResolver)
    {
        return $this->tokenResolver = $tokenResolver;
    }

    public function getTokenResolver()
    {
        return $this->tokenResolver ?: function () {
        };
    }

    public function token()
    {
        return call_user_func($this->getTokenResolver());
    }
}