<?php

namespace Sinergia\Gd;

class Size
{
    public $width;
    public $height;

    public function __construct($width = 100, $height = null)
    {
        if ( ! is_numeric($width) ) {
            if ( is_resource($image = $width) ) {
                $width = imagesx($image);
                $height = imagesy($image);

            } elseif ( is_array($array = $width) ) {
                list($width, $height) = array_values($array);

            } elseif ( is_string($file = $width) && is_readable($file) ) {
                list($width, $height) = getimagesize($file);

            } elseif ( $width instanceof static ) {
                $size = $width;
                $width = $size->width;
                $height = $size->height;
            }
        }
        $this->width = $width;
        $this->height = $height;
    }

    public function getRatio()
    {
        return $this->width / $this->height;
    }

    public function fit(Size $target)
    {
        if ($this->getRatio() > $target->getRatio()) {
            $width = $target->width;
            $height = $width / $this->getRatio();
        } else {
            $height = $target->height;
            $width = $height * $this->getRatio();
        }

        return new static($width, $height);
    }

    public function zoom($times)
    {
        return new static($times * $this->width, $times * $this->height);
    }

    /**
     * Negative or float value
     * @param $dx
     * @param $dy
     * @return Point
     */
    public function pointAt($dx, $dy)
    {
        if ($dx < 0) {
            $dx = $this->width + $dx;
        } elseif ($dx > 0 && $dx <= 1) {
            $dx = round($this->width * $dx) - 1;
        }

        if ($dy < 0) {
            $dy = $this->height + $dy;
        } elseif ($dy > 0 && $dy <= 1) {
            $dy = round($this->height * $dy) - 1;
        }

        return new Point($dx, $dy);
    }

    public function __toString()
    {
        return sprintf("(%d/%d=%0.2f)", $this->width, $this->height, $this->getRatio());
    }
}
