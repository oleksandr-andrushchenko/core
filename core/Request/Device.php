<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/23/18
 * Time: 10:58 PM
 */

namespace SNOWGIRL_CORE\Request;

use SNOWGIRL_CORE\Request;

/**
 * Class Device
 *
 * @package SNOWGIRL_CORE\Request
 */
class Device
{
    protected $request;
    protected $session;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected $adapter;

    /**
     * @return \Mobile_Detect
     */
    public function getAdapter()
    {
        if (null === $this->adapter) {
            $this->adapter = new \Mobile_Detect(
                $this->request->getServer()
//                $this->request->getServer('HTTP_USER_AGENT')
            );
        }

        return $this->adapter;
    }

    public const SESSION_ROBOT = 'is_robot';

    protected $robot;

    public function isRobot()
    {
        if (null === $this->robot) {
            $session = $this->request->getSession();

            if ($this->request->getSession()->_isset(self::SESSION_ROBOT)) {
                $this->robot = $session->get(self::SESSION_ROBOT);
            } else {
                $this->robot = false;

                $ua = strtolower($this->request->getServer('HTTP_USER_AGENT'));

                foreach ([
                             'googlebot',
                             'yandexbot',
                             'yandexmobilebot',
                             'bingbot',
                             'bingpreview',
                             'mj12bot',
                             'dotbot',
                             'ahrefsbot',
                             'semrushbot',
                             'sogou',
                             'ltx71',
                             'semrushbot',
                             'mail.ru_bot',
                             'slurp',
                             'grapeshotcrawler',
                             'blexbot'
                         ] as $check) {
                    if (strpos($ua, $check) !== false) {
                        $this->robot = true;
                        break;
                    }
                }

                $session->set(self::SESSION_ROBOT, $this->robot);
            }
        }

        return $this->robot;
    }

    public const SESSION_MOBILE = 'is_mobile';

    protected $mobile;

    /**
     * @return bool|null
     */
    public function isMobile()
    {
        if (null === $this->mobile) {
            $session = $this->request->getSession();

            if ($session->_isset(self::SESSION_MOBILE)) {
                $this->mobile = $session->get(self::SESSION_MOBILE);
            } else {
                $this->mobile = $this->getAdapter()->isMobile();

                //@todo remove this fix when adapter updated...
                if ($this->mobile) {
                    $this->mobile = !$this->isTablet();
                }

                $session->set(self::SESSION_MOBILE, $this->mobile);
            }
        }

        return $this->mobile;
    }

    public const SESSION_TABLET = 'is_tablet';

    protected $tablet;

    public function isTablet()
    {
        if (null === $this->tablet) {
            $session = $this->request->getSession();

            if ($session->_isset(self::SESSION_TABLET)) {
                $this->tablet = $session->get(self::SESSION_TABLET);
            } else {
                $this->tablet = $this->getAdapter()->isTablet();
                $session->set(self::SESSION_TABLET, $this->tablet);
            }
        }

        return $this->tablet;
    }

    public function isDesktop()
    {
        return !$this->isTablet() && !$this->isMobile();
    }
}