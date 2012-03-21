<?php

// register the autoloader
spl_autoload_register(function($className){
    if (strpos($className, 'Imagr') !== 0) {
        return;
    }

    $filename = sprintf('%s/lib/%s.php', __DIR__, str_replace('\\', '/', $className));
    if (file_exists($filename) === true) {
        require_once $filename;
    }
});

$imagr = new Imagr\Imagr();

// cache
$remoteCacheDir = $imagr-
$imagr->setRequest(new Imagr\Request(array($_GET)));
$imagr->setCache(new Imagr\Cache($imagr->getConfig('cache_dir')));
$imagr->process();