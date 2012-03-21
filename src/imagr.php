<?php

// register the autoloader
spl_autoload_register(function($className){
    if (strpos($className, 'Imagr') !== 0) {
        return;
    }

    $filename = sprintf('%s/lib/%s.php', __DIR__, str_replace('\\', DIRECTORY_SEPARATOR, $className));
    if (file_exists($filename) === true) {
        require_once $filename;
    }
});

$imagr = new Imagr\Imagr();

/**
 * Cache configuration. Make sure the web server and user have permission to write to these locations e.g.
 *
 * sudo chmod +a "_www allow delete,write,append,file_inherit,directory_inherit" cache
 * sudo chmod +a "`whoami` allow delete,write,append,file_inherit,directory_inherit" cache
 */
$remoteCache = new \Imagr\Cache($imagr->getConfig('cache_dir') . '/remote');
$imageCache = new \Imagr\Cache($imagr->getConfig('cache_dir') . '/images');
$imagr->setImageCache($imageCache);
$imagr->setRemoteCache($remoteCache);

// the request
$imagr->setRequest(new Imagr\Request(array($_GET)));
$imagr->process();