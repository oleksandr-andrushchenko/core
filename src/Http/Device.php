<?php

namespace SNOWGIRL_CORE\Http;

class Device
{
    protected $request;
    protected $session;
    protected $adapter;
    public const SESSION_ROBOT = 'is_robot';

    protected $robot;
    public const SESSION_MOBILE = 'is_mobile';

    protected $mobile;
    public const SESSION_TABLET = 'is_tablet';

    protected $tablet;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function getAdapter(): \Mobile_Detect
    {
        if (null === $this->adapter) {
            $this->adapter = new \Mobile_Detect(
                $this->request->getServer()
//                $this->request->getServer('HTTP_USER_AGENT')
            );
        }

        return $this->adapter;
    }

    public function isRobot(): bool
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

    public function isMobile(): bool
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

    public function isTablet(): bool
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

    public function isDesktop(): bool
    {
        return !$this->isTablet() && !$this->isMobile();
    }
}