<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/22/16
 * Time: 2:22 PM
 */

namespace SNOWGIRL_CORE\Script;

//use JShrink\Minifier;
use SNOWGIRL_CORE\Script;

/**
 * Class Js
 * @package SNOWGIRL_CORE\Script
 */
class Js extends Script
{
    protected static $dir = 'js';

    protected static $regex = array(
        '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/' => '',

        // Remove comment(s)
        '#\\s*("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')\\s*|\\s*\\/\\*(?!\\!|@cc_on)(?>[\\s\\S]*?\\*\\/)\\s*|\\s*(?<![\\:\\=])\\/\\/.*(?=[\\n\\r]|$)|^\\s*|\\s*$#' => '$1',
        // Remove white-space(s) outside the string and regex
        '#("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|\\/\\*(?>.*?\\*\\/)|\\/(?!\\/)[^\\n\\r]*?\\/(?=[\\s.,;]|[gimuy]|$))|\\s*([!%&*\\(\\)\\-=+\\[\\]\\{\\}|;:,.<>?\\/])\\s*#s' => '$1$2',
        // Remove the last semicolon
        '#;+\\}#' => '}',
        // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
        '#([\\{,])([\'])(\\d+|[a-z_][a-z0-9_]*)\\2(?=\\:)#i' => '$1$3',
        // --ibid. From `foo['bar']` to `foo.bar`
        '#([a-z0-9_\\)\\]])\\[([\'"])([a-z_][a-z0-9_]*)\\2\\]#i' => '$1.$3',
    );

    public static function minifyContent($content)
    {
        if ('' === trim($content)) {
            return $content;
        }

        return $content;
//        return preg_replace(array_keys(self::$regexp), self::$regexp, $input);
//        return Minifier::minify($content, array('flaggedComments' => false));
    }

    public static function addContentHtmlTag($content)
    {
        return '<script type="text/javascript"' . '>' . $content . '</script>';
    }

    public static function addHttpHtmlTag($uri)
    {
        return '<script type="text/javascript" src="' . $uri . '"></script>';
    }
}