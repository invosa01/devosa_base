<?php
/**
 * Contains code written by the Invosa Systems Company and is strictly used within this program.
 * Any other use of this code is in violation of copy rights.
 *
 * @package   -
 * @author    Bambang Adrian Sitompul <bambang@invosa.com>
 * @copyright 2016 Invosa Systems Indonesia
 * @license   http://www.invosa.com/license No License
 * @version   GIT: $Id$
 * @link      http://www.invosa.com
 */
# This module must be loaded first because it's required for other standard modules.
/**
 * Standard function loaded constant.
 *
 * @constant boolean STANDARD_FUNCTION_LOADED
 */
define('STANDARD_FUNCTION_LOADED', true);
/**
 * Default new line character.
 *
 * @constant string NL
 */
define('NL', "\n");
/**
 * Default tab character.
 *
 * @constant string TAB
 */
define('TAB', "\t");
/**
 * Default directory separator.
 *
 * @constant string DS
 */
define('DS', DIRECTORY_SEPARATOR);
/**
 * Default namespace separator.
 *
 * @constant string NS
 */
define('NS', '\\');
if (function_exists('debug') === false) {
    /**
     * Debugging variable.
     *
     * @param mixed   $variable     Variable that want to be inspected.
     * @param boolean $exit         Exit control statement flag option parameter.
     * @param string  $preface      Preface string that will be prepend into debug string.
     * @param string  $functionName Function name parameter.
     *
     * @return void
     */
    function debug($variable, $exit = false, $preface = '', $functionName = '')
    {
        echo '<pre>';
        echo $preface;
        if (trim($functionName) !== '' and $functionName !== null) {
            $backTrace = debug_backtrace();
            echo $backTrace[array_search($functionName, array_column($backTrace, 'function'), true)]['file'];
        }
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($variable);
        echo '</pre>';
        if ($exit === true) {
            exit;
        }
    }
}
if (function_exists('applyPathFix') === false) {
    /**
     * Applying path fix for given path string.
     *
     * @param string $path Path string parameter.
     * @param string $ds   Directory separator parameter.
     *
     * @return string
     */
    function applyPathFix($path, $ds = DS)
    {
        return trim((string)preg_replace('/(\/|\\\)+/', $ds, $path), $ds);
    }
}
if (function_exists('getAbsolutePath') === false) {
    /**
     * Determine the absolute project path.
     *
     * @param string $path Path string parameter.
     *
     * @return string
     */
    function getAbsolutePath($path = '')
    {
        return realpath(dirname(dirname(__DIR__))) . (string)getValue($path, '', DS . applyPathFix($path));
    }
}
if (function_exists('getBasePath') === false) {
    /**
     * Get absolute real base path system.
     *
     * @param string  $path         Path string parameter.
     * @param boolean $validatePath Path validation flag option parameter.
     * @param string  $defaultPath  Default path if the given path not exists.
     *
     * @throws \RuntimeException If invalid path given.
     * @return string
     */
    function getBasePath($path = '', $validatePath = true, $defaultPath = '')
    {
        $basePath = getAbsolutePath($path);
        if (realpath($basePath) === false) {
            if ($validatePath === true) {
                throw new \RuntimeException('Invalid path given: ' . $path);
            }
            $basePath = getMappedValue(trim($defaultPath) !== '', $defaultPath, $basePath);
        }
        return $basePath;
    }
}
if (function_exists('doInclude') === false) {
    /**
     * Function to include once the needed other php file.
     *
     * @param string   $path             File path parameter.
     * @param boolean  $strict           Include the php file using strict mode (require).
     * @param boolean  $includeOnce      Include once option, enable if you want to check if already included before.
     * @param boolean  $useBuffer        Include the file using output buffer option flag parameter.
     * @param callable $onBufferCallback Callback function when doing a buffered include.
     *
     * @throws \RuntimeException If included file is not exists.
     * @return void
     */
    function doInclude(
        $path,
        $strict = true,
        $includeOnce = true,
        $useBuffer = false,
        callable $onBufferCallback = null
    ) {
        if ($useBuffer === true) {
            ob_start();
        }
        try {
            $includedPath = getBasePath($path);
            if ($strict === true) {
                if ($includeOnce === true) {
                    /** @noinspection PhpIncludeInspection */
                    require_once $includedPath;
                } else {
                    /** @noinspection PhpIncludeInspection */
                    require $includedPath;
                }
            }
            if ($includeOnce === true) {
                /** @noinspection PhpIncludeInspection */
                include_once $includedPath;
            } else {
                /** @noinspection PhpIncludeInspection */
                include $includedPath;
            }
        } catch (\Exception $ex) {
            if ($useBuffer === true) {
                ob_clean();
            }
            throw new \RuntimeException($ex->getMessage());
        }
        if ($useBuffer === true and $onBufferCallback !== null) {
            $onBufferCallback();
            ob_flush();
        }
    }
}
if (function_exists('doIncludes') === false) {
    /**
     * Function to include once the needed other php file.
     *
     * @param array    $paths            File path collection data parameter.
     * @param boolean  $strict           Include the php file using strict mode (require).
     * @param boolean  $includeOnce      Include once option, enable if you want to check if already included before.
     * @param boolean  $useBuffer        Include the file using output buffer option flag parameter.
     * @param callable $onBufferCallback Callback function when doing a buffered include.
     *
     * @throws \RuntimeException If included file(s) is not exists.
     * @return void
     */
    function doIncludes(
        array $paths,
        $strict = true,
        $includeOnce = true,
        $useBuffer = false,
        callable $onBufferCallback = null
    ) {
        foreach ($paths as $path) {
            doInclude($path, $strict, $includeOnce, $useBuffer, $onBufferCallback);
        }
    }
}
if (function_exists('getValue') === false) {
    /**
     * Get simple value of variable.
     *
     * @param mixed $variable          Variable parameter.
     * @param mixed $default           Default value parameter.
     * @param mixed $mappedValue       Mapped variable value parameter.
     * @param array $defaultConditions Default empty condition rule data collection parameter.
     *
     * @return mixed
     */
    function getValue($variable, $default = null, $mappedValue = null, array $defaultConditions = [null, '', [], false])
    {
        $checkedVariable = $variable;
        if (is_string($variable) === true) {
            $checkedVariable = trim($variable);
        }
        if (in_array($checkedVariable, $defaultConditions, true) === true) {
            $variable = $default;
        } else {
            if ($mappedValue !== null) {
                $variable = $mappedValue;
            }
        }
        return $variable;
    }
}
if (function_exists('getValueIfExistsOnArray') === false) {
    /**
     * Search array by key constant.
     *
     * @constant integer SEARCH_ARR_BY_KEY
     */
    define('SEARCH_ARR_KEY', 1);
    /**
     * Search array by value constant.
     *
     * @constant integer SEARCH_ARR_VAL
     */
    define('SEARCH_ARR_VAL', 2);
    /**
     * Search array by both key and value constant.
     *
     * @constant integer SEARCH_ARR_BOTH
     */
    define('SEARCH_ARR_BOTH', 3);
    /**
     * Return a custom value if exists on an one dimension array, searching method only use case-sensitive mode.
     *
     * @param string  $term         Search keyword term parameter.
     * @param array   $source       Array data resource that want to be searched.
     * @param mixed   $checkedValue Value comparison will be running if this value is not null.
     * @param integer $mode         Search mode flag option parameter.
     *
     * @return mixed
     */
    function getValueIfExistsOnArray($term, array $source = [], $checkedValue = null, $mode = 1)
    {
        switch ($mode) {
            case SEARCH_ARR_KEY:
                $isFound = (array_key_exists($term, $source) === true);
                if ($isFound === true) {
                    $isFound = $source[$term];
                    if ($checkedValue !== null) {
                        $isFound = ($source[$term] === $checkedValue);
                        if (is_array($checkedValue) === true) {
                            $isFound = (in_array($term, $checkedValue, true) === true);
                        }
                        return getMappedValue($isFound === true, $source[$term], false);
                    }
                }
                return $isFound;
            case SEARCH_ARR_VAL:
                $isFound = (in_array($term, $source, true) === true);
                if ($isFound === true and $checkedValue !== null) {
                    $keyFound = array_search($term, $source, true);
                    if ($keyFound !== false) {
                        $isFound = ($keyFound === $checkedValue);
                        if (is_array($checkedValue) === true) {
                            $isFound = (in_array($keyFound, $checkedValue, true) === true);
                        }
                    }
                }
                return $isFound;
            case SEARCH_ARR_BOTH:
                $keyExists = (array_key_exists($term, $source) === true);
                $valueExists = (in_array($term, $source, true) === true);
                $isFound = ($keyExists or $valueExists);
                if ($isFound === true and $checkedValue !== null) {
                    if ($keyExists) {
                        $isFound = getValueIfExistsOnArray($term, $source, $checkedValue, SEARCH_ARR_KEY);
                    }
                    if ($valueExists === true and $isFound === false) {
                        $isFound = getValueIfExistsOnArray($term, $source, $checkedValue, SEARCH_ARR_VAL);
                    }
                }
                return $isFound;
            default:
                return false;
        }
    }
}
if (function_exists('getArrayItemValue') === false) {
    /** @noinspection PhpTooManyParametersInspection */
    /**
     * Get array item value property.
     *
     * @param array               $arrayData         Array data parameter.
     * @param array|number|string $fieldName         Field name parameter.
     * @param mixed               $defaultValue      Default value parameter.
     * @param mixed               $mappedValue       Mapped value if searched index is exists.
     * @param boolean             $strict            Strict checking to fetch the array item flag option parameter.
     * @param array               $defaultConditions Default empty condition rule data collection parameter.
     *
     * @throws \RuntimeException If given field key string is not exists.
     * @return mixed
     */
    function getArrayItemValue(
        array $arrayData,
        $fieldName,
        $defaultValue = null,
        $mappedValue = null,
        $strict = false,
        array $defaultConditions = [null, '', [], false]
    ) {
        $result = null;
        $fieldKeyVariables = (array)$fieldName;
        if (is_string($fieldName) === true) {
            parse_str($fieldName, $fieldKeyVariables);
        }
        if (count($fieldKeyVariables) === 1) {
            foreach ($fieldKeyVariables as $index => $key) {
                if (array_key_exists($index, $arrayData) === false) {
                    if ($strict === true) {
                        throw new \RuntimeException('Invalid field array key/index given: ' . $fieldName);
                    }
                    break;
                }
                if (is_array($key) === true) {
                    return getArrayItemValue($arrayData[$index], $key, $defaultValue, $mappedValue, $strict);
                }
                $result = $arrayData[$index];
            }
        }
        return getValue($result, $defaultValue, $mappedValue, $defaultConditions);
    }
}
if (function_exists('isGivenArrayKeyCanParsed') === false) {
    /**
     * Check if given array key can be parsed or not.
     *
     * @param string $arrayKeyString Array key string parameter.
     *
     * @return boolean
     */
    function isGivenArrayKeyCanParsed($arrayKeyString)
    {
        $matchCounter = preg_match('#[\w\s]+(?=\[.+\])#', $arrayKeyString);
        return $matchCounter === 1;
    }
}
if (function_exists('getMappedValue') === false) {
    /**
     * Get result of mapped value.
     *
     * @param boolean $condition    The condition parameter that will be checked.
     * @param mixed   $mappedValue  Mapped value parameter.
     * @param mixed   $defaultValue Default value parameter.
     *
     * @return mixed
     */
    function getMappedValue($condition, $mappedValue, $defaultValue = null)
    {
        return $condition === true ? $mappedValue : $defaultValue;
    }
}
if (function_exists('implodeArray') === false) {
    /**
     * Implode an array content into a string (Just work for 1 dimension only).
     *
     * @param array  $arrayData    Array data parameter that will be imploded.
     * @param string $concatString Concat string parameter.
     * @param string $prefix       Prefix string parameter.
     * @param string $suffix       Suffix string parameter.
     *
     * @return string
     */
    function implodeArray(array $arrayData, $concatString = ',', $prefix = '', $suffix = '')
    {
        $result = '';
        if (count($arrayData) > 0) {
            if ($prefix !== '' or $suffix !== '') {
                $arrayData = array_map(
                    function ($item) use ($prefix, $suffix, $concatString) {
                        if (is_array($item) === true) {
                            return implodeArray($item, $concatString, $prefix, $suffix);
                        }
                        if (getValue($prefix) !== null) {
                            $item = $prefix . $item;
                        }
                        if (getValue($suffix) !== null) {
                            $item .= $suffix;
                        }
                        return $item;
                    },
                    $arrayData
                );
            }
            $result = implode(
                $concatString,
                $arrayData
            );
        }
        return $result;
    }
}
if (function_exists('getFilteredArrayWithKeys') === false) {
    /**
     * Get filtered array using given keys data.
     *
     * @param array   $sourceArray          Array source data parameter.
     * @param array   $keysFilter           Filter keys array data parameter.
     * @param boolean $removeIfKeyNotExists Remove key if not exists flag option parameter.
     * @param mixed   $defaultValue         Default value parameter.
     *
     * @return array
     */
    function getFilteredArrayWithKeys(
        array $sourceArray,
        array $keysFilter = [],
        $removeIfKeyNotExists = false,
        $defaultValue = null
    ) {
        $filteredData = [];
        if (count($keysFilter) === 0) {
            $filteredData = $sourceArray;
        } else {
            foreach ($keysFilter as $keyName) {
                $value = getArrayItemValue($sourceArray, $keyName, $defaultValue);
                if ($removeIfKeyNotExists === true and $value === $defaultValue) {
                    continue;
                }
                $filteredData[$keyName] = $value;
            }
        }
        return $filteredData;
    }
}
if (function_exists('doIfElse') === false) {
    /**
     * Simple do if else on one line.
     *
     * @param boolean  $condition   Condition result parameter.
     * @param callable $ifTrue      Callable function or method if condition return true.
     * @param array    $argsIfTrue  Arguments data that will be passed into ifTrue callable.
     * @param callable $ifFalse     Callable function or method if condition return false.
     * @param array    $argsIfFalse Arguments data that will be passed into ifFalse callable.
     *
     * @return void
     */
    function doIfElse(
        $condition,
        callable $ifTrue,
        array $argsIfTrue = [],
        callable $ifFalse = null,
        array $argsIfFalse = []
    ) {
        if ($condition === true and is_callable($ifTrue) === true) {
            call_user_func_array($ifTrue, $argsIfTrue);
        } elseif ($condition === false and is_callable($ifFalse) === true) {
            call_user_func_array($ifFalse, $argsIfFalse);
        }
    }
}
if (function_exists('getGlobalVar') === false) {
    /**
     * Get global variable.
     *
     * @param string $variableName Variable name parameter.
     * @param mixed  $defaultValue Default value parameter.
     * @param mixed  $mappedValue  Mapped variable value parameter.
     *
     * @return mixed
     */
    function getGlobalVar($variableName, $defaultValue = null, $mappedValue = null)
    {
        global ${$variableName};
        return getValue(${$variableName}, $defaultValue, $mappedValue);
    }
}
if (function_exists('getGlobalVars') === false) {
    /**
     * Get all global variables.
     *
     * @return array
     */
    function getGlobalVars()
    {
        return $GLOBALS;
    }
}
if (function_exists('setGlobalVar') === false) {
    /**
     * Set global variable item property.
     *
     * @param string $variableName Variable name parameter.
     * @param mixed  $value        Value that will be assigned to global variable.
     *
     * @return void
     */
    function setGlobalVar($variableName, $value)
    {
        $GLOBALS[$variableName] = $value;
    }
}
if (function_exists('setArrayItemValueByRefString') === false) {
    /**
     * Set array item value by reference using key string.
     *
     * @param array|number|string $keys               Key data parameter.
     * @param mixed               $value              Assigned value parameter.
     * @param array               $refArr             Referenced array parameter.
     * @param boolean             $preserveNumericKey Preserve the numeric key flag option parameter.
     *
     * @return null Returns reference variable that handle of specific key data of given reference array.
     */
    function &setArrayItemValueByRefString($keys, $value, array &$refArr, $preserveNumericKey = false)
    {
        if (is_string($keys) === true or is_numeric($keys) === true) {
            parse_str((string)$keys, $keys);
        }
        $refArrHandler = null;
        if (count($keys) === 1) {
            foreach ((array)$keys as $index => $key) {
                if (is_array($key) === true) {
                    if (isset($refArr[$index]) === false or is_array($refArr[$index]) === false) {
                        $refArr[$index] = [];
                    }
                    $refArrHandler = &setArrayItemValueByRefString($key, $value, $refArr[$index], $preserveNumericKey);
                } else {
                    if (count($refArr) > 0 and is_numeric($index) === true and $preserveNumericKey === false) {
                        $refArr[] = $value;
                    } else {
                        $refArr[$index] = $value;
                    }
                    return $refArr[$index];
                }
            }
        }
        return $refArrHandler;
    }
}
if (function_exists('getArrayItemValueByRefString') === false) {
    /**
     * Get array item value by reference using key string.
     *
     * @param array|number|string $keys   Key data parameter.
     * @param array               $refArr Referenced array parameter.
     * @param boolean             $strict Throw an error if trying to access array element on index out of bound state.
     *
     * @throws \OutOfBoundsException If index out of bound array access detected.
     * @return null Returns reference variable that handle of specific key data of given reference array.
     */
    function &getArrayItemValueByRefString($keys, &$refArr, $strict = false)
    {
        if (is_string($keys) === true or is_numeric($keys) === true) {
            parse_str((string)$keys, $keys);
        }
        $refArrHandler = null;
        if (count($keys) === 1) {
            foreach ((array)$keys as $index => $key) {
                if (isset($refArr[$index]) === false) {
                    if ($strict === true) {
                        throw new \OutOfBoundsException('Out of bound key on array access');
                    }
                    return null;
                }
                if (is_array($key) === true) {
                    $refArrHandler = &getArrayItemValueByRefString($key, $refArr[$index], $strict);
                } else {
                    return $refArr[$index];
                }
            }
        }
        return $refArrHandler;
    }
}
if (function_exists('getMergedArrayRecursively') === false) {
    /**
     * Get merged array from 2 given passed array recursively, all the array item that has same key with
     * array collection 2 will be replaced.
     *
     * @param array   $arrCollection1     First array data collection parameter.
     * @param array   $arrCollection2     Second array data collection parameter.
     * @param boolean $replaceIfNotEmpty  Replace if the right array value is not empty.
     * @param boolean $preserveNumericKey Preserve numeric key/index flag option parameter.
     * @param array   $emptyConditions    Empty condition data collection parameter.
     *
     * @throws \RuntimeException If one of given arguments is not an array.
     * @return array
     */
    function getMergedArrayRecursively(
        $arrCollection1,
        $arrCollection2,
        $replaceIfNotEmpty = false,
        $preserveNumericKey = false,
        array $emptyConditions = [null, '']
    ) {
        if (is_array($arrCollection1) === false or is_array($arrCollection2) === false) {
            throw new \RuntimeException('Both arguments must be an array');
        }
        $result = $arrCollection1;
        foreach ($arrCollection2 as $key => $val) {
            $isArrayKeyExists = (array_key_exists($key, $arrCollection1) === true);
            if ($isArrayKeyExists === true and
                is_array($val) === true and
                gettype($arrCollection1[$key]) === gettype($val)
            ) {
                $result[$key] = getMergedArrayRecursively(
                    $arrCollection1[$key],
                    $val,
                    $replaceIfNotEmpty,
                    $preserveNumericKey,
                    $emptyConditions
                );
                continue;
            }
            if (is_int($key) === true and ($isArrayKeyExists === true or $preserveNumericKey === false)) {
                $result[] = $val;
                continue;
            }
            if ($isArrayKeyExists === true and
                $replaceIfNotEmpty === true and
                in_array($val, $emptyConditions, true) === true
            ) {
                continue;
            }
            $result[$key] = $val;
        }
        return $result;
    }
}
if (function_exists('initStandardFramework') === false) {
    /**
     * Standard framework initialization.
     *
     * @param array $additionalSystemComponents Additional system components library package parameter.
     *
     * @throws \RuntimeException If any error raised when init the framework.
     * @return void
     */
    function initStandardFramework(array $additionalSystemComponents = [])
    {
        require_once __DIR__ . '/../Constants.php';
        require_once __DIR__ . '/System.php';
        require_once __DIR__ . '/Paths.php';
        require_once __DIR__ . '/Loader.php';
        require_once __DIR__ . '/Exceptions.php';
        require_once __DIR__ . '/Sessions.php';
        # Register all the required framework libraries that must be loaded.
        $coreSystemLibraries = 'Config,Environment,Registry,Url,Routes,Requests,Template,Validation,' .
            'Application,FileSystem';
        $arrComponentSystemLibraries = [
            'Database/DbHandler',
            'Helper/String',
            'Helper/DateTime',
            'Helper/Array',
            'Gui/Html',
            'Runtime/Logger',
            'Mvc/Components',
            'Common/Translation',
            'Helper/General'
        ];
        $arrComponentSystemLibraries = array_filter(
            array_merge($arrComponentSystemLibraries, $additionalSystemComponents)
        );
        try {
            # Include vendor autoloader that generated by composer.
            doInclude('vendor/autoload.php');
            # Load all core system libraries.
            loadSysCoreModules(explode(',', $coreSystemLibraries));
            # Load all the default and additional system component libraries.
            loadSysComponentModules($arrComponentSystemLibraries);
        } catch (\Exception $ex) {
            throw new \RuntimeException($ex->getMessage());
        }
        $refreshMode = (boolean)getSysConfigItem('config.RefreshOnInit');
        # Please always start and initialize configuration sessions.
        initConfigSession($refreshMode);
        # Load the framework configurations.
        loadSysConfigFile(MAIN_SYS_CONFIG_FILE_NAME);
        # Booting system registry.
        bootSysRegistry($refreshMode);
        # Run the pre-defined system service.
        runRequestServices();
    }
}
