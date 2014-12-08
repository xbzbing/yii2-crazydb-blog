<?php

/**
 * 临时的autoloader，将来会提交到packagist
 * Class ExtLoader
 */
class ExtLoader
{
    public static function loadClass($class)
    {
        $className = array_pop(explode('\\',$class));
        $file = __DIR__.'/'.$className . '.php';
        if (is_file($file)) {
            require_once($file);
        }
    }
}
spl_autoload_register(['ExtLoader', 'loadClass']);

