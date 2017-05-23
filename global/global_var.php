<?php
/**
 * Code written is strictly used within this program.
 * Any other use of this code is in violation of copy rights.
 *
 * @package   -
 * @author    Muhammad Faisal Setyawan <setyawan@invosa.com>
 * @copyright 2016 Developer
 * @license   - No License
 * @version   GIT: $Id$
 * @link      -
 */
require_once __DIR__ . '/../src/System/Standards.php';
require_once __DIR__ . '/common_variable.php';
if (function_exists('getGlobalVariable') === false) {
    /**
     * .
     * @return
     */
    function getGlobalVariable($var)
    {
        $privileges = null;
        global $strDateWidth, $formObject;
        $model = [];
        $model = [
            'formObject' => $formObject,
            'strDateWidth' => $strDateWidth
        ];
        return $GLOBALS[$model[$var]];
    }
}
