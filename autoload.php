<?php
spl_autoload_register(function($class) {
    require __DIR__ . "/src/" . str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";
});
