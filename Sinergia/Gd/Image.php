<?php

namespace Sinergia\Gd;

use finfo, RuntimeException, DomainException;

class Image
{
    public $res;
    /**
     * @var Size
     */
    public $size;

    public function __construct($width, $height = null)
    {
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

        } elseif (is_array($width)) {
            $this->res = imagecreatetruecolor(reset($width), end($width));

        } elseif ( file_exists((string) $width) ) {
            $file = (string) $width;
            switch ( strtolower(pathinfo($file, PATHINFO_EXTENSION)) ) {
                case 'jpg':
                case 'jpeg':
                    $this->res = imagecreatefromjpeg($file);
                    break;
                case 'png':
                    $this->res = imagecreatefrompng($file);
                    break;
                default:
                    throw new RuntimeException("Arquivo '$file' não suportado!");
            }
        }

        $this->size = new Size($this->res);
        imagesavealpha($this->res, true);
        imagealphablending($this->res, false);
    }

    public static function send($dst)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($dst);

        $size = filesize($dst);

        header("Content-Type: $mime");
        header("Content-Length: $size");

        readfile($dst);
    }

    public function copyResampled(Image $target, Rect $src = null, Rect $dst = null)
    {
        $dst = $dst ?: new Rect($target->size);
        $src = $src ?: new Rect($this->size);
        imagecopyresampled($target->res, $this->res,
            $dst->origin->x, $dst->origin->y,
            $src->origin->x, $src->origin->y,
            $dst->size->width, $dst->size->height,
            $src->size->width, $src->size->height);
        return $target;
    }

    public function resize(Size $size)
    {
        $new = new static($size);
        return $this->copyResampled($new);
    }

    protected function output($ext, $file = null, $quality = 75)
    {
        if ($file) $file = (string)$file;

        switch($ext) {
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

    public function save($file, $quality = 75)
    {
        $this->output(pathinfo($file, PATHINFO_EXTENSION), $file, $quality);
        return $this;
    }

    public function flush($ext = 'jpg', $quality = 75)
    {
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
}
