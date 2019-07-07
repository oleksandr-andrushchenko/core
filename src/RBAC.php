<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Exception\HTTP\Forbidden;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;

class RBAC
{
    public const ROLE_NONE = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_COPYWRITER = 2;
    public const ROLE_SEO = 3;
    public const ROLE_MANAGER = 4;

    public static $roleHierarchy = [
        self::ROLE_ADMIN => [
            self::ROLE_NONE,
            self::ROLE_COPYWRITER,
            self::ROLE_SEO,
            self::ROLE_MANAGER
        ],
        self::ROLE_MANAGER => [
            self::ROLE_NONE,
            self::ROLE_COPYWRITER,
            self::ROLE_SEO
        ],
        self::ROLE_SEO => [
            self::ROLE_NONE,
            self::ROLE_COPYWRITER
        ],
        self::ROLE_COPYWRITER => [
            self::ROLE_NONE
        ]
    ];

    public const PERM_NONE = 0;
    public const PERM_ALL = 1;

    public const PERM_CONTROL_PAGE = 2;
    public const PERM_DATABASE_PAGE = 3;
    public const PERM_GENERATE_SITEMAP = 4;
    public const PERM_UPLOAD_IMG = 5;
    public const PERM_DELETE_IMG = 6;
    public const PERM_PAGES_PAGE = 7;
    public const PERM_PAGE_PAGE = 8;
    public const PERM_ROTATE_MCMS = 9;
    public const PERM_ROTATE_FTDMS = 15;
    public const PERM_CREATE_ROW = 10;
    public const PERM_UPDATE_ROW = 11;
    public const PERM_DELETE_ROW = 12;
    public const PERM_SHOW_TRACE = 13;
    public const PERM_ADD_USER = 14;

    /** @var App */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    protected $clientPermissions;

    public function hasPerm(int $permissionId): bool
    {
        if (!$this->app->request->getClient()->isLoggedIn()) {
            return false;
        }

        if (null === $this->clientPermissions) {
            $user = $this->app->request->getClient()->getUser();
            $db = $this->app->services->rdbms;

            $roleId = [];

            if (isset(self::$roleHierarchy[$user->getRoleId()])) {
                $roleId = self::$roleHierarchy[$user->getRoleId()];
            }

            $roleId[] = $user->getRoleId();

            $params = [];

            $params[] = $db->quote('user_id') . ' = ? OR ' . $db->quote('role_id') . ' IN (' . implode(', ', array_fill(0, count($roleId), '?')) . ')';
            $params[] = $user->getId();
            $params = array_merge($params, $roleId);

            $this->clientPermissions = $this->app->managers->rbac->clear()
                ->setWhere(new Expr(...$params))
                ->getColumn('permission_id');
        }

        if (in_array(self::PERM_ALL, $this->clientPermissions)) {
            return true;
        }

        if (in_array($permissionId, $this->clientPermissions)) {
            return true;
        }

        return false;
    }

    /**
     * @param int $permissionId
     *
     * @throws void
     */
    public function checkPerm(int $permissionId)
    {
        if (!$this->hasPerm($permissionId)) {
            throw new Forbidden;
        }
    }

    public function hasRole(int $roleId, int $roleId2 = null): bool
    {
        if (!$this->app->request->getClient()->isLoggedIn()) {
            return false;
        }

        $user = $this->app->request->getClient()->getUser();

        return 0 < count(array_intersect([$user->getRoleId()], func_get_args()));
    }

    /**
     * @param int      $roleId
     * @param int|null $roleId2
     *
     * @throws void
     */
    public function checkRole(int $roleId, int $roleId2 = null)
    {
        if (!$this->hasRole(...func_get_args())) {
            throw new Forbidden;
        }
    }
}