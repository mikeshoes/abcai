<?php

namespace cores\driver;

use cores\exception\UnAuthenticatedException;
use cores\provider\UserProvider;
use cores\Request;
use think\Model;

class TokenAuth
{

    protected UserProvider $provider;

    protected ?Model $user = null;

    protected string $inputKey = "user_key";
    private Request $request;

    public function __construct(Request $request, UserProvider $provider)
    {
        $this->request = $request;
        $this->provider = $provider;
    }

    public function user(): ?Model
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        $token = $this->token();

        if (!empty($token)) {
            $user = $this->provider->retrieveByToken($token);
        }

        return $this->user = $user;

    }

    public function token()
    {
        $token = $this->request->param($this->inputKey, "");

        if (empty($token)) {
            $header = $this->request->header('Authorization', '');
            $position = strrpos($header, 'Bearer ');
            if ($position !== false) {
                $header = substr($header, $position + 7);
                $token = strpos($header, ',') !== false ? strstr($header, ',', true) : $header;
            }
        }

        if (empty($token)) {
            $token = $this->request->cookie($this->inputKey, "");
        }

        if (empty($token)) {
            $token = $this->request->header("Access-Token", "");
        }

        return $token;
    }


    /**
     * @throws UnAuthenticatedException
     */
    public function authenticate()
    {
        if (is_null($this->user())) {
            throw new UnAuthenticatedException("请重新登陆");
        }
    }

}