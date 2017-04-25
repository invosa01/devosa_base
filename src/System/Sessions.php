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
defined('STANDARD_FUNCTION_LOADED') === true or die('STANDARD FUNCTION NOT LOADED YET');
if (function_exists('isSessionStarted') === false) {
    /**
     * Check if session has been started or not yet.
     *
     * @return boolean
     */
    function isSessionStarted()
    {
        return session_id() !== '' or session_status() === PHP_SESSION_ACTIVE;
    }
}
if (function_exists('loadSessions') === false) {
    /**
     * Load session if not started yet.
     *
     * @return void
     */
    function loadSessions()
    {
        if (isSessionStarted() === false) {
            session_start();
        }
    }
}
if (function_exists('getSessions') === false) {
    /**
     * Get all $_SESSION data.
     *
     * @return array
     */
    function getSessions()
    {
        loadSessions();
        return $_SESSION;
    }
}
if (function_exists('getSessionValue') === false) {
    /**
     * Get session item value property.
     *
     * @param string $sessionName  SESSION field name parameter.
     * @param mixed  $defaultValue Default value parameter.
     * @param mixed  $mappedValue  Mapped value if the field name exists.
     *
     * @return mixed
     */
    function getSessionValue($sessionName, $defaultValue = '', $mappedValue = '')
    {
        return getArrayItemValue(getSessions(), $sessionName, $defaultValue, $mappedValue);
    }
}
if (function_exists('getSessionValues') === false) {
    /**
     * Get all session values property with given fields filter.
     *
     * @param array $fields Field name collection data parameter.
     *
     * @return array
     */
    function getSessionValues(array $fields)
    {
        return getFilteredArrayWithKeys(getSessions(), $fields);
    }
}
if (function_exists('setSessionValue') === false) {
    /**
     * Set session item value property.
     *
     * @param string $sessionName  SESSION field name parameter.
     * @param mixed  $sessionValue Assigned value parameter.
     *
     * @return void
     */
    function setSessionValue($sessionName, $sessionValue)
    {
        loadSessions();
        $_SESSION[$sessionName] = $sessionValue;
    }
}
if (function_exists('setSessionValues') === false) {
    /**
     * Set session data using array data source.
     *
     * @param array $sessionData Session array data source parameter.
     *
     * @return void
     */
    function setSessionValues(array $sessionData)
    {
        foreach ($sessionData as $sessionName => $sessionValue) {
            setSessionValue($sessionName, $sessionValue);
        }
    }
}
if (function_exists('unsetSession') === false) {
    /**
     * Unset $_SESSION item property.
     *
     * @param string $sessionName SESSION field name parameter.
     *
     * @return void
     */
    function unsetSession($sessionName)
    {
        unset($_SESSION[$sessionName]);
    }
}
if (function_exists('getFlashMessage') === false) {
    /**
     * Get flash message that using sessions data.
     *
     * @param string $flashMessageName Flash message name parameter.
     *
     * @return string|null
     */
    function getFlashMessage($flashMessageName)
    {
        $flashMessage = null;
        if (array_key_exists($flashMessageName, (array)getSessionValue('flashMessageCollection')) === true) {
            $flashMessage = getSessionValue($flashMessageName);
            unsetSession($flashMessageName);
            unset($_SESSION['flashMessageCollection'][$flashMessageName]);
        }
        return $flashMessage;
    }
}
if (function_exists('isSessionExists') === false) {
    /**
     * Check the existence of sessions item.
     *
     * @param string $sessionName SESSION field name parameter.
     *
     * @return boolean
     */
    function isSessionExists($sessionName)
    {
        return array_key_exists($sessionName, getSessions());
    }
}
if (function_exists('setFlashMessage') === false) {
    /**
     * Set flash message using sessions data manipulation.
     *
     * @param string $flashMessageName Flash message name parameter.
     * @param string $message          Message string parameter.
     *
     * @return void
     */
    function setFlashMessage($flashMessageName, $message)
    {
        setSessionValue($flashMessageName, $message);
        setSessionValue(
            'flashMessageCollection',
            array_merge((array)getSessionValue('flashMessageCollection'), [$flashMessageName => true])
        );
    }
}
if (function_exists('resetFlashMessage') === false) {
    /**
     * Reset all flash message container.
     *
     * @return void
     */
    function resetFlashMessage()
    {
        unset($_SESSION['flashMessageCollection']);
        setSessionValue('flashMessageCollection', []);
    }
}
if (function_exists('clearSessions') === false) {
    /**
     * Clearing all the active session and destroy it.
     *
     * @param boolean $closeSession Close the session instance flag option parameter.
     *
     * @return void
     */
    function clearSessions($closeSession = true)
    {
        session_unset();
        if ($closeSession === true) {
            session_destroy();
        }
    }
}
