<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/1/18
 * Time: 1:09 PM
 */
namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Widget;

/**
 * Class Google
 * @package SNOWGIRL_CORE\View\Widget
 */
class Google extends Widget
{
    protected $tagId;

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'tagId' => $this->app->config->keys->google_tag_id(false),
        ]);
    }

    protected function getNode()
    {
        return null;
    }

    protected function getInner($template = null)
    {
        return null;
    }

    public function isOk()
    {
        return $this->tagId;
    }
}