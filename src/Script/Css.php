<?php

namespace SNOWGIRL_CORE\Script;

use SNOWGIRL_CORE\Script;

class Css extends Script
{
    protected $dir = 'css';

    private $regexp = [
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
    ];

    public function minifyContent(string $content): string
    {
        if ('' === trim($content)) {
            return $content;
        }

        return preg_replace(array_keys($this->regexp), $this->regexp, $content);
    }

    public static function addContentHtmlTag(string $content): string
    {
        return '<style type="text/css">' . $content . '</style>';
    }

    public static function addHttpHtmlTag(string $uri): string
    {
        return '<link href="' . $uri . '" rel="stylesheet" type="text/css">';
    }

    protected function getUrlFileServerName(string $url): string
    {
        $map = [];

        foreach ($this->aliases as $alias) {
            $map[str_replace('@dir', $this->dir, ltrim($alias, '@') . '/@dir')] = str_replace('@dir', $this->dir, 'public/@dir/' . ltrim($alias, '@'));
        }

        $tmp = str_replace(array_keys($map), $map, dirname($this->getServerName())) . '/' . $url;

        if (false !== strpos($tmp, '..')) {
            $new = array();
            $tmp2 = explode('/', $tmp);
            $tmp2 = array_reverse($tmp2);
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

        return $tmp;
    }

    protected function modifyUrl(string $uri): string
    {
//        return  $uri;
        $uri = trim($uri, '"\'');

        if (0 === strpos($uri, 'data:')) {
            $tmp = $uri;
        } elseif (filter_var($uri, FILTER_VALIDATE_URL) !== false) {
            $tmp = $uri;
        } else {
            $tmp = $this->getUrlFileServerName($uri);
            $tmp = str_replace(realpath($this->dirs['@public'] . '/'), '', $tmp);
            $tmp = $this->domain . '/' . trim($tmp, '/');
        }

        return $tmp;
    }

    protected function rawContentToTmpPath(string $content): string
    {
        return preg_replace_callback("/url\(([^\)]+)\)/", function ($m) {
            return 'url(\'' . $this->modifyUrl($m[1]) . '\')';
        }, $content);
    }

    protected function rawContentToHttp2(string $content): string
    {
//        return parent::rawContentToHttp($content);
        return preg_replace_callback("/url\(([^\)]+)\)/", function ($m) {
            return 'url(\'' . $this->modifyUrl($m[1]) . '\')';
        }, $content);
    }

    protected function rawContentToHttp(string $content): string
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