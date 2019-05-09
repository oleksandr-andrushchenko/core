<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/20/17
 * Time: 9:29 PM
 */

namespace SNOWGIRL_CORE\Request;

use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Manager\User as Users;
use SNOWGIRL_CORE\Request;

/**
 * Class Client
 * @package SNOWGIRL_CORE\Request
 */
class Client
{
    public const SESSION_USER_ID = 'user_id';
    public const SESSION_USER_HASH = 'user_hash';
    
    protected $request;
    protected $users;

    public function __construct(Request $request, Users $users)
    {
        $this->request = $request;
        $this->users = $users;
    }

    public function getIp($checkProxy = false)
    {
        if ($checkProxy && $this->request->getServer('HTTP_CLIENT_IP') != null) {
            return $this->request->getServer('HTTP_CLIENT_IP');
        }

        if ($checkProxy && $this->request->getServer('HTTP_X_FORWARDED_FOR') != null) {
            return $this->request->getServer('HTTP_X_FORWARDED_FOR');
        }

        return $this->request->getServer('REMOTE_ADDR');
    }

    protected $user;

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

            if (!$user = $this->users->find($id)) {
                return $this->user = false;
            }

            $this->user = $user;
        }

        return $this->user;
    }

    public function isLoggedIn()
    {
        return $this->getUser() instanceof User;
    }

    public function logIn($user, $remember = true)
    {
        if (!$user = $this->makeUserObject($user)) {
            return false;
        }

        $time = time() + ($remember ? 7 : 1) * 24 * 60 * 60;

        $this->request->getCookie()->set(self::SESSION_USER_ID, $user->getId(), $time, '/')
            ->set(self::SESSION_USER_HASH, $this->makeUserSessionSecurityHash($user->getId()), $time, '/');

        return true;
    }

    public function logOut($user = null)
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
     * @param string $default
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

    protected function makeUserSessionSecurityHash($id)
    {
        return md5('adfsd' . $id);
    }

    /**
     * @param $user
     * @return bool|User
     */
    protected function makeUserObject($user)
    {
        if (!$user instanceof User) {
            $user = $this->users->find($user);
        }

        if (!$user) {
            return false;
        }

        return $user;
    }

    public function isAdmin()
    {
        return $this->isLoggedIn() && $this->getUser()->isRole(User::ROLE_ADMIN);
    }
}