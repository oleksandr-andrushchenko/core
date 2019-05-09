<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/7/16
 * Time: 6:47 AM
 */

namespace SNOWGIRL_CORE\Controller;

use SNOWGIRL_CORE\Controller;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\Service\Logger;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '4096M');

/**
 * Class Command
 *
 * @package SNOWGIRL_CORE\Controller
 */
class Command extends Controller
{
    /**
     * @throws NotFound
     */
    public function initialize()
    {
        if (!$this->app->request->isCli()) {
            throw new NotFound;
        }

//        $this->app->configMaster = null;
        $this->app->services->rdbms->debug(false);
        $this->app->services->mcms->disable();
    }

    public function actionRunTests()
    {
        return $this->output($this->app->tests->run() ? 'DONE' : 'FAILED');
    }

    public function actionRotateSphinx()
    {
        return $this->output($this->app->utils->sphinx->doRotate() ? 'DONE' : 'FAILED');
    }

    public function actionRotateMcms()
    {
        return $this->output($this->app->services->mcms->rotate() ? 'DONE' : 'FAILED');
    }

    public function actionUpdatePages()
    {
        return $this->output($this->app->seo->getPages()->update() ? 'DONE' : 'FAILED');
    }

    public function actionUpdateRobotsTxt()
    {
        return $this->output($this->app->seo->getRobotsTxt()->update() ? 'DONE' : 'FAILED');
    }

    public function actionUpdateSitemap()
    {
        $names = null;

        if ($tmp = $this->app->request->get('param_1', null)) {
            $names = array_map('trim', explode(',', $tmp));
        }

        return $this->output($this->app->seo->getSitemap()->update($names) ? 'DONE' : 'FAILED');
    }

    public function actionUpdateRatings()
    {
        return $this->output($this->app->analytics->updateRatings() ? 'DONE' : 'FAILED');
    }

    public function actionDropRatings()
    {
        return $this->output($this->app->analytics->dropRatings() ? 'DONE' : 'FAILED');
    }

    public function actionUpdateAdsTxt()
    {
        return $this->output($this->app->ads->getAdsTxt()->update() ? 'DONE' : 'FAILED');
    }

    public function actionDefault()
    {
        $this->output('NotFound');
    }

    //@todo
    public function __call($fn, $args)
    {
        D(func_get_args());
        //php cmd database:migrate-data-from-table-to-table --table-from=table1 --table-to=table2
        //should calls $this->app->utils->database->doMigrateDataFromTableToTable(table1, table2)
    }

    protected function output($text, $type = Logger::TYPE_DEBUG)
    {
        $text = is_array($text) ? implode(PHP_EOL, $text) : $text;

        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $this->app->services->logger->make($text, $type);
        return true;
    }
}
