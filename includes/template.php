<?php
class MasterTemplate {
    var $vars = array(); /// Holds all the template variables

    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */
    function MasterTemplate ($file = null) {
        $this->file = $file;
    }

    /**
     * Set a template variable.
     */
    function set ($name, $value) {
        //$this->vars[$name] = is_object($value) ? $value->fetch() : $value;
        $this->vars[$name] = $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     */
    function fetch ($file = null) {
        if(!$file) $file = $this->file;

        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        include($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }
}

/**
 * An extension to Template that provides automatic caching of
 * template contents.
 */
class CachedTemplate extends MasterTemplate {
    var $cache_id;
    var $expire;
    var $cached;

    /**
     * Constructor.
     *
     * @param $cache_id string unique cache identifier
     * @param $expire int number of seconds the cache will live
     */
    function CachedTemplate($cache_id = null, $expire = 900) {
        $this->EIATemplate();
        $this->cache_id = $cache_id ? 'cache/' . md5($cache_id) : $cache_id;
        $this->expire   = $expire;
    }

    /**
     * Test to see whether the currently loaded cache_id has a valid
     * corrosponding cache file.
     */
    function is_cached() {
        if($this->cached) return true;

        // Passed a cache_id?
        if(!$this->cache_id) return false;

        // Cache file exists?
        if(!file_exists($this->cache_id)) return false;

        // Can get the time of the file?
        if(!($mtime = filemtime($this->cache_id))) return false;

        // Cache expired?
        if(($mtime + $this->expire) < time()) {
            @unlink($this->cache_id);
            return false;
        }
        else {
            /**
             * Cache the results of this is_cached() call.  Why?  So
             * we don't have to double the overhead for each template.
             * If we didn't cache, it would be hitting the file system
             * twice as much (file_exists() & filemtime() [twice each]).
             */
            $this->cached = true;
            return true;
        }
    }

    /**
     * This function returns a cached copy of a template (if it exists),
     * otherwise, it parses it as normal and caches the content.
     *
     * @param $file string the template file
     */
    function fetch_cache($file) {
        if($this->is_cached()) {
            $fp = @fopen($this->cache_id, 'r');
            $contents = fread($fp, filesize($this->cache_id));
            fclose($fp);
            return $contents;
        }
        else {
            $contents = $this->fetch($file);

            // Write the cache
            if($fp = @fopen($this->cache_id, 'w')) {
                fwrite($fp, $contents);
                fclose($fp);
            }
            else {
                die('Unable to write cache.');
            }

            return $contents;
        }
    }
}


/**
 * Example of file-based template usage.  This uses two templates.
 * Notice that the $bdy object is assigned directly to a $tpl var.
 * The template system has built in a method for automatically
 * calling the fetch() method of a passed in template.
 */
//$tpl = new EIATemplate();
//$tpl->set('title', 'My Test Page');
//$tpl->set('intro', 'The intro paragraph.');
//$tpl->set('list', array('cat', 'dog', 'mouse'));

//$bdy = new EIATemplate('body.tpl');
//$bdy->set('title', 'My Body');
//$bdy->set('footer', 'My Footer');

//$tpl->set('body', $bdy);

//echo $tpl->fetch('index.tpl');


/**
 * Example of cached template usage.  Doesn't provide any speed increase since
 * we're not getting information from multiple files or a database, but it
 * introduces how the is_cached() method works.
 */

/**
 * Define the template file we will be using for this page.
 */
//$file = 'index.tpl';

/**
 * Pass a unique string for the template we want to cache.  The template
 * file name + the server REQUEST_URI is a good choice because:
 *    1. If you pass just the file name, re-used templates will all
 *       get the same cache.  This is not the desired behavior.
 *    2. If you just pass the REQUEST_URI, and if you are using multiple
 *       templates per page, the templates, even though they are completely
 *       different, will share a cache file (the cache file names are based
 *       on the passed-in cache_id.
 */
//$tpl = new CachedTemplate($file . $_SERVER['REQUEST_URI']);

/**
 * Test to see if the template has been cached.  If it has, we don't
 * need to do any processing.  Thus, if you put a lot of db calls in
 * here (or file reads, or anything processor/disk/db intensive), you
 * will significantly cut the amount of time it takes for a page to
 * process.
 */
//if(!($tpl->is_cached())) {
//    $tpl->set('title', 'My Title');
//    $tpl->set('intro', 'The intro paragraph.');
//    $tpl->set('list', array('cat', 'dog', 'mouse'));
//}

/**
 * Fetch the cached template.  It doesn't matter if is_cached() succeeds
 * or fails - fetch_cache() will fetch a cache if it exists, but if not,
 * it will parse and return the template as usual (and make a cache for
 * next time).
 */
//echo $tpl->fetch_cache($file);
?>