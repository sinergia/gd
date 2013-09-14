<?php

namespace Sinergia\Gd;

use finfo, RuntimeException, DomainException;

class Image
{
    public $res;
    /**
     * @var Size
     */
    protected $size;

    /**
     * File extension
     * @var string
     */
    public $ext = 'jpg';

    /**
     * @var Color
     */
    public $bg;

    public function __construct($width, $height = null)
    {
        if (is_string($height)) {
            $this->ext = $height;
        }
        if ( is_resource($width) ) {
            $this->res = $width;

        } elseif ( is_numeric($width) ) {
            $this->res = imagecreatetruecolor($width, $height);

        } elseif ($width instanceof Rect) {
            $this->res = imagecreatetruecolor($width->size->width, $width->size->height);

        } elseif ($width instanceof Size) {
            $this->res = imagecreatetruecolor($width->width, $width->height);

        } elseif ($width instanceof Point) {
            $this->res = imagecreatetruecolor($width->x, $width->y);

        } elseif ( is_array($width) ) {
            $this->res = imagecreatetruecolor(reset($width), end($width));

        } elseif ( file_exists((string) $width) ) {
            $file = (string) $width;
            switch ( $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ) {
                case 'jpg':
                case 'jpeg':
                    $this->res = imagecreatefromjpeg($file);
                    $this->ext = 'jpg';
                    break;
                case 'png':
                    $this->res = imagecreatefrompng($file);
                    $this->ext = 'png';
                    break;
                default:
                    throw new RuntimeException("no $ext support from file '$file'");
            }
        }

        $this->size = new Size($this->res);
        $this->bg = new Color(0, 0, 0, 0);

        imagesavealpha($this->res, true);
        imagealphablending($this->res, false);
    }

    /**
     * Statically sends a image file to the browser
     */
    public static function send($dst)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($dst);

        $size = filesize($dst);

        header("Content-Type: $mime");
        header("Content-Length: $size");

        readfile($dst);
    }

    public function getRect()
    {
        return new Rect($this->size);
    }

    /**
     * Shortcut method to: $this->getRect()->getSize();
     *
     * @return Size
     */
    public function getSize()
    {
        return new Size($this->size);
    }

    /**
     * Rotate ClockWise, contrary to default imagerotate
     * @param $angle
     * @return Image returns a new image rotated
     */
    public function rotate($angle)
    {
        $image = imagerotate($this->res, -$angle, $this->bg->toInt());

        return new static($image, $this->ext);
    }

    /**
     * @param $mode IMG_FLIP_VERTICAL | IMG_FLIP_HORIZONTAL | IMG_FLIP_BOTH
     * @return Image returns a new image flipped
     */
    public function flip($mode)
    {
        $img = new static($this->res, $this->ext);
        // flip changes by reference and returns bool
        $ret = imageflip($img->res, $mode);

        return $ret ? $img : $this;
    }

    public function clear()
    {
        return $this->fill($this->bg);
    }

    /**
     * inplace image fill
     * @param Color $color
     * @param Rect $rect default to full fill
     */
    public function fill(Color $color, Rect $rect = null)
    {
        if (!$rect) $rect = $this->getRect();
        imagefilledrectangle($this->res,
                             $rect->origin->x, $rect->origin->y,
                             $rect->getRight() - 1, $rect->getBottom() - 1, // required to work as expected
                             $color->toGd($this->res));
        //imagefill($this->res, $rect->origin->x, $rect->origin->y, $color->toGd($this->res));

        return $this;
    }

    /**
     * inplace set a single pixel
     * @param Color $color
     * @param Point $point
     */
    public function setPixel(Color $color, Point $point)
    {
        if ( $color->hasAlpha() ) {
            $color = $color->toGd($this->res);
        } else {
            $color = $color->toInt();
        }
        imagesetpixel($this->res, $point->x, $point->y, $color);

        return $this;
    }

    public function copyResampled(Image $target, Rect $src = null, Rect $dst = null)
    {
        $dst = $dst ?: $target->getRect();
        $src = $src ?: $this->getRect();
        imagecopyresampled($target->res, $this->res,
            $dst->origin->x, $dst->origin->y,
            $src->origin->x, $src->origin->y,
            $dst->size->width, $dst->size->height,
            $src->size->width, $src->size->height);

        return $target;
    }

    public function copyResized(Image $target, Rect $src = null, Rect $dst = null)
    {
        $dst = $dst ?: $target->getRect();
        $src = $src ?: $this->getRect();
        imagecopyresized($target->res, $this->res,
            $dst->origin->x, $dst->origin->y,
            $src->origin->x, $src->origin->y,
            $dst->size->width, $dst->size->height,
            $src->size->width, $src->size->height);

        return $target;
    }

    public function resize(Size $size)
    {
        return $this->copyResized(new static($size, $this->ext));
    }

    public function resample(Size $size)
    {
        return $this->copyResampled(new static($size, $this->ext));
    }

    public function getColorAt(Point $point)
    {
        $colorIndex = imagecolorat($this->res, $point->x, $point->y);
        $rgba = imagecolorsforindex($this->res, $colorIndex);
        $rgba['alpha'] = 1 - $rgba['alpha']/127;

        return new Color($rgba);
    }

    public function getPixels()
    {
        $width = $this->getSize()->width;
        $height = $this->getSize()->height;

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                yield(array($x, $y));
            }
        }
    }

    /**
     * Saves image to a file
     * @param $file
     * @param int $quality
     * @return $this
     */
    public function save($file, $quality = 75)
    {
        $this->output(pathinfo((string) $file, PATHINFO_EXTENSION), (string) $file, $quality);

        return $this;
    }

    /**
     * Send image to the browser with appropriated headers
     *
     * @param string $ext
     * @param int $quality
     * @return $this
     */
    public function flush($ext = null, $quality = 75)
    {
        if (!$ext) $ext = $this->ext;
        $ext = strtolower($ext);
        if ($ext == 'jpg') $ext = 'jpeg';
        $mime = "image/$ext";

        ob_start();
        $this->output($ext, null, $quality);
        $buffer = ob_get_clean();
        $size = strlen($buffer);

        header("Content-Type: $mime");
        header("Content-Length: $size");

        echo $buffer;

        return $this;
    }

    /**
     * Output image to file or browser
     *
     * @param $ext
     * @param null $file
     * @param int $quality
     * @throws DomainException
     */
    protected function output($ext, $file = null, $quality = 75)
    {
        if ($file) $file = (string) $file;

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->res, $file, $quality);
                break;
            case 'png':
                imagepng($this->res, $file);
                break;
            case 'gif':
                imagegif($this->res);
                break;
            default:
                ob_end_clean();
                throw new DomainException("Image format '$ext' not supported!");
        }
    }
}
