<?php

namespace SNOWGIRL_CORE\View;

use SNOWGIRL_CORE\Exception;

class Node
{
    protected $tag;
    protected $attrs;
    protected $nodes = [];
    protected $empty;

    public function __construct($tag, array $attrs = [])
    {
        $this->tag = $tag;
        $this->empty = in_array($this->tag, ['img', 'br', 'hr', 'input', 'area', 'link', 'meta', 'param']);

        //@todo make difference between text and html
        foreach (['text', 'html'] as $k) {
            if (array_key_exists($k, $attrs)) {
                $this->append($attrs[$k]);
                unset($attrs[$k]);
            }
        }

        $this->attrs = $attrs;
    }

    /**
     * @param            $node
     * @param bool|false $ignoreEmpty
     *
     * @return $this
     */
    public function append($node, $ignoreEmpty = false)
    {
        if ($this->empty) {
            if ($ignoreEmpty) {
                return $this;
            }

            throw new Exception('empty tags can\'t contains any html');
        }

        $this->nodes[] = $node;
        return $this;
    }

    public function stringify()
    {
        try {
            $s = '<' . $this->tag;

            foreach ($this->attrs as $k => $v) {
                $s .= ' ' . (is_int($k) ? $v : ($k . '="' . $v . '"'));
            }

            if ($this->empty) {
                $s .= '/>' . chr(13);
            } else {
                $s .= '>';

                foreach ($this->nodes as $v) {
                    $s .= $v;
                }

                $s .= '</' . $this->tag . '>' . chr(13);
            }

            return $s;
        } catch (Exception $ex) {
            //@todo switch on deploy
//            return $ex->getMessage();
            return trans('Error');
        }
    }

    public function __toString()
    {
        return $this->stringify();
    }
}