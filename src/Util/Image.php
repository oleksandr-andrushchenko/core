<?php

namespace SNOWGIRL_CORE\Util;

use SNOWGIRL_CORE\Service\Storage\Query\Expr;
use SNOWGIRL_CORE\Util;

class Image extends Util
{
    public function doOptimizeImages($dir, $quality = 85, $replace = false)
    {
        $dir = $this->app->dirs['@root'] . '/' . trim($dir, '/');

        $image = $this->app->images->get('_blank');
        $this->output('dir: ' . $dir);

        $optimizePrefix = $image->getOptimizePrefix();

        foreach (glob($dir . '/*.jpg') as $k => $img) {
            $name = basename($img);

            if ($replace) {
                if (0 === strpos($name, $optimizePrefix)) {
                    continue;
                }

                if (file_exists($dir . '/' . $optimizePrefix . $name)) {
                    continue;
                }
            }

            $size = filesize($img);

            if ($tmp = $image->optimize($img, $quality, $replace)) {
                $newSize = filesize($tmp);
                $economy = round((1 - $newSize / $size) * 100);
                $size = round($size / 1000, 2);
                $newSize = round($newSize / 1000, 2);

                $this->output($k . ').' . $name . ' [' . $size . 'kb] -> ' . basename($tmp) . ' [' . $newSize . 'kb] with ' . $economy . '% economy');

                if (!$replace) {
                    if ($economy <= 0) {
                        $this->output('...economy is negative ...deleting');
                        unlink($tmp);
                    } elseif ($economy > 0) {
                        $this->output('...economy is positive ...replacing');
                        unlink($img);
                        rename($tmp, $img);
                    }
                }
            } else {
                $this->output($k . ').' . $name . ' ...optimization is failed');
            }
        }

        return true;
    }

    /**
     * @todo check all tables and execute if has image column...
     * @todo add other tables...
     *
     * @param null $table
     *
     * @return bool
     */
    public function doCutImageDimensions($table = null)
    {
        if ($table) {
            $db = $this->app->services->rdbms;

            if ($this->app->isDev()) {
                $db->updateMany($table, [
                    'image' => new Expr('CONCAT(SUBSTRING(' . $db->quote('image') . ', 1, 32), \'.jpg\')')
                ]);
            } else {
                $db->updateMany($table, [
                    'image' => new Expr('REGEXP_REPLACE(' . $db->quote('image') . ', \'_[^\.]+(.[a-z]+)\', \'\\\\1\')')
                ]);
            }

            foreach (glob($this->app->dirs['@public'] . '/img/0/0/*') as $k => $file) {
                rename($file, preg_replace('/([a-z0-9]{32})_[^\.]+(.[a-z]+)/', '$1$2', $file));
            }
        }

        return true;
    }
}