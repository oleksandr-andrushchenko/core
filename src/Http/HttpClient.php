<?php

namespace SNOWGIRL_CORE\Http;

use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Manager\User as Users;
use Throwable;

class HttpClient
{
    private const SESSION_USER_ID = 'user_id';
    private const SESSION_USER_HASH = 'user_hash';

    private $request;
    private $users;
    private $user;

    public function __construct(HttpRequest $request, Users $users)
    {
        $this->request = $request;
        $this->users = $users;
    }

    public function getIp(bool $checkProxy = false)
    {
        if ($checkProxy && $this->request->getServer('HTTP_CLIENT_IP') != null) {
            return $this->request->getServer('HTTP_CLIENT_IP');
        }

        if ($checkProxy && $this->request->getServer('HTTP_X_FORWARDED_FOR') != null) {
            return $this->request->getServer('HTTP_X_FORWARDED_FOR');
        }

        return $this->request->getServer('REMOTE_ADDR');
    }

    /**
     * @return bool|User
     */
    public function getUser()
    {
        if (null === $this->user) {
            if (!$this->request->getCookie()->_isset(self::SESSION_USER_ID)) {
                return $this->user = false;
            }

            $id = $this->request->getCookie()->get(self::SESSION_USER_ID);

            if (!$this->request->getCookie()->_isset(self::SESSION_USER_HASH)) {
                return $this->user = false;
            }

            if ($this->request->getCookie()->get(self::SESSION_USER_HASH) != $this->makeUserSessionSecurityHash($id)) {
                return $this->user = false;
            }

            try {
                if (!$user = $this->users->find($id)) {
                    return $this->user = false;
                }
            } catch (Throwable $ex) {
                return $this->user = false;
            }

            $this->user = $user;
        }

        return $this->user;
    }

    public function isLoggedIn(): bool
    {
        return $this->getUser() instanceof User;
    }

    public function logIn($user, bool $remember = true): bool
    {
        if (!$user = $this->makeUserObject($user)) {
            return false;
        }

        $time = time() + ($remember ? 7 : 1) * 24 * 60 * 60;

        $this->request->getCookie()->set(self::SESSION_USER_ID, $user->getId(), $time, '/')
            ->set(self::SESSION_USER_HASH, $this->makeUserSessionSecurityHash($user->getId()), $time, '/');

        return true;
    }

    public function logOut($user = null): bool
    {
        if ($user) {
            if (!$user = $this->makeUserObject($user)) {
                return false;
            }
        } else {
            $user = null;
        }

        $this->request->getCookie()->_unset(self::SESSION_USER_ID, '/')
            ->_unset(self::SESSION_USER_HASH, '/');

        return true;
    }

    /**
     * @todo...
     *
     * @param string $default
     *
     * @return string
     */
    public function getLocale($default = 'en_EN')
    {
        $output = null;

//        if ($this->isLoggedIn()) {
//            //@todo get locale from the client
//        }
//
//        if (null == $output) {
//            //@todo get locale from session
//        }
//
//        if (null == $output) {
//            //@todo get locale from request
//        }

        if (null == $output) {
            $output = $default;
        }

        return $output;
    }

    private function makeUserSessionSecurityHash($id)
    {
        return md5('adfsd' . $id);
    }

    /**
     * @param $user
     *
     * @return bool|User
     */
    private function makeUserObject($user)
    {
        if (!$user instanceof User) {
            $user = $this->users->find($user);
        }

        if (!$user) {
            return false;
        }

        return $user;
    }
}