<?php

namespace SNOWGIRL_CORE\Script;

use SNOWGIRL_CORE\Script;

class Html extends Script
{
    protected $dir = 'html';

    private $regexp = [
        // Keep important white-space(s) after self-closing HTML tag(s)
        '#<(img|input)(>| .*?>)#s' => '<$1$2</$1>',
        // Remove a line break and two or more white-space(s) between tag(s)
        '#(<!--.*?-->)|(>)(?:\\n*|\\s{2,})(<)|^\\s*|\\s*$#s' => '$1$2$3',
        '#(<!--.*?-->)|(?<!\\>)\\s+(<\\/.*?>)|(<[^\\/]*?>)\\s+(?!\\<)#s' => '$1$2$3',
        '#(<!--.*?-->)|(<[^\\/]*?>)\\s+(<[^\\/]*?>)|(<\\/.*?>)\\s+(<\\/.*?>)#s' => '$1$2$3$4$5',
        '#(<!--.*?-->)|(<\\/.*?>)\\s+(\\s)(?!\\<)|(?<!\\>)\\s+(\\s)(<[^\\/]*?\\/?>)|(<[^\\/]*?\\/?>)\\s+(\\s)(?!\\<)#s' => '$1$2$3$4$5$6$7',
        '#(<!--.*?-->)|(<[^\\/]*?>)\\s+(<\\/.*?>)#s' => '$1$2$3',
        '#<(img|input)(>| .*?>)<\\/\\1>#s' => '<$1$2',
        '#(&nbsp;)&nbsp;(?![<\\s])#' => '$1 ',
        '#(?<=\\>)(&nbsp;)(?=\\<)#' => '$1',
        // Remove HTML comment(s) except IE comment(s)
        '#\\s*<!--(?!\\[if\\s).*?-->\\s*|(?<!\\>)\\n+(?=\\<[^!])#s' => '',
    ];

    public function minifyContent(string $content): string
    {
        if ('' === trim($content)) {
            return $content;
        }

        // Remove extra white-space(s) between HTML attribute(s)
        $content = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function ($tmp) {
            return '<' . $tmp[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $tmp[2]) . $tmp[3] . '>';
        }, str_replace("\r", "", $content));

        if (strpos($content, ' style=') !== false) {
            $content = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function ($tmp) {
                return '<' . $tmp[1] . ' style=' . $tmp[2] . Css::minifyContent($tmp[3]) . $tmp[2];
            }, $content);
        }

        return preg_replace(array_keys($this->regexp), $this->regexp, $content);
    }

    protected function getHtmlCachePath(): string
    {
        $dir = implode('/', [
            $this->dirs['@tmp'],
            $this->dir
        ]);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }
}