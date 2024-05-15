<?php

return [
    /**
     * For non-standard solutions, such as a module system,
     * you can provide paths to configuration files
     * that are not in the main configuration directory.
     * Paths should be provided starting from the main application directory
     * because they will be loaded from the base_path() function.
     * for example:
     *
     * 'module' => 'modules/module/config/module.php'
     * 'example' => 'dir/dir2//example.php'
     *
     * The keys of this array will correspond to the name of the configuration file
     * and the value is the path to this file.
     * Ultimately, it will be possible to use it in this way:
     *
     * module.php
     * [
     *      'path' => [
     *          'to' => [
     *              'config' => true
     *          ]
     *      ]
     * ]
     *
     * Usage:
     *
     * config('module.path.to.config')
     *
     * Remember that the paths to these configurations must be declared directly in this file,
     * dynamic setting will not work here, so you must, for example,
     * prepare the module installer to automatically substitute new paths to this file, or do it manually.
     */

    'custom_files_config' => [

    ],
];