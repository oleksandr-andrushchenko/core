<?php

namespace SNOWGIRL_CORE\Script;

use SNOWGIRL_CORE\Script;

class Js extends Script
{
    protected $dir = 'js';

    private $regex = [
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
    ];

    public function minifyContent(string $content): string
    {
        if ('' === trim($content)) {
            return $content;
        }

        return $content;
//        return preg_replace(array_keys($this->regexp), $this->regexp, $content);
//        return Minifier::minify($content, ['flaggedComments' => false]);
    }

    public static function addContentHtmlTag(string $content): string
    {
        return '<script type="text/javascript"' . '>' . $content . '</script>';
    }

    public static function addHttpHtmlTag(string $uri): string
    {
        return '<script type="text/javascript" src="' . $uri . '"></script>';
    }
}