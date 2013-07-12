<?php

namespace Sinergia\Gd;

class Rect
{
    /**
     * @var Point
     */
    public $origin;

    /**
     * @var Size
     */
    public $size;

    public function __construct(Size $size)
    {
        $this->origin = new Point();
        $this->size = $size;
    }
}
