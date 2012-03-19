<?php

spl_autoload_register(function($className){
    if (strpos($className, 'Imagr') !== 0) {
        return;
    }

    $filename = sprintf('%s/src/lib/%s.php', realpath(__DIR__ . '/../'), str_replace('\\', '/', $className));
    if (file_exists($filename) === true) {
        require_once $filename;
    }
});