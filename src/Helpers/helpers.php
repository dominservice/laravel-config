<?php

use Illuminate\Support\Env;

if (! function_exists('optimize_config')) {
    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    function optimize_config($key, $default = null): mixed
    {
        $mainConfig = file_exists(base_path('optimize_config.php')) ? include (base_path('optimize_config.php')) : [];

        if (isset($mainConfig[$key])) {
            if (preg_match('#\${.*}#', $mainConfig[$key], $math)) {
                return preg_replace('#\${.*}#', optimize_config(preg_replace('#(\${|})#', '', $math[0]), $default), $mainConfig[$key]);
            }

            return $mainConfig[$key];
        } else {
            return Env::get($key, $default);
        }
    }
}