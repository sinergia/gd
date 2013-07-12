<?php

namespace Sinergia\Gd;

class Size
{
    public $width;
    public $height;

    public function __construct($width, $height = null)
    {
        if ( ! is_numeric($width) ) {
            if ( is_resource($image = $width) ) {
                $width = imagesx($image);
                $height = imagesy($image);

            } elseif ( is_array($array = $width) ) {
                list($width, $height) = array_values($array);

            } elseif ( is_string($file = $width) && is_readable($file) ) {
                list($width, $height) = getimagesize($file);
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

    public function __toString()
    {
        return sprintf("(%d/%d=%0.2f)", $this->width, $this->height, $this->getRatio());
    }
}
