<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/22/16
 * Time: 2:22 PM
 */

namespace SNOWGIRL_CORE\Script;

use SNOWGIRL_CORE\Script;

/**
 * Class Css
 * @package SNOWGIRL_CORE\Script
 */
class Css extends Script
{
    protected static $dir = 'css';

    protected static $regexp = array(
        "`^([\t\s]+)`ism" => '',
        "`^\/\*(.+?)\*\/`ism" => "",
        "`([\n\A;]+)\/\*(.+?)\*\/`ism" => "$1",
        "`([\n\A;\s]+)//(.+?)[\n\r]`ism" => "$1\n",
        "`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism" => "\n",

        // Remove comment(s)
        '#("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')|\\/\\*(?!\\!)(?>.*?\\*\\/)|^\\s*|\\s*$#s' => '$1',
        // Remove unused white-space(s)
        '#("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|\\/\\*(?>.*?\\*\\/))|\\s*+;\\s*+(})\\s*+|\\s*+([*$~^|]?+=|[{};,>~+]|\\s*+-(?![0-9\\.])|!important\\b)\\s*+|([[(:])\\s++|\\s++([])])|\\s++(:)\\s*+(?!(?>[^{}"\']++|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')*+{)|^\\s++|\\s++\\z|(\\s)\\s+#si' => '$1$2$3$4$5$6$7',
        // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
        '#(?<=[\\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si' => '$1',
        // Replace `:0 0 0 0` with `:0`
        '#:(0\\s+0|0\\s+0\\s+0\\s+0)(?=[;\\}]|\\!important)#i' => ':0',
        // Replace `background-position:0` with `background-position:0 0`
        '#(background-position):0(?=[;\\}])#si' => '$1:0 0',
        // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
        '#(?<=[\\s:,\\-])0+\\.(\\d+)#s' => '.$1',
        // Minify string value
        '#(\\/\\*(?>.*?\\*\\/))|(?<!content\\:)([\'"])([a-z_][a-z0-9\\-_]*?)\\2(?=[\\s\\{\\}\\];,])#si' => '$1$3',
        '#(\\/\\*(?>.*?\\*\\/))|(\\burl\\()([\'"])([^\\s]+?)\\3(\\))#si' => '$1$2$4$5',
        // Minify HEX color code
        '#(?<=[\\s:,\\-]\\#)([a-f0-6]+)\\1([a-f0-6]+)\\2([a-f0-6]+)\\3#i' => '$1$2$3',
        // Replace `(border|outline):none` with `(border|outline):0`
        '#(?<=[\\{;])(border|outline):none(?=[;\\}\\!])#' => '$1:0',
        // Remove empty selector(s)
        '#(\\/\\*(?>.*?\\*\\/))|(^|[\\{\\}])(?:[^\\s\\{\\}]+)\\{\\}#s' => '$1$2',
    );

    public static function minifyContent($content)
    {
        if ('' === trim($content)) {
            return $content;
        }

        return preg_replace(array_keys(self::$regexp), self::$regexp, $content);
    }

    protected static function addContentHtmlTag($content)
    {
        return '<style type="text/css">' . $content . '</style>';
    }

    protected static function addHttpHtmlTag($uri)
    {
        return '<link href="' . $uri . '" rel="stylesheet" type="text/css">';
    }

    protected function getUrlFileServerName($url)
    {
        $map = static::getServerLinkMap();
        $tmp = str_replace(array_keys($map), $map, dirname($this->getServerName())) . '/' . $url;

        if (false !== strpos($tmp, '..')) {
            $new = array();
            $tmp2 = explode('/', $tmp);
            $tmp2 = array_reverse($tmp2);
//            var_dump($tmp2);

            $i = 0;
            foreach ($tmp2 as $k => $v) {
                if ('..' == $v) {
                    $i++;
                } else {
                    if ($i > 0) {
                        $i--;
                    } else {
                        $new[] = $tmp2[$k + $i];
                    }
                }
            }

            $new = array_reverse($new);
            $new = implode('/', $new);
            $tmp = $new;
        }
//        var_dump($tmp);
        return $tmp;
    }

    protected function modifyUrl($uri)
    {
//        return  $uri;
        $uri = trim($uri, '"\'');

        if (0 === strpos($uri, 'data:')) {
            $tmp = $uri;
        } elseif (filter_var($uri, FILTER_VALIDATE_URL) !== false) {
            $tmp = $uri;
        } else {
            $tmp = $this->getUrlFileServerName($uri);
            $tmp = str_replace(realpath(self::$app->dirs['@web'] . '/'), '', $tmp);
            $tmp = $this->domain . '/' . trim($tmp,'/');
        }

        return $tmp;
    }

    protected function rawContentToTmpPath($content)
    {
        return preg_replace_callback("/url\(([^\)]+)\)/", function ($m) {
            return 'url(\'' . $this->modifyUrl($m[1]) . '\')';
        }, $content);
    }

    protected function rawContentToHttp2($content)
    {
//        return parent::rawContentToHttp($content);
        return preg_replace_callback("/url\(([^\)]+)\)/", function ($m) {
            return 'url(\'' . $this->modifyUrl($m[1]) . '\')';
        }, $content);
    }

    protected function rawContentToHttp($content)
    {
        return preg_replace_callback("/url\(([^\)]+)\)/", function ($m) {
            $uri = $m[1];
            $uri = trim($uri, "\"'");

            if (0 === strpos($uri, 'data:')) {
                $tmp = $uri;
            } elseif (filter_var($uri, FILTER_VALIDATE_URL) !== false) {
                $tmp = $uri;
            } else {
                $tmp = implode('/', array(
                    $this->getBaseHttpPath(),
                    $uri
                ));
            }

            $tmp = 'url(\'' . $tmp . '\')';

            return $tmp;
        }, $content);
    }
}