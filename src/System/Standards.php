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
 * Standard function loaded constanta.
 *
 * @constant boolean STANDARD_FUNCTION_LOADED
 */
define('STANDARD_FUNCTION_LOADED', true);
require_once __DIR__ . '/../Config.php';
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
        print_r($variable);
        echo '</pre>';
        if ($exit === true) {
            exit;
        }
    }
}
if (function_exists('getBasePath') === false) {
    /**
     * Get base path system.
     *
     * @param string $path Path string parameter.
     *
     * @throws \LogicException If invalid path given.
     * @return string
     */
    function getBasePath($path = '')
    {
        $basePath = realpath(dirname(dirname(__DIR__)) . DS . $path);
        if ($basePath === false) {
            throw new \LogicException('Invalid path given: ' . $path);
        }
        return $basePath;
    }
}
if (function_exists('doInclude') === false) {
    /**
     * Function to include once the needed other php file.
     *
     * @param string  $path        File path parameter.
     * @param boolean $strict      Include the php file using strict mode (require).
     * @param boolean $includeOnce Include once option, enable if you want to check if already included before.
     *
     * @throws \LogicException If included file is not exists.
     * @return void
     */
    function doInclude($path, $strict = true, $includeOnce = true)
    {
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
            throw new \LogicException($ex->getMessage());
        }
    }
}
if (function_exists('doIncludes') === false) {
    /**
     * Function to include once the needed other php file.
     *
     * @param array   $paths       File path collection data parameter.
     * @param boolean $strict      Include the php file using strict mode (require).
     * @param boolean $includeOnce Include once option, enable if you want to check if already included before.
     *
     * @throws \LogicException If included file(s) is not exists.
     * @return void
     */
    function doIncludes(array $paths, $strict = true, $includeOnce = true)
    {
        foreach ($paths as $path) {
            doInclude($path, $strict, $includeOnce);
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
     * @param array $defaultConditions Default condition rule parameter.
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
if (function_exists('getArrayItemValue') === false) {
    /**
     * Get array item value property.
     *
     * @param array          $arrayData    Array data parameter.
     * @param string|integer $fieldName    Field name parameter.
     * @param string         $defaultValue Default value parameter.
     * @param string         $mappedValue  Mapped value if searched index is exists.
     *
     * @return mixed
     */
    function getArrayItemValue(array $arrayData, $fieldName, $defaultValue = '', $mappedValue = '')
    {
        $result = $defaultValue;
        if (array_key_exists($fieldName, $arrayData) === true) {
            $result = $arrayData[$fieldName];
            if ($mappedValue !== '') {
                $result = $mappedValue;
            }
        }
        return $result;
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
        $result = $defaultValue;
        if ($condition === true) {
            $result = $mappedValue;
        }
        return $result;
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
            $result = implode(
                $concatString,
                array_map(
                    function ($item) use ($prefix, $suffix) {
                        if (getValue($prefix) !== null) {
                            $item = $prefix . $item;
                        }
                        if (getValue($suffix) !== null) {
                            $item .= $suffix;
                        }
                        return $item;
                    },
                    $arrayData
                )
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
        array $keysFilter,
        $removeIfKeyNotExists = false,
        $defaultValue = null
    ) {
        $filteredData = [];
        foreach ($keysFilter as $keyName) {
            $value = getArrayItemValue($sourceArray, $keyName, $defaultValue);
            if ($removeIfKeyNotExists === true and $value === $defaultValue) {
                continue;
            }
            $filteredData[$keyName] = $value;
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
if (function_exists('getServerValue') === false) {
    /**
     * Get $_SERVER item value.
     *
     * @param string $fieldName    SERVER field name parameter.
     * @param string $defaultValue Default value parameter.
     * @param string $mappedValue  Mapped value if the field name exists.
     *
     * @return string
     */
    function getServerValue($fieldName, $defaultValue = '', $mappedValue = '')
    {
        return getArrayItemValue($_SERVER, $fieldName, $defaultValue, $mappedValue);
    }
}
if (function_exists('getGlobalVar') === false) {
    /**
     * Get global variable.
     *
     * @param string $variableName Variable name parameter.
     * @param mixed  $defaultValue Default value parameter.
     *
     * @return mixed
     */
    function getGlobalVar($variableName, $defaultValue = null)
    {
        global ${$variableName};
        $result = $defaultValue;
        if (${$variableName} !== null) {
            $result = ${$variableName};
        }
        return $result;
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
if (function_exists('getMessageBox') === false) {
    /**
     * Display for error message box.
     *
     * @param string $message     Message string parameter.
     * @param string $messageType Message type parameter.
     * @param string $codeNo      Message code number parameter.
     * @param string $boxStyle    Message box style parameter.
     *
     * @return string
     */
    function getMessageBox($message, $messageType = 'info', $codeNo = '', $boxStyle = '')
    {
        $defaultStyle = [
            'error'   => 'background:yellow; border:1px solid maroon;',
            'info'    => 'background:#4F91C8; color:white; border:1px solid #2d70a6;',
            'success' => 'background:#84CE84; color:white; border:1px solid #62ac62;'
        ];
        if (trim($message) === '') {
            return '';
        }
        if (trim($boxStyle) === '') {
            $boxStyle = $defaultStyle[$messageType] . 'padding:3px;margin:10px 0;';
        }
        if ($codeNo !== '') {
            $message .= ' (CodeNo: ' . $codeNo . ')';
        }
        return '<div class="message-box ' . $messageType . '" style="' . $boxStyle . '">' . $message . '</div>';
    }
}
if (function_exists('showJsAlert') === false) {
    /**
     * Show Javascript alert box.
     *
     * @param string $message Message string parameter.
     *
     * @return void
     */
    function showJsAlert($message)
    {
        echo '<script>alert("' . $message . '")</script>';
    }
}
if (function_exists('getCheckedValue') === false) {
    /**
     * Get all the checked id data that want to be deleted.
     *
     * @param array $checkedArr   Checked array data parameter.
     * @param array $postFieldArr Post field that will be fetched to combined with the checked array.
     *
     * @return array
     */
    function getCheckedValue(array $checkedArr, array $postFieldArr)
    {
        return array_filter(
            $postFieldArr,
            function ($index) use ($checkedArr) {
                return in_array($index, $checkedArr, true) === true;
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
