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
if (function_exists('getBuildGrid') === false) {
    function getBuildGrid(array $gridModel = [], array $gridOptions = [], array $gridDataBinding)
    {
        $gridCaption = (string)getValue(getValueIfExistsOnArray('caption', $gridOptions), '');
        $defaultNormalizedGridElements = [
            'type'      => 'data',
            'label'     => '',
            'fieldName' => '',
            'titleAttr' => [],
            'attr'      => [],
            'titleFmtr' => '',
            'itemFmtr'  => '',
            'dataType'  => 'string'
        ];
        $defaultNormalizedGridElementsKeys = array_keys($defaultNormalizedGridElements);
        $conDb = new CdbClass;
        $gridContents = new cDataGrid("formData", "DataGrid1");
        $gridContents->caption = $gridCaption;
        $gridContents->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
        foreach ($gridModel as $fieldName => $fieldProps) {
            $normalizedFieldProps = [];
            foreach ($fieldProps as $key => $value) {
                if (is_integer($key) === true) {
                    $keyName = $defaultNormalizedGridElementsKeys[$key];
                    $normalizedFieldProps[$keyName] = $value;
                    continue;
                }
                $normalizedFieldProps[$key] = $value;
            }
            $normalizedFieldProps = getMergedArrayRecursively(
                $defaultNormalizedGridElements,
                $normalizedFieldProps
            );
            $type = $normalizedFieldProps['type'];
            $fieldAttributes = $normalizedFieldProps['attr'];
            $isNotSortable = getValueIfExistsOnArray('sortable', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $isNotSearchable = getValueIfExistsOnArray('searchable', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $isHideInExcel = getValueIfExistsOnArray('showInExcel', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $clientAction = null;
            $serverAction = null;
            if (in_array($type, ['submit', 'button']) === true) {
                $clientAction = $normalizedFieldProps['itemFmtr'];
                $serverAction = $normalizedFieldProps['titleFmtr'];
                $name = $fieldName;
            }
            if (in_array($type, ['role', 'buttons']) === true) {
                $delete = false;
                $defaultValue = $normalizedFieldProps['titleAttr'];
                $buttons = getGenerateRoleButtons($defaultValue);
                $edit = setReleaseModel('edit', $buttons);
                $delete = setReleaseModel('delete', $buttons);
                $check = setReleaseModel('check', $buttons);
                $approve = setReleaseModel('approve', $buttons);
                $acknowledge = setReleaseModel('acknowledge', $buttons);
            }
            switch ($type) {
                case 'exportExl' :
                    $gridContents->addButtonExportExcel(
                        $normalizedFieldProps['label'],
                        $fieldName . '.xls'
                    );
                    break;
                case 'role' :
                    generateRoleButtons(
                        $edit,
                        $delete,
                        $check,
                        $approve,
                        $acknowledge,
                        true,
                        $gridContents
                    );
                    break;
                case 'submit'  :
                    $gridContents->addSpecialButton(
                        $fieldName,
                        $name,
                        $type,
                        $normalizedFieldProps['label'],
                        $clientAction,
                        $serverAction
                    );
                    break;
                case 'checked' :
                    $gridContents->addColumnCheckbox(
                        new DataGrid_Column(
                            $normalizedFieldProps['label'],
                            $fieldName,
                            $normalizedFieldProps['attr'],
                            $normalizedFieldProps['titleAttr'],
                            ($isNotSortable === false),
                            ($isNotSearchable === false),
                            $normalizedFieldProps['titleFmtr'],
                            $normalizedFieldProps['itemFmtr']
                        )
                    );
                    break;
                case 'no' :
                    $gridContents->addColumnNumbering(
                        new DataGrid_Column(
                            getWords($normalizedFieldProps['label']),
                            $fieldName,
                            $normalizedFieldProps['titleAttr'],
                            $normalizedFieldProps['attr'],
                            ($isNotSortable === false),
                            ($isNotSearchable === false),
                            $normalizedFieldProps['titleFmtr'],
                            $normalizedFieldProps['itemFmtr'],
                            $normalizedFieldProps['dataType']
                        )
                    );
                    break;
                case 'data' :
                default:
                    $gridContents->addColumn(
                        new DataGrid_Column(
                            getWords($normalizedFieldProps['label']),
                            $fieldName,
                            $normalizedFieldProps['titleAttr'],
                            $normalizedFieldProps['attr'],
                            ($isNotSortable === false),
                            ($isNotSearchable === false),
                            $normalizedFieldProps['titleFmtr'],
                            $normalizedFieldProps['itemFmtr'],
                            $normalizedFieldProps['dataType'],
                            ($isHideInExcel === false)
                        )
                    );
                    break;
            }
        }
        $gridContents->getRequest();
        foreach ($gridDataBinding as $queryName => $queryProps) {
            $normalizedQueryProps = [];
            $normalizedQueryProps[$queryName] = $queryProps;
        }
        $normalizedFieldProps = getMergedArrayRecursively(
            $gridDataBinding,
            $normalizedQueryProps
        );
        $strSqlCount = $normalizedFieldProps['strSqlCount'];
        $gridContents->totalData = $gridContents->getTotalData($conDb, $strSqlCount);
        $strSql = $normalizedFieldProps['strSql'];
        $gridContents->bind($strSql);
        return $gridContents;
    }
}
if (function_exists('getGenerateRoleButtons') === false) {
    function getGenerateRoleButtons(array $defaultValue = [])
    {
        $calledFile = basename($_SERVER['PHP_SELF']);
        $privileges = getDataPrivileges($calledFile);
        $model = [
            'edit'        => '',
            'delete'      => '',
            'check'       => '',
            'approve'     => '',
            'acknowledge' => ''
        ];
        $defaultNormalizedRecordKeys = array_keys($model);
        $normalizedFieldProps = [];
        foreach ($defaultValue as $item => $value) {
            if (is_integer($item) === true) {
                $keyName = $defaultNormalizedRecordKeys[$item];
                $normalizedFieldProps[$keyName] = $value;
                continue;
            }
            $normalizedFieldProps[$item] = $value;
        }
        $normalizedFieldProps = getMergedArrayRecursively(
            $model,
            $normalizedFieldProps
        );
        if (($edit = $normalizedFieldProps['edit']) === 'edit') {
            $edit = $privileges[$normalizedFieldProps['edit']];
        } else {
            $edit = false;
        }
        if (($delete = $normalizedFieldProps['delete']) === 'delete') {
            $delete = $privileges[$normalizedFieldProps['delete']];
        } else {
            $delete = false;
        }
        if (($check = $normalizedFieldProps['check']) === 'check') {
            $check = $privileges[$normalizedFieldProps['check']];
        } else {
            $check = false;
        }
        if (($approve = $normalizedFieldProps['approve']) === 'approve') {
            $approve = $privileges[$normalizedFieldProps['approve']];
        } else {
            $approve = false;
        }
        if (($acknowledge = $normalizedFieldProps['acknowledge']) === 'acknowledge') {
            $acknowledge = $privileges[$normalizedFieldProps['acknowledge']];
        } else {
            $acknowledge = false;
        }
        $modelRole =
            [
                'edit'        => $edit,
                'delete'      => $delete,
                'check'       => $check,
                'approve'     => $edit,
                'acknowledge' => $acknowledge
            ];
        return $modelRole;
    }
}
if (function_exists('setReleaseModel') === false) {
    function setReleaseModel($name, array $modelRole = [])
    {
        $normalized = '';
        if (array_key_exists($name, $modelRole) === true) {
            $normalized = $modelRole[$name];
        }
        return $normalized;
    }
}

