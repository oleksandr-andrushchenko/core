<?php

namespace SNOWGIRL_CORE;

class DateTime extends \DateTime
{
    protected $defaultFormat = 'Y-m-d H:i:s';

    public function setDefaultFormat($v)
    {
        $this->defaultFormat = $v;
        return $this;
    }

    public function getDefaultFormat()
    {
        return $this->defaultFormat;
    }

    /**
     * @see http://php.net/manual/ru/function.strftime.php
     * %A %B %C %D %E %F %G %H %I %J %K %L %M %N %O %P %Q %R %S %T %U %V %W %X %Y %Z %a %b %c %d %e %f %g %h %i %j %k
     * %l %m %n %o %p %q %r %s %t %u %v %w %x %y %z %
     *
     * @param $format
     *
     * @return string
     */
    public function formatWithLocale($format)
    {
//        return strftime(preg_replace('/[a-zA-Z]/', '%$0', $format), $this->getTimestamp());
        return strftime($format, $this->getTimestamp());
    }

    public function getNiceWhenDate()
    {
        $current = $this->format('Y-m-d');

        if (date('Y-m-d') == $current) {
            return trans('date.today');
        } elseif (date('Y-m-d', strtotime('-1 days')) == $current) {
            return trans('date.yesterday');
        } elseif (date('Y-m-d', strtotime('-2 days')) == $current) {
            return trans('date.day-before-yesterday');
        } elseif (date('Y-m-d', strtotime('+1 days')) == $current) {
            return trans('date.tomorrow');
        } elseif (date('Y-m-d', strtotime('+2 days')) == $current) {
            return trans('date.day-after-tomorrow');
        } else {
            return $this->formatWithLocale('%F %d %Y');
        }
    }

    public function getNiceWhenDatetime()
    {
        return implode(', ', [
            $this->getNiceWhenDate(),
            $this->getNiceTime()
        ]);
    }

    public function getNiceTime()
    {
        return $this->format('%H:%S');
    }

    public function getNiceDate()
    {
        return $this->formatWithLocale('%A, %d %h %Y');
    }

    public function getNiceDatetime()
    {
        return $this->formatWithLocale('%d %B %Y, %H:%S');
    }

    public function stringify()
    {
        return $this->format($this->getDefaultFormat());
    }

    public function __toString()
    {
        return $this->stringify();
    }
}