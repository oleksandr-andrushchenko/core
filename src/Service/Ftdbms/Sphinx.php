<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 21.03.14
 * Time: 0:42
 * To change this template use File | Settings | File Templates.
 */

namespace SNOWGIRL_CORE\Service\Ftdbms;

use SNOWGIRL_CORE\Service\Ftdbms;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;
use SNOWGIRL_CORE\Service\Rdbms\Mysql;
use SNOWGIRL_CORE\Service\Storage\Query;

/**
 * mysql -P9306 --protocol=tcp --prompt='sphinxQL> '
 * sudo service sphinxsearch stop
 * sudo pkill -f searchd
 * sudo indexer --all
 * sudo service sphinxsearch start
 *
 * Class Sphinx
 *
 * @property Mysql client
 * @method Mysql getClient($force = false)
 * @method Mysql client()
 * @package SNOWGIRL_CORE\Service\Ftdbms
 */
class Sphinx extends Ftdbms
{
    /**
     * @param null $key
     *
     * @return array[]
     */
    public function reqToArrays($key = null)
    {
        return $this->client()->reqToArrays($key);
    }

    /**
     * @return array
     */
    public function reqToArray()
    {
        return $this->client()->reqToArray();
    }

    /**
     * @param      $className
     * @param null $key
     *
     * @return array
     */
    public function reqToObjects($className, $key = null)
    {
        return $this->client()->reqToObjects($className, $key);
    }

    protected $socket;
    protected $prefix;

    public function getPrefix()
    {
        return $this->prefix;
    }

    protected $service;

    public function getService()
    {
        return $this->service;
    }

    protected $searchd;
    protected $indexer;

    public function getIndexer()
    {
        return $this->indexer;
    }

    //sphinx's schemas folder
    protected $schemas;

    public function getSchemas()
    {
        return $this->schemas;
    }

    protected $masterServices = true;

    public function getMasterServices(): bool
    {
        return $this->masterServices;
    }

    protected function _getClient()
    {
        return (new Mysql([
            'host' => $this->host,
            'port' => $this->port,
            'socket' => $this->socket,
            'debug' => $this->debug,
            'logger' => $this->logger
        ]))->setServiceName($this->getServiceName())
            ->setProviderName($this->getProviderName());
    }

    protected function _dropClient()
    {
        $this->client->dropClient();
    }

    /**
     * @param Query $query
     *
     * @return $this|Ftdbms|\SNOWGIRL_CORE\Service\Storage
     */
    public function _req(Query $query)
    {
        $tmp = explode('?', $query->text);
        $query->text = '';

        foreach ($query->params as $k => $v) {
            $query->text .= $tmp[$k] . (is_string($v) ? "'$v'" : $v);
        }

        $query->text .= end($tmp);
        $query->params = [];

        $this->client()->req($query);

        return $this;
    }

    /**
     * @param            $what
     * @param Query|null $query
     *
     * @return array
     */
    public function selectMany($what, Query $query = null)
    {
        $query = Query::normalize($query);
        $query->params = [];

        $query->text = implode(' ', [
            $this->client()->makeSelectSQL($query->columns, false, $query->params),
            $this->client()->makeFromSQL(array_map(function ($table) {
                return $this->makeTable($table);
            }, (array)$what)),
            $query->where ? $this->client()->makeWhereSQL($query->where, $query->params) : '',
            $query->groups ? $this->client()->makeGroupSQL($query->groups, $query->params) : '',
            $query->orders ? $this->client()->makeOrderSQL($query->orders, $query->params) : '',
            false === $query->limit ? '' : $this->makeLimitSQL($query->offset, $query->limit, $query->params),
            false === $query->limit ? '' : $this->makeOptionsSQL($query->offset, $query->limit, $query->params)
        ]);

        return $this->req($query)->reqToArrays();
    }

    /**
     * @param            $what
     * @param array      $values
     * @param Query|null $query
     *
     * @return int
     */
    public function updateMany($what, array $values, Query $query = null)
    {
        return $this->connect()->client()->updateMany($this->makeTable($what), $values, $query);
    }

    public function makeLimitSQL($offset, &$limit, array &$bind)
    {
        $limit = $limit ?: 99999;
        return $this->client()->makeLimitSQL($offset, $limit, $bind);
    }

    public function makeOptionsSQL($offset, $limit, &$bind)
    {
        $tmp = [];

        $tmp['max_matches'] = $offset + $limit;

        foreach ($tmp as $k => $v) {
            $tmp[$k] = $k . ' = ?';
            $bind[] = $v;
        }

        return $tmp ? ('OPTION ' . implode(' ', $tmp)) : '';
    }

    /**
     * @param            $what
     * @param Query|null $query
     *
     * @return array|int|int[]
     */
    public function selectCount($what, Query $query = null)
    {
        $query = $query ? $query : new Query;

        $query->columns = [];

        if ($query->groups) {
            $query->columns[] = new Expr('@groupby AS ' . $this->client()->quote($query->groups));
        }

        $query->columns[] = new Expr('COUNT(*) AS ' . $this->client()->quote('cnt'));
        $query->limit = false;

        $tmp = $this->selectMany($what, $query);

        if ($query->groups) {
            $output = [];

            foreach ($tmp as $i) {
                $output[$i[$query->groups]] = (int)$i['cnt'];
            }

            return $output;
        }

        $output = (int)$tmp[0]['cnt'];

        return $output;
    }

    public function foundRows()
    {
        foreach ($this->req(new Query('SHOW META'))->reqToArrays() as $i) {
            if ('total_found' == $i['Variable_name']) {
                return (int)$i['Value'];
            }
        }

        return null;
    }

    public static function prepareQuery($v)
    {
        $result = trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $v));

        if ($v = preg_split('/[\s,-]+/', $result, 5)) {
            $tmp = [];

            foreach ($v as $vv) {
                $tmp[] = '(' . $vv . ' | *' . $vv . '*)';
            }

            $result = implode(' & ', $tmp);
        }

        return $result;
    }

    public function makeTable($rdbmsTable)
    {
        return $this->prefix . '_' . $rdbmsTable;
    }

    public function prepareText($q)
    {
        return implode('|', explode(' ', trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $q))));
    }

    public function quote($v, $table = '')
    {
        return $this->client()->quote($v, $table);
    }

    public function numRows()
    {
        // TODO: Implement numRows() method.
    }

    public function insertedId()
    {
        // TODO: Implement insertedId() method.
    }

    public function insertOne($what, array $values, Query $query = null)
    {
        // TODO: Implement insertOne() method.
    }

    public function insertMany($what, array $values, Query $query = null)
    {
        // TODO: Implement insertMany() method.
    }

    public function delete($what, Query $query = null)
    {
        // TODO: Implement delete() method.
    }

    public function deleteMany($what, Query $query = null)
    {
        // TODO: Implement deleteMany() method.
    }

    public function affectedRows()
    {
        // TODO: Implement affectedRows() method.
    }

    public function warnings()
    {
        // TODO: Implement warnings() method.
    }

    public function errors()
    {
        // TODO: Implement errors() method.
    }

    public function getErrors(\Exception $ex = null)
    {
        // TODO: Implement getErrors() method.
    }

    public function _getRefreshConnectionMessage()
    {
        // TODO: Implement _getRefreshConnectionMessage() method.
    }
}
