<?php

namespace Sinergia\Gd;

class Color
{
    public $red = 0;
    public $green = 0;
    public $blue = 0;
    public $alpha = null;

    public function __construct($red = 0, $green = 0, $blue = 0, $alpha = null)
    {
        if ( is_array($red) ) {
            extract($red);
        }
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->alpha = $alpha;
    }

    public function hasAlpha()
    {
        return ! is_null($this->alpha);
    }

    public function toInt()
    {
        return ($this->red << 16) + ($this->green << 8) + $this->blue;
    }

    public function toGd($res)
    {
        if ($this->hasAlpha()) {
            return imagecolorallocatealpha($res, $this->red, $this->green, $this->blue, $this->alphaToGd());
        } else {
            return imagecolorallocate($res, $this->red, $this->green, $this->blue);
        }
    }

    /**
     * GD uses alpha between 0 and 127 equivalent to 1.0 to 0.
     * @return float
     */
    public function alphaToGd()
    {
        return $this->alpha > 1 ? $this->alpha : round(127 * (1-$this->alpha));
    }

    public function isGray()
    {
        return $this->red == $this->green && $this->green == $this->blue;
    }

    public function toArray()
    {
        return array(
            'red' => $this->red,
            'green' => $this->green,
            'blue' => $this->blue,
            'alpha' => $this->alpha
        );
    }

    public function toHex()
    {
        return sprintf("%02X%02X%02X", $this->red, $this->green, $this->blue);
    }

    public function __toString()
    {
        return sprintf("(%s %0.1f)", $this->toHex(), $this->alpha);
    }
}
