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
doInclude('includes/form2/form2.php');
if (function_exists('getBuildForm') === false) {
    function getBuildForm(array $formModel = [], array $formOptions = [])
    {
        $columnNo = (integer)getValue(getValueIfExistsOnArray('column', $formOptions), 1);
        $formCaption = (string)getValue(getValueIfExistsOnArray('caption', $formOptions), '');
        $references = (array)getValue(getValueIfExistsOnArray('references', $formOptions), []);
        $mode = 'edit';
        $referenceExits = true;
        foreach ($references as $reference) {
            if (getPostValue($reference) === false) {
                $referenceExits = false;
            }
        }
        if ($referenceExits === true) {
            $mode = 'insert';
        }
        # Normalized form element data collection.
        $defaultNormalizedFormElements = [
            'type'      => 'input',
            'label'     => '',
            'value'     => '',
            'attr'      => [],
            'dataType'  => 'string',
            'before'    => '',
            'after'     => '',
            'labelAttr' => []
        ];
        $defaultNormalizedFormElementsKeys = array_keys($defaultNormalizedFormElements);
        $formObject = new clsForm("formInput", $columnNo, "100%", "");
        $formObject->caption = $formCaption;
        $formObject->mode = $mode;
        foreach ($formModel as $fieldName => $fieldProps) {
            $normalizedFieldProps = [];
            foreach ($fieldProps as $key => $value) {
                if (is_integer($key) === true) {
                    $keyName = $defaultNormalizedFormElementsKeys[$key];
                    $normalizedFieldProps[$keyName] = $value;
                    continue;
                }
                $normalizedFieldProps[$key] = $value;
            }
            $normalizedFieldProps = getMergedArrayRecursively(
                $defaultNormalizedFormElements,
                $normalizedFieldProps
            );
            $type = $normalizedFieldProps['type'];
            $fieldAttributes = $normalizedFieldProps['attr'];
            $isDisabled = getValueIfExistsOnArray('disabled', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $isHidden = getValueIfExistsOnArray('hidden', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $isRequired = getValueIfExistsOnArray('required', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $noLabel = getValueIfExistsOnArray('noLabel', $fieldAttributes, true, SEARCH_ARR_BOTH);
            $serverAction = null;
            $value = null;
            if (in_array($type, ['submit', 'button']) === true) {
                $serverAction = $normalizedFieldProps['value'];
            }
            if (in_array($type, ['input', 'hidden']) === true) {
                $defaultValue = $normalizedFieldProps['value'];
                $dataType = $normalizedFieldProps['dataType'];
                if ($dataType === 'date') {
                    $defaultValue = date($_SESSION['sessionDateSetting']['php_format']);
                }
                $value = getFormPostValue($fieldName, $defaultValue);
            }
            if (in_array($type, ['select', 'options']) === true) {
                $value = [];
                $defaultValue = $normalizedFieldProps['value'];
                $value = getIntoRecorList($defaultValue);
            }
            # Process all passed field element properties into form object.
            switch ($type) {
                case 'submit':
                    $formObject->addSubmit(
                        $fieldName,
                        getWords($normalizedFieldProps['label']),
                        $normalizedFieldProps['attr'],
                        ($isDisabled === false),
                        ($isHidden === false),
                        $normalizedFieldProps['before'],
                        $normalizedFieldProps['after'],
                        $serverAction
                    );
                    break;
                case 'hidden':
                    $formObject->addHidden($fieldName, $value);
                    break;
                case 'textarea' :
                    $formObject->addTextArea(
                        getWords($normalizedFieldProps['label']),
                        $fieldName,
                        $value,
                        $normalizedFieldProps['attr'],
                        $dataType,
                        $isRequired,
                        ($isDisabled === false),
                        ($isHidden === false),
                        $normalizedFieldProps['before'],
                        $normalizedFieldProps['after'],
                        ($noLabel === false),
                        $normalizedFieldProps['labelAttr']
                    );
                    break;
                case 'select' :
                    $formObject->addSelect(
                        getWords($normalizedFieldProps['label']),
                        $fieldName,
                        $value,
                        $normalizedFieldProps['attr'],
                        $dataType,
                        $isRequired,
                        ($isDisabled === false),
                        ($isHidden === false),
                        $normalizedFieldProps['before'],
                        $normalizedFieldProps['after'],
                        ($noLabel === false),
                        $normalizedFieldProps['labelAttr']
                    );
                    break;
                case 'input':
                default:
                    $calendarUpdate = getValueIfExistsOnArray('calendarUpdate', $normalizedFieldProps, SEARCH_ARR_BOTH);
                    $formObject->addInput(
                        getWords($normalizedFieldProps['label']),
                        $fieldName,
                        $value,
                        $normalizedFieldProps['attr'],
                        $dataType,
                        $isRequired,
                        ($isDisabled === false),
                        ($isHidden === false),
                        $normalizedFieldProps['before'],
                        $normalizedFieldProps['after'],
                        ($noLabel === false),
                        $normalizedFieldProps['labelAttr'],
                        $calendarUpdate
                    );
                    break;
            }
        }
        return $formObject;
    }
}
if (function_exists('getFormPostValue') === false) {
    function getFormPostValue($fieldName, $defaultValue = null)
    {
        # Priority : post, session, value.
        if (array_key_exists($fieldName, $_POST) === true) {
            return $_POST[$fieldName];
        }
        if (array_key_exists($fieldName, $_SESSION) === true) {
            return $_SESSION[$fieldName];
        }
        return $defaultValue;
    }
}
if (function_exists('getIntoRecorList') === false) {
    function getIntoRecorList(array $defaultValue = [])
    {
        $result = '';
        $model = [
            'database' => '',
            'code'     => '',
            'name'     => ''
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
        $database = $normalizedFieldProps['database'];
        $code = $normalizedFieldProps['code'];
        $name = $normalizedFieldProps['name'];
        if ($database !== '') {
            $result = '<option value="">-</option>';
            $strSql = "SELECT 
                         * 
                       FROM 
                         $database ";
            $record = pgFetchRows($strSql);
            foreach ($record as $row) {
                $result .= '<option value="' . $row[$code] . '">'
                    . $row[$code]
                    . ' - '
                    . $row[$name]
                    . '</option>';
            }
        }
        return $result;
    }
}