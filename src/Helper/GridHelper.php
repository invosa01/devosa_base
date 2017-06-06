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
if (function_exists('getBuildDataGrid') === false) {
    /**
     * Get grid object.
     *
     * @param array $modelData Model data that will bind into grid object.
     * @param array $columnSet Column data set collection parameter.
     * @param array $options   Grid option data collection parameter.
     *
     * @throws \RuntimeException If incomplete column set grid data given.
     * @throws \LogicException If invalid column type given.
     * @return \cDataGrid
     */
    function getBuildDataGrid(array $modelData, array $columnSet = [], array $options = [])
    {
        global $dataGridObj;
        # Fetch and parse all grid options into specific variable.
        $defaultCalledFile = basename($_SERVER['PHP_SELF']);
        $formName = (string)getValue(getValueIfExistsOnArray('formName', $options), '');
        $gridName = (string)getValue(getValueIfExistsOnArray('gridName', $options), '');
        $gridWidth = (string)getValue(getValueIfExistsOnArray('gridWidth', $options), '100%');
        $gridHeight = (string)getValue(getValueIfExistsOnArray('gridHeight', $options), '100%');
        $showPageLimit = (boolean)getValue(getValueIfExistsOnArray('showPageLimit', $options), true);
        $showSearch = (boolean)getValue(getValueIfExistsOnArray('showSearch', $options), true);
        $showSort = (boolean)getValue(getValueIfExistsOnArray('showSort', $options), true);
        $showPageNumbering = (boolean)getValue(getValueIfExistsOnArray('showPageNumbering', $options), true);
        $showExportXlsButton = (boolean)getValue(getValueIfExistsOnArray('showExportXlsButton', $options), true);
        $path = (string)getValue(getValueIfExistsOnArray('path', $options), null);
        $gridCaption = (string)getValue(getValueIfExistsOnArray('caption', $options), '');
        $gridButtons = (array)getValue(getValueIfExistsOnArray('buttons', $options), []);
        $ajaxCallbackUrl = (string)getValue(getValueIfExistsOnArray('ajaxCallbackUrl', $options), $defaultCalledFile);
        $calledFile = (string)getValue(getValueIfExistsOnArray('calledFile', $options), $defaultCalledFile);
        $columnProperty = [
            'value'     => '',
            'attr'      => [],
            'type'      => 'string',
            'formatter' => null,
            'form'      => [],
            'options'   => []
        ];
        # Create data grid instance.
        $dataGridObj = new \cDataGrid(
            $formName,
            $gridName,
            $gridWidth,
            $gridHeight,
            $showPageLimit,
            $showSearch,
            $showSort,
            $showPageNumbering,
            $path
        );
        $dataGridObj->caption = $gridCaption;
        $dataGridObj->setAJAXCallBackScript($ajaxCallbackUrl);
        if ($showExportXlsButton === true) {
            $dataGridObj->addButtonExportExcel('Export To Excel', $gridName . '.xls');
        }
        # Validate the column set.
        if (array_key_exists('head', $columnSet) === false or array_key_exists('content', $columnSet) === false) {
            throw new \RuntimeException('Incomplete column set grid data given');
        }
        # Formatting the cell based on the given formatter.
        $cellFormat = function ($value, array $formatter = []) {
        };
        # Create the grid column instance.
        $generateGridColumn = function (\cDataGrid $dataGridObj, array $params) {
            $type = trim(strtolower($params['dataType']));
            # Validate the column data type.
            $validColTypes = ['string', 'auto', 'checkbox'];
            if (in_array($type, $validColTypes, true) === false) {
                throw new \LogicException('Invalid column type given: ' . json_encode($params));
            }
            $dataGridColumnObj = new DataGrid_Column(
                $params['label'],
                $params['fieldName'],
                $params['titleAttr'],
                $params['attr'],
                $params['sortable'],
                $params['searchable'],
                $params['titleFormatter'],
                $params['itemFormatter'],
                $params['dataType'],
                $params['showInExcel'],
                $params['xlsColWidth'],
                $params['grouped'],
                $params['groupField'],
                $params['isSummary']
            );
            switch ($type) {
                case 'checkbox' :
                    $dataGridObj->addColumnCheckbox($dataGridColumnObj);
                    break;
                case 'auto' :
                    $dataGridObj->addColumnNumbering($dataGridColumnObj);
                    break;
                default:
                    $dataGridObj->addColumn($dataGridColumnObj);
            }
        };
        $normalizedColHeadData = getNormalizedArrays($columnSet['head'], $columnProperty);
        $normalizedColContentData = getNormalizedArrays($columnSet['content'], $columnProperty);
        # Fetch all fields that want to be fetched.
        $columnKeys = array_keys($columnSet['content']);
        foreach ($columnKeys as $columnKey) {
            $colParams = [];
            $colType = 'string';
            # Parse the column head property arguments.
            if (array_key_exists($columnKey, $normalizedColHeadData) === true) {
                $colData = $normalizedColHeadData[$columnKey];
                $colOptions = $colData['options'];
                $isNotSortable = getValueIfExistsOnArray('sortable', $colOptions, true, SEARCH_ARR_BOTH);
                $isNotSearchable = getValueIfExistsOnArray('searchable', $colOptions, true, SEARCH_ARR_BOTH);
                $isHideInExcel = getValueIfExistsOnArray('showInExcel', $colOptions, true, SEARCH_ARR_BOTH);
                $isGrouped = getValueIfExistsOnArray('grouped', $colOptions, true, SEARCH_ARR_BOTH);
                $isSummary = getValueIfExistsOnArray('isSummary', $colOptions, true, SEARCH_ARR_BOTH);
                $xlsColWidth = (integer)getValue(getValueIfExistsOnArray('xlsColWidth', $colOptions), 0);
                $groupField = (string)getValue(getValueIfExistsOnArray('groupField', $colOptions), '');
                $colParams = [
                    'label'          => $colData['value'],
                    'titleAttr'      => $colData['attr'],
                    'sortable'       => ($isNotSortable === false),
                    'searchable'     => ($isNotSearchable === false),
                    'showInExcel'    => ($isHideInExcel === false),
                    'titleFormatter' => $colData['formatter'],
                    'grouped'        => $isGrouped,
                    'isSummary'      => $isSummary,
                    'xlsColWidth'    => $xlsColWidth,
                    'groupField'     => $groupField
                ];
            }
            # Parse the column content property arguments.
            if (array_key_exists($columnKey, $normalizedColContentData) === true) {
                $colData = $normalizedColContentData[$columnKey];
                $colParams['fieldName'] = $colData['value'];
                $colParams['attr'] = $colData['attr'];
                $colParams['itemFormatter'] = $colData['formatter'];
                $colParams['dataType'] = $colType = $colData['type'];
            }
            # Call the grid column instance generator.
            $generateGridColumn($dataGridObj, $colParams);
        }
        # Render all special buttons.
        foreach ($gridButtons as $btnName => $btnProps) {
            $defaultBtnIdentifier = 'btn_' . microtime();
            $defaultBtnProps = [
                'accessName'   => null,
                'value'        => '',
                'clientAction' => '',
                'serverAction' => '',
                'id'           => '',
                'name'         => getMappedValue(is_integer($btnName) === true, $defaultBtnIdentifier, $btnName),
                'type'         => 'submit',
                'btnClass'     => 'btn-primary',
            ];
            $defaultBtnPropsKeys = array_keys($defaultBtnProps);
            $normalizedBtnProps = getNormalizedArray($btnProps, $defaultBtnProps, $defaultBtnPropsKeys);
            addRoleButton($calledFile, $dataGridObj, 'sessionPrivileges', $normalizedBtnProps);
        }

        $dataGridObj->getRequest();
        $dataGridObj->totalData = count($modelData);
        $dataGridObj->bind($modelData);
        setFlashMessage($gridName, serialize($dataGridObj));
        return $dataGridObj;
    }
}
if (function_exists('addRoleButton') === false) {
    /**
     * Add specified role button into given data grid instance object.
     *
     * @param string     $calledFile  Called file to validate the page role session data.
     * @param \cDataGrid $dataGridObj Data grid object data parameter.
     * @param string     $sessionName Session name that will be checked.
     * @param array      $btnProps    Button property data collection parameter.
     *
     * @return void
     */
    function addRoleButton($calledFile, \cDataGrid $dataGridObj, $sessionName, array $btnProps)
    {
        # Initialize local variables.
        $privilegeSessionData = [];
        $isAllowed = false;
        try {
            $privilegeSessionData = $_SESSION[$sessionName];
        } catch (\Exception $ex) {
            throw new \RuntimeException($ex->getMessage());
        }
        if (getValue($btnProps['id']) === null) {
            $btnProps['id'] = $btnProps['name'];
        }
        if (getValue($btnProps['value']) === null) {
            $btnProps['value'] = $btnProps['name'];
        }
        $requiredAccessName = $btnProps['accessName'];
        if (count($privilegeSessionData) > 0 and array_key_exists($calledFile, $privilegeSessionData) === true) {
            $pageRoleSessionData = $privilegeSessionData[$calledFile];
            if (array_key_exists($requiredAccessName, $pageRoleSessionData) === true) {
                $rolePagePrivileges = $pageRoleSessionData[$requiredAccessName];
                $allowedScope = ['t', 'y', true];
                $isAllowed = (in_array($rolePagePrivileges, $allowedScope, true) === true);
            }
        }
        if ($isAllowed === true) {
            $dataGridObj->addSpecialButton(
                $btnProps['id'],
                $btnProps['name'],
                $btnProps['type'],
                $btnProps['value'],
                $btnProps['clientAction'],
                $btnProps['serverAction'],
                $btnProps['btnClass']
            );
        }
    }
}
if (function_exists('getNormalizedArray') === false) {
    /**
     * Normalize given array.
     *
     * @param array $arr             Source data array parameter.
     * @param array $defaultProps    Default array property parameter.
     * @param array $defaultPropKeys Default property key data parameter.
     *
     * @return array
     */
    function getNormalizedArray(array $arr, array $defaultProps, array $defaultPropKeys)
    {
        $normalizedData = [];
        foreach ($arr as $key => $value) {
            if (is_integer($key) === true) {
                $keyName = $defaultPropKeys[$key];
                $normalizedData[$keyName] = $value;
                continue;
            }
            $normalizedData[$key] = $value;
        }
        return getMergedArrayRecursively(
            $defaultProps,
            $normalizedData
        );
    }
}
if (function_exists('getNormalizedArrays') === false) {
    /**
     * Get normalized of given array data collection.
     *
     * @param array $dataSource   Source data array parameter.
     * @param array $defaultProps Default array property parameter.
     *
     * @return array
     */
    function getNormalizedArrays(array $dataSource, array $defautProps)
    {
        $defautPropKeys = array_keys($defautProps);
        $result = [];
        foreach ($dataSource as $index => $props) {
            $result[$index] = getNormalizedArray($props, $defautProps, $defautPropKeys);
        }
        return $result;
    }
}
