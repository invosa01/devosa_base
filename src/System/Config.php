<?php
/**
 * Code written is strictly used within this program.
 * Any other use of this code is in violation of copy rights.
 *
 * @package   -
 * @author    Bambang Adrian Sitompul <bambang.adrian@gmail.com>
 * @copyright 2016 Developer
 * @license   - No License
 * @version   GIT: $Id$
 * @link      -
 */
defined('STANDARD_SESSION_LIBRARY_LOADED') === true or die('STANDARD SESSION LIBRARY IS NOT LOADED YET');
defined('STANDARD_PGSQL_DRIVER_LIBRARY') === true or die('STANDARD POSTGRESQL DRIVER LIBRARY IS NOT LOADED YET');
/**
 * A Flag sign if standard configuration library has been loaded or not.
 *
 * @constant boolean STANDARD_CONFIG_LIBRARY
 */
define('STANDARD_CONFIG_LIBRARY', true);
if (function_exists('initConfig') === false) {
    /**
     * Intialize the configuration library.
     *
     * @return void
     */
    function initConfig()
    {
    }
}
if (function_exists('loadDbConfig') === false) {
    /**
     * Load database configuration data.
     *
     * @return void
     */
    function loadDbConfig()
    {
    }
}
if (function_exists('getConfigItem') === false) {
    /**
     * Get configuration item data.
     *
     * @return mixed
     */
    function getConfigItem()
    {
    }
}
if (function_exists('setConfigItem') === false) {
    /**
     * Set configuration item data.
     *
     * @return void
     */
    function setConfigItem()
    {
    }
}
if (function_exists('parseConfigKey') === false) {
    /**
     * Parse configuration key.
     *
     * @return array
     */
    function parseConfigKey()
    {
    }
}