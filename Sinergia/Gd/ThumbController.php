<?php

namespace Sinergia\Gd;

use SplFileInfo;

class ThumbController
{
    /**
     * @var SplFileInfo
     */
    protected $cache;

    /**
     * @var SplFileInfo
     */
    protected $source;

    protected function notFound()
    {
        die("imagem $this->source não existe!");
    }

    protected function hasCache()
    {
        return $this->cache->isFile() && $this->cache->isReadable();
    }

    protected function isCacheNewer()
    {
        return $this->cache->getMTime() > $this->source->getMTime();
    }

    protected function sendCache()
    {
        ob_end_clean();
        Image::send($this->cache);
    }

    /**
     * @param $transformation
     * @return Image
     */
    protected function generateThumb($transformation)
    {
        $cache_size = new Size(explode("x", $transformation));
        $source_image = new Image($this->source);
        return $source_image->resize( $source_image->size->fit($cache_size) );
    }

    protected function saveCache(Image $image)
    {
        $image->save($this->cache);
    }

    protected function createCacheDir()
    {
        if ( ! $this->cache->isDir() ) {
            return mkdir($this->cache->getPath(), 0777, true);
        }

        return true;
    }

    protected function format2pattern($format)
    {
        $pattern = preg_quote($format, '!');
        $pattern = preg_replace('!@(\w+)!', '(?<\1>.+)', $pattern);
        return "!^$pattern$!";
    }

    protected function removeNumericKeys($array)
    {
        return array_diff_key($array, range(0, count($array)));
    }

    public function getPath()
    {
        return @$_SERVER['PATH_INFO'] ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    protected function buildPaths($__VARS__)
    {
        extract($__VARS__);
        $this->source = new SplFileInfo("$publicDir/$dir/$name.$ext");
        $this->cache = new SplFileInfo("$cacheDir/$dir/$name.$size.$ext");
    }

    protected function parseParams($path)
    {
        $format = '@dir/@name.@size.@ext';
        $pattern = $this->format2pattern($format);
        preg_match($pattern, $path, $matches);
        return $this->removeNumericKeys($matches);
    }

    protected function isMissingSource()
    {
        return ! ($this->source->isFile() && $this->source->isReadable());
    }

    public function dispatch($path = null, $root, $cacheDir = 'tmp/thumbs', $publicDir = '')
    {
        // captura qualquer erro, para não enviar para o browser
        ob_start();
        $path = $path ?: $this->getPath();
        $params = $this->parseParams($path);
        $params['cacheDir'] = "$root/$cacheDir";
        $params['publicDir'] = "$root/$publicDir";
        $this->buildPaths($params);

        if ( $this->isMissingSource() ) {
            $this->notFound();
        }

        if ( $this->hasCache() ) {
            $this->sendCache();
        } else {
            $thumb = $this->generateThumb($params['size']);
            $this->saveCache($thumb);
            $this->sendCache();
        }
    }
}
