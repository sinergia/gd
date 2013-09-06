<?php

namespace Sinergia\Gd;

class Color
{
    public $r = 0;
    public $g = 0;
    public $b = 0;
    public $a = null;

    public function __construct($r = 0, $g = 0, $b = 0, $a = null)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
        $this->a = $a;
    }

    public function hasAlpha()
    {
        return ! is_null($this->a);
    }

    public function toInt()
    {
        return ($this->r << 16) + ($this->g << 8) + $this->b;
    }

    public function __toString()
    {
        return sprintf("%X%X%X%X", $this->r, $this->g, $this->b, $this->a);
    }

    public function toGd($res)
    {
        if ($this->hasAlpha()) {
            return imagecolorallocatealpha($res, $this->r, $this->g, $this->b, $this->alphaToGd());
        } else {
            return imagecolorallocate($res, $this->r, $this->g, $this->b);
        }
    }

    public function alphaToGd()
    {
        return $this->a > 1 ? $this->a : round(127 * (1-$this->a));
    }
}
