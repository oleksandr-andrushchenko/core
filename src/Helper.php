<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Helper\Data;

class Helper
{
    public static function modifyLink($link, $p = [])
    {
        $t = parse_url($link);

        if (array_key_exists('query', $t)) {
            parse_str($t['query'], $o);
            $o = array_merge($o, $p);
        } else {
            $o = $p;
        }

        $t = $t['scheme'] . '://' . $t['host'] . $t['path'];

        if ($o) {
            $t .= '?' . http_build_query($o);
        }

        return $t;
    }

    public static function getNiceSemanticText($text)
    {
        if (false !== strpos($text, '{')) {
            $text = preg_replace("/\{[^\}]+\}/", '', $text);
        }

        $text = trim(preg_replace("/\s+/", ' ', $text));
        $text = str_replace([' ,', ' .', ',.', ',,', ',,,', '..', '...'], [',', '.', ',', ',', ',', '.', '.'], $text);

        foreach (['.', ','] as $delimiter) {
            $tmp = array_map(function ($i) {
                return trim($i);
            }, explode($delimiter, $text));

            $text = implode($delimiter . ' ', array_filter($tmp, function ($i) {
                return '' != $i;
            }));
        }

        $text = Data::ucFirst($text);

        $text = implode('. ', array_map(function ($i) {
            return Data::ucFirst(trim($i));
        }, array_filter(explode('.', $text), function ($i) {
            return '' != trim($i);
        })));

        return $text;
    }

    public static function makeNiceNumber($number)
    {
        return number_format($number, 0, '.', ' ');
    }

    public static function roundUpToAny($n, $x = 5)
    {
        return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
    }

    public static function buildUrlByParts(array $parts, $url = null)
    {
        $tmp = $url ? parse_url($url) : [];

        foreach (['scheme', 'host', 'user', 'pass', 'port', 'path', 'query', 'fragment'] as $key) {
            if (isset($parts[$key])) {
                $tmp[$key] = $parts[$key];
            }
        }

        return implode('', [
            (isset($tmp['scheme'])) ? $tmp['scheme'] . '://' : '',
            (isset($tmp['user'])) ? $tmp['user'] . ((isset($tmp['pass'])) ? ':' . $tmp['pass'] : '') . '@' : '',
            (isset($tmp['host'])) ? $tmp['host'] : '',
            (isset($tmp['port'])) ? ':' . $tmp['port'] : '',
            (isset($tmp['path'])) ? $tmp['path'] : '',
            (isset($tmp['query'])) ? '?' . $tmp['query'] : '',
            (isset($tmp['fragment'])) ? '#' . $tmp['fragment'] : ''
        ]);
    }
}