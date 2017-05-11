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

defined('STANDARD_FUNCTION_LOADED') === true or die('STANDARD FUNCTION IS NOT LOADED YET');
doInclude('includes/datagrid2/datagrid.php');

if (function_exists('getBuildDatagrid') === false) {
    function getBuildDatagrid(array $datagridModel = [], array $datagridOptions = []){
        $defaultNormalizedFormElements = [
            'columnName' => '',
            'fieldName' => null,
            'titleAttr' => [],
            'attr' => []
        ];
    }
}

