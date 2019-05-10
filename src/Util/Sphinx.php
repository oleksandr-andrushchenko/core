<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 4/19/19
 * Time: 2:36 PM
 */

namespace SNOWGIRL_CORE\Util;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Helper;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Helper\Classes;
use SNOWGIRL_CORE\Util;

/**
 * mysql -P9306 --protocol=tcp --prompt='sphinxQL> '
 * sudo service sphinxsearch stop
 * sudo pkill -f searchd
 * sudo indexer --all
 * sudo service sphinxsearch start
 *
 * Class Sphinx
 *
 * @package SNOWGIRL_CORE\Util
 */
class Sphinx extends Util
{
    /**
     * indexer gw_ru_page_catalog --rotate
     *
     * @param bool|false $customEntities
     *
     * @return string
     */
    public function doRotate($customEntities = false)
    {
        $db = $this->app->storage->sphinx(null, $this->app->storage->sphinx->getMasterServices());

        $this->doStart();
        sleep(3);
        return $this->run(implode(' ', [
            $db->getIndexer(),
            implode(' ', array_map(function ($entity) {
                /** @var Entity $entity */
                return $this->app->storage->sphinx->makeTable($entity::getTable());
            }, $customEntities ? Arrays::cast($customEntities) : $this->getEntities())),
            '--rotate'
        ]));
    }

    protected function run($cmd)
    {
        $this->generateConfig();
        return Helper::runShell($cmd);
    }

    public function doStart()
    {
        return $this->run(implode(' ', [
            $this->app->storage->sphinx->getService(),
            'start'
        ]));
    }

    public function doRestart()
    {
        return $this->run(implode(' ', [
            $this->app->storage->sphinx->getService(),
            'restart'
        ]));
    }

    protected function preGenerateConfig()
    {

    }

    protected function getConfigRawAttrs($entity)
    {
        false && $entity;
        return [];
    }

    protected function getDataPath($index)
    {
        $dir = implode('/', [
            $this->app->dirs['@tmp'],
            'sphinx'
        ]);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
            $user = $this->app->config->server->user;
            shell_exec('chown ' . $user . ':' . $user . ' -R ' . $dir);
            shell_exec('chmod g+w -R ' . $dir);
        }

        return $dir . '/' . $index;
    }

    protected function generateConfig()
    {
        if (!$entities = $this->getEntities()) {
            return true;
        }

        $db = $this->app->storage->sphinx(null, $this->app->storage->sphinx->getMasterServices());

        $this->preGenerateConfig();

        $columnTypeToAttrMap = [
            Entity::COLUMN_INT => 'uint',
            Entity::COLUMN_FLOAT => 'float',
//            Entity::TYPE_BOOL => 'bool',
            Entity::COLUMN_TIME => 'timestamp',
            Entity::COLUMN_TEXT => 'string'
        ];

        $config = [];

        /** @var Entity $entity */

        foreach ($entities as $entity) {
            $mysql = $this->app->storage->mysql(null, $this->app->managers->getByEntityClass($entity)->getMasterServices());

            //@todo compare self sphinx connection params with mysql params and fix... checkout[sudo php cmd.php rotate-sphinx]
            $tmp = [
                'index' => $index = $this->app->storage->sphinx->makeTable($entity::getTable()),
                'table' => $entity::getTable(),
                'pk' => $entity::getPk(),
                'schema' => $db->getPrefix(),
                'db_type' => strtolower($mysql->getProviderName()),
                'db_host' => $mysql->getHost(),
                'db_user' => $mysql->getUser(),
                'db_pass' => $mysql->getPassword(),
                'db_schema' => $mysql->getSchema(),
                'db_port' => $mysql->getPort(),
                'db_socket' => $mysql->getSocket(),
                'data' => $this->getDataPath($index)
            ];

            $columns = [];
            $fields = [];
            $rawAttrs = [];

            $attributes = array_combine(array_values($columnTypeToAttrMap), array_fill(0, count($columnTypeToAttrMap), []));

            foreach ($entity::getColumns() as $column => $settings) {
                if (!isset($settings['type'])) {
                    $settings['type'] = Entity::COLUMN_TEXT;
                }

                if (in_array(Entity::FTDBMS_FIELD, $settings)) {
                    $fields[] = $column;
                } elseif (in_array(Entity::FTDBMS_ATTR, $settings) && isset($columnTypeToAttrMap[$settings['type']])) {
                    $attributes[$columnTypeToAttrMap[$settings['type']]][] = $column;
                }
            }

            if ($tmp2 = $this->getConfigRawAttrs($entity)) {
                $rawAttrs = array_merge($rawAttrs, $tmp2);
            }

            $columns = array_merge($columns, $fields);

            foreach ($attributes as $type => $attr) {
                $columns = array_merge($columns, $attr);
                $rawAttrs = array_merge($rawAttrs, array_map(function ($v) use ($type) {
                    return 'sql_attr_' . $type . '          = ' . $v;
                }, $attr));
            }

            foreach ($attributes['timestamp'] as $k) {
                $columns = array_diff($columns, [$k]);
                $columns[] = 'UNIX_TIMESTAMP(' . $k . ') AS ' . $k;
            }

            $tmp['columns'] = implode(', ', $columns);
            $tmp['attributes'] = implode("\n     ", $rawAttrs);

            $tmp['fields'] = implode("\n     ", array_map(function ($v) {
                return 'sql_field_string          = ' . $v;
            }, $fields));

            if ($where = $this->getFetchWhereForEntity($entity)) {
                $bind = [];
                $tmp['where'] = $mysql->makeWhereSQL($where, $bind);
            } else {
                $tmp['where'] = '';
            }

            $template = file_get_contents($this->app->dirs['@core'] . '/config.sphinx_index.txt');

            $config[] = str_replace(array_map(function ($k) {
                return '{{' . $k . '}}';
            }, array_keys($tmp)), $tmp, $template);
        }

        $config = implode("\n\n", $config);

        file_put_contents($db->getSchemas() . '/' . $db->getPrefix() . '.conf', $config);

        return true;
    }

    protected $entities;

    /**
     * @return array
     */
    protected function getEntities(): array
    {
        if (null === $this->entities) {
            $this->entities = array_filter(Classes::getInNsCheckAppNs('Entity', $this->app), function ($entity) {
                /** @var Entity $entity */
                return $entity::isFtdbmsIndex();
            });
        }

        return $this->entities;
    }

    protected function getFetchWhereForEntity($entity)
    {
        false && $entity;
        return null;
    }
}