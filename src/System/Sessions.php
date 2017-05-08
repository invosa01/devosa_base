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
defined('STANDARD_FUNCTION_LOADED') === true or die('STANDARD FUNCTION IS NOT LOADED YET');
/**
 * A Flag sign if standard session library has been loaded or not.
 *
 * @constant boolean STANDARD_SESSION_LIBRARY_LOADED
 */
define('STANDARD_SESSION_LIBRARY_LOADED', true);
if (function_exists('generateSessionFingerPrint') === false) {
    /**
     * Get finger print identifier for session.
     *
     * @param string $fingerPrintIdPrefix Finger print identifier prefix parameter.
     *
     * @return string
     */
    function generateSessionFingerPrint($fingerPrintIdPrefix = 'INV')
    {
        $fingerPrint = md5(preg_replace('/[^a-zA-Z0-9]/', '', getServerValue('HTTP_USER_AGENT') . session_id()));
        return $fingerPrintIdPrefix . $fingerPrint;
    }
}
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
if (function_exists('initSessions') === false) {
    /**
     * Initialize session service.
     *
     * @return void
     */
    function initSessions()
    {
        if (isSessionStarted() === false) {
            session_start();
        }
    }
}
if (function_exists('getActiveFingerPrintSessionValue') === false) {
    /**
     * Get active finger-print session value.
     *
     * @return string
     */
    function getActiveFingerPrintSessionValue()
    {
        return $_SESSION['fingerPrintId'];
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
        initSessions();
        return $_SESSION;
    }
}
if (function_exists('getSessionValue') === false) {
    /**
     * Get session item value property.
     *
     * @param string  $sessionName  SESSION field name parameter.
     * @param mixed   $defaultValue Default value parameter.
     * @param mixed   $mappedValue  Mapped value if the field name exists.
     * @param boolean $autoCreate   Auto create and init the assigned session name flag option parameter.
     *
     * @return mixed
     */
    function getSessionValue($sessionName, $defaultValue = null, $mappedValue = null, $autoCreate = false)
    {
        if ($autoCreate === true and array_key_exists($sessionName, getSessions()) === false) {
            setSessionValue($sessionName, $defaultValue);
            return $defaultValue;
        }
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
    function getSessionValues(array $fields = [])
    {
        return getFilteredArrayWithKeys(getSessions(), $fields);
    }
}
if (function_exists('setSessionValue') === false) {
    /**
     * Set session item value property.
     *
     * @param string|array $sessionName  SESSION field name parameter.
     * @param mixed        $sessionValue Assigned value parameter.
     * @param boolean      $overwrite    Overwrite existing session key flag option parameter.
     *
     * @throws \RuntimeException Failed to write new session because given session name has been locked.
     * @return void
     */
    function setSessionValue($sessionName, $sessionValue, $overwrite = true)
    {
        initSessions();
        if ($overwrite === false) {
            addLockedSessionVariable($sessionName);
        }
        # Check first if the session name is locked or not.
        if (isSessionLocked($sessionName) === true) {
            throw new \RuntimeException('Cannot overwrite existing session: ' . $sessionName . ' (locked-session)');
        }
        if (getValue(getSessionValue($sessionName)) === null or $overwrite === true) {
            setArrayItemValueByRefString($sessionName, $sessionValue, $_SESSION);
        }
    }
}
if (function_exists('getLockedSessions') === false) {
    /**
     * Get all locked sessions data collection.
     *
     * @return array
     */
    function getLockedSessions()
    {
        return (array)getSessionValue('lockedSession');
    }
}
if (function_exists('isSessionLocked') === false) {
    /**
     * Check if a session is locked or not.
     *
     * @param string $sessionName Session name parameter.
     *
     * @return boolean
     */
    function isSessionLocked($sessionName)
    {
        return in_array($sessionName, getLockedSessions(), true);
    }
}
if (function_exists('addLockedSessionVariable') === false) {
    /**
     * Add specified session variable name into locked session container.
     *
     * @param string $sessionName Session variable name parameter.
     *
     * @throws \RuntimeException If given session name has been added into locked-sessions already.
     * @return void
     */
    function addLockedSessionVariable($sessionName)
    {
        if (isSessionExists('lockedSession') === false) {
            setSessionValue('lockedSession', ['lockedSession']);
        }
        $lockedSessions = getLockedSessions();
        if (in_array($sessionName, $lockedSessions, true) === false) {
            $lockedSessions[] = $sessionName;
            setSessionValue('lockedSession', $lockedSessions);
            return;
        }
        throw new \RuntimeException('Session variable: ' . $sessionName . ' has been locked');
    }
}
if (function_exists('setSessionValues') === false) {
    /**
     * Set session data using array data source.
     *
     * @param array   $sessionData Session array data source parameter.
     * @param boolean $overwrite   Overwrite existing session key flag option parameter.
     *
     * @return void
     */
    function setSessionValues(array $sessionData, $overwrite = true)
    {
        foreach ($sessionData as $sessionName => $sessionValue) {
            setSessionValue($sessionName, $sessionValue, $overwrite);
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
if (function_exists('unsetSessions') === false) {
    /**
     * Unset given session names from $_SESSION global variable.
     *
     * @param array $sessionNames Session name data collection parameter.
     *
     * @return void
     */
    function unsetSessions(array $sessionNames)
    {
        foreach ($sessionNames as $sessionName) {
            unsetSession($sessionName);
        }
    }
}
if (function_exists('getFlashMessages') === false) {
    /**
     * Get flash messages session data collection.
     *
     * @return array
     */
    function getFlashMessages()
    {
        return (array)getSessionValue('flashMessageCollection');
    }
}
if (function_exists('getFlashMessage') === false) {
    /**
     * Get flash message that using sessions data.
     *
     * @param string  $flashMessageName Flash message name parameter.
     * @param mixed   $defaultValue     Default value if session flash doesn't exist yet.
     * @param mixed   $mappedValue      Mapped value if the field name exists.
     * @param boolean $unMount          Automatic un-mount the flash message data.
     *
     * @return mixed
     */
    function getFlashMessage($flashMessageName, $defaultValue = null, $mappedValue = null, $unMount = true)
    {
        $flashMessage = getSessionValue($flashMessageName, $defaultValue, $mappedValue);
        if (array_key_exists($flashMessageName, getFlashMessages()) === true and $unMount === true) {
            unsetSession($flashMessageName);
            unset($_SESSION['flashMessageCollection'][$flashMessageName]);
        }
        return $flashMessage;
    }
}
if (function_exists('getFlashAlertMessage') === false) {
    /**
     * Get flash alert message based on given related element.
     *
     * @param string $alertId Alert identifier parameter.
     *
     * @return array
     */
    function getFlashAlertMessage($alertId = '')
    {
        $result = [];
        $alertCollection = (array)getFlashMessage('alert');
        if (getValue($alertId) !== null and array_key_exists($alertId, $alertCollection) === true) {
            $result = (array)$alertCollection[$alertId];
            unset($_SESSION['alert'][$alertId], $_SESSION['flashMessageCollection']['alert'][$alertId]);
        }
        return $result;
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
        setSessionValue('flashMessageCollection[' . $flashMessageName . ']', true);
    }
}
if (function_exists('setFlashAlertMessage') === false) {
    /**
     * Set flash alert message session.
     *
     * @param string       $alertTitle   Alert title data parameter.
     * @param array|string $alertMessage Alert message data parameter.
     * @param string       $alertType    Alert type data parameter.
     * @param string       $alertId      Alert identifier parameter.
     * @param boolean      $dismissible  Dismissible alert flag option data parameter.
     *
     * @return void
     */
    function setFlashAlertMessage(
        $alertTitle = '',
        $alertMessage = '',
        $alertType = 'info',
        $alertId = '',
        $dismissible = true
    ) {
        $alertData = [
            'id'          => $alertId,
            'title'       => $alertTitle,
            'message'     => (array)$alertMessage,
            'type'        => $alertType,
            'dismissible' => $dismissible
        ];
        $alertSessionKey = 'alert';
        if (getValue($alertId) !== null) {
            $alertSessionKey = 'alert[' . $alertId . ']';
        }
        setFlashMessage($alertSessionKey, $alertData);
    }
}
if (function_exists('setFlashErrorAlertMessage') === false) {
    /**
     * Set flash error alert message session.
     *
     * @param string       $alertTitle   Alert title data parameter.
     * @param array|string $alertMessage Alert message data parameter.
     * @param string       $alertId      Alert identifier parameter.
     * @param boolean      $dismissible  Dismissible alert flag option data parameter.
     *
     * @return void
     */
    function setFlashErrorAlertMessage($alertTitle = '', $alertMessage = '', $alertId = '', $dismissible = true)
    {
        setFlashAlertMessage($alertTitle, $alertMessage, 'error', $alertId, $dismissible);
    }
}
if (function_exists('setFlashSuccessAlertMessage') === false) {
    /**
     * Set flash success alert message session.
     *
     * @param string       $alertTitle   Alert title data parameter.
     * @param array|string $alertMessage Alert message data parameter.
     * @param string       $alertId      Alert identifier parameter.
     * @param boolean      $dismissible  Dismissible alert flag option data parameter.
     *
     * @return void
     */
    function setFlashSuccessAlertMessage($alertTitle = '', $alertMessage = '', $alertId = '', $dismissible = true)
    {
        setFlashAlertMessage($alertTitle, $alertMessage, 'success', $alertId, $dismissible);
    }
}
if (function_exists('setFlashWarningAlertMessage') === false) {
    /**
     * Set flash warning alert message session.
     *
     * @param string       $alertTitle   Alert title data parameter.
     * @param array|string $alertMessage Alert message data parameter.
     * @param string       $alertId      Alert identifier parameter.
     * @param boolean      $dismissible  Dismissible alert flag option data parameter.
     *
     * @return void
     */
    function setFlashWarningAlertMessage($alertTitle = '', $alertMessage = '', $alertId = '', $dismissible = true)
    {
        setFlashAlertMessage($alertTitle, $alertMessage, 'warning', $alertId, $dismissible);
    }
}
if (function_exists('setFlashInfoAlertMessage') === false) {
    /**
     * Set flash info alert message session.
     *
     * @param string       $alertTitle   Alert title data parameter.
     * @param array|string $alertMessage Alert message data parameter.
     * @param string       $alertId      Alert identifier parameter.
     * @param boolean      $dismissible  Dismissible alert flag option data parameter.
     *
     * @return void
     */
    function setFlashInfoAlertMessage($alertTitle = '', $alertMessage = '', $alertId = '', $dismissible = true)
    {
        setFlashAlertMessage($alertTitle, $alertMessage, 'info', $alertId, $dismissible);
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
if (function_exists('setSessionTimeOutLimit') === false) {
    /**
     * Set timeout limit for active session.
     *
     * @param integer $sessionTimeOut Number of time out limit parameter.
     *
     * @return void
     */
    function setSessionTimeOutLimit($sessionTimeOut)
    {
        setSessionValue('sessionTimeOutLimit', $sessionTimeOut);
    }
}
if (function_exists('setSessionActiveTimer') === false) {
    /**
     * Set session for active timer.
     *
     * @return void
     */
    function setSessionActiveTimer()
    {
        setSessionValue('activeTimer', time());
    }
}
if (function_exists('isSessionHasBeenExpired') === false) {
    /**
     * Check if active session has been expired or over the time-out limit.
     *
     * @return boolean
     */
    function isSessionHasBeenExpired()
    {
        if (isSessionExists('activeTimer') === true and isSessionExists('sessionTimeOutLimit')) {
            $activeTime = (time() - (integer)getSessionValue('activeTimer'));
            if ($activeTime > (integer)getSessionValue('sessionTimeOutLimit')) {
                return true;
            }
        }
        return false;
    }
}
if (function_exists('runSessionService') === false) {
    /**
     * Running session service to check the active timer and expired php session.
     *
     * @return void
     */
    function runSessionService()
    {
        if (isSessionHasBeenExpired() === true and session_status() === PHP_SESSION_ACTIVE) {
            # Also don't forget to remove all active cookies.
            if (array_key_exists('PHPSESSID', $_COOKIE) === true and $_COOKIE['PHPSESSID'] === session_name()) {
                setcookie(session_name(), '', time() - (integer)getSysConfigItem('sessions.ExpiredCookieTime'), '/');
            }
            clearSessions(false);
        } else {
            # Set the session timeout limit and start the active timer.
            setSessionTimeOutLimit((integer)getAppliedConfigItem('sessions.SessionTimeOut'));
            setSessionActiveTimer();
        }
    }
}
