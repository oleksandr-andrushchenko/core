<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 6/18/17
 * Time: 1:36 PM
 */
namespace SNOWGIRL_CORE\Profiler;

use SNOWGIRL_CORE\Service\Profiler;

/**
 * Install:
 * 1) +symlink: profiler -> vendor/lox/xhprof/xhprof_html/
 * 2) +separate host:
 *
 * <VirtualHost *:80>
 * ServerName local.profiler.example.com
 * DocumentRoot /var/www/example.com/profiler
 * php_value auto_prepend_file "/var/www/example.com/vendor/snowgirl-core/app.profiler-prepend.php"
 * </VirtualHost>
 *
 * Class XHProf
 * @package SNOWGIRL_CORE\Profiler
 */
class Xhprof extends Profiler
{
    protected $dataDir;
    protected $libDir;

    public function prepare()
    {
        ini_set('xhprof.output_dir', $this->dataDir);
    }

    public function setDataDir($dir)
    {
        ini_set('xhprof.output_dir', $dir);
        $this->dataDir = $dir;
    }

    public function setLibDir($dir)
    {
        $this->libDir = $dir;
    }

    protected function isCan()
    {
        return extension_loaded('xhprof');
    }

    protected function makeFlagsMap()
    {
        return array(
            self::FLAG_MEMORY => XHPROF_FLAGS_MEMORY,
            self::FLAG_CPU => XHPROF_FLAGS_CPU,
            self::FLAG_NO_BUILT_INS => XHPROF_FLAGS_NO_BUILTINS
        );
    }

    protected function _enable()
    {
        xhprof_enable(array_sum($this->makeFlags()));
    }

    protected function _disable()
    {
        return xhprof_disable();
    }

    protected function _save()
    {
        /** @noinspection PhpIncludeInspection */
        require_once $this->libDir . '/utils/xhprof_lib.php';
        /** @noinspection PhpIncludeInspection */
        require_once $this->libDir . '/utils/xhprof_runs.php';

        $run = new \XHProfRuns_Default($this->dataDir);
        return $run->save_run(xhprof_disable(), $this->namespace);
    }

    protected function _makeReportUri($id)
    {
        return 'index.php?run=' . $id . '&source=' . $this->namespace;
    }

//    public function display()
//    {
//        return new View('@snowgirl-core/xhprof.phtml', array(
//            'host' => $this->host
//        ));
//    }
}