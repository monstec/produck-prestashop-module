<?php
/**
 * NOTICE OF LICENSE
 *
 * Licensed under the MonsTec Prestashop Module License v.1.0
 *
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Monstec UG (haftungsbeschränkt)
 * @copyright 2019 Monstec UG (haftungsbeschränkt)
 * @license   LICENSE.txt
 */

/**
 * Filebased caching based on removed CacheFs class
 *
 * @see https://raw.githubusercontent.com/PrestaShop/PrestaShop/1.6.1.x/classes/cache/CacheFs.php
 */
class ProduckCache extends Cache
{
    const KEYPREFIX = "ProDuck_";

    //! root of cache folder
    protected $cacheFolderRoot;

    //! depth of cache folder structure
    protected $depth;

    protected function __construct()
    {
        // this is the cache path
        if ((_PS_CACHE_DIR_ !== null) && !empty(_PS_CACHE_DIR_)) {
            $this->cacheFolderRoot = _PS_CACHE_DIR_ . 'cachefs/produck/';
        // old cache path - deprecated?
        } elseif ((_PS_CACHEFS_DIRECTORY_ !== null) && !empty(_PS_CACHEFS_DIRECTORY_)) {
            $this->cacheFolderRoot = _PS_CACHEFS_DIRECTORY_ . 'produck/';
        // fallback
        } else {
            $this->cacheFolderRoot = '/tmp/';
        }

        // define folder depth so not too many files are beside each other (filesystem performance)
        $this->depth = 2;

        // load keys if available
        $keys_filename = $this->getFilename(self::KEYS_NAME);
        if (@filemtime($keys_filename)) {
            $this->keys = unserialize(Tools::file_get_contents($keys_filename));
        }
    }

    /**
     * Singleton
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new ProduckCache();
        }

        return self::$instance;
    }

    /**
     * Cache a data.
     *
     * @param string $key cache key
     * @param mixed $value value to be cached
     * @param integer $ttl IMPORTANT! This parameter is only here to be compatible with the Prestashop
     *                     Cache. It must stay in order to be able to install the module in Prestashop
     *                     at all!
     *
     * @return bool
     */
    public function _set($key, $value, $ttl = 0)
    {
        // Do something with the $ttl so that the Prestashop validator does not complain about the unused
        // parameter. Of course this is kind of silly, because on the one hand the validator complains when
        // adding the parameter to the signature but on the other hand Prestashop won't let you install the
        // module if the parameter is not there, because it does not comply with CacheCore::_set...
        if (!$ttl) {
            $ttl = 0;
        }

        return (@file_put_contents($this->getFilename($key), serialize($value)));
    }

    /**
     * Retrieve a cached data by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function _get($key)
    {
        if (!$this->checkCacheValid($key)) {
            return false;
        }

        $filename = $this->getFilename($key);
        if (!@filemtime($filename)) {
            unset($this->keys[$key]);
            $this->_writeKeys();
            return false;
        }
        $file = Tools::file_get_contents($filename);

        // in case the cache gets corrupted an error notice would occur - ignore in order to refresh cache again
        return @unserialize($file);
    }

    /**
     * Check if a data is cached by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key)
    {
        if (!$this->checkCacheValid($key)) {
            return false;
        }

        return isset($this->keys[$key]) && @filemtime($this->getFilename($key));
    }

    /**
     * Delete a data from the cache by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function _delete($key)
    {
        $filename = $this->getFilename($key);
        if (!@filemtime($filename)) {
            return true;
        }

        return unlink($filename);
    }

    /**
     * Write keys index.
     */
    public function _writeKeys()
    {
        @file_put_contents($this->getFilename(self::KEYS_NAME), serialize($this->keys));
    }

    /**
     * Clean all cached data.
     *
     * @return bool
     */
    public function flush()
    {
        $this->delete('*');
        return true;
    }

    /**
     * Delete cache directory
     */
    public function deleteCacheDirectory()
    {
        return Tools::deleteDirectory($this->cacheFolderRoot, true);
    }

    /**
     * Check if TTL of cache item expired
     */
    protected function checkCacheValid($key)
    {
        if ($this->keys[$key] > 0 && $this->keys[$key] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Transform a key into its absolute path
     *
     * @param string $key
     * @return string
     */
    protected function getFilename($key)
    {
        $keyMd5 = md5($key);
        $path = $this->cacheFolderRoot;
        for ($i = 0; $i < $this->depth; $i++) {
            $path .= $keyMd5[$i] . '/';
        }

        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }

        return $path . $key;
    }
}
