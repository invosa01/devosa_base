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
if (function_exists('getMathStackPrecedenceSymbols') === false) {
    /**
     * Get math stack precedence symbols list data collection.
     *
     * @return string[]
     */
    function getMathStackPrecedenceSymbols()
    {
        return ['open' => '(', 'close' => ')'];
    }
}
if (function_exists('isOpenedMathPrecedenceSymbol') === false) {
    /**
     * Check if given string is opened math precedence symbol.
     *
     * @param string $symbol Symbol character parameter.
     *
     * @return boolean
     */
    function isOpenedMathPrecedenceSymbol($symbol)
    {
        return array_search(trim($symbol), getMathStackPrecedenceSymbols(), true) === 'open';
    }
}
if (function_exists('isClosedMathPrecedenceSymbol') === false) {
    /**
     * Check if given string is closed math precedence symbol.
     *
     * @param string $symbol Symbol character parameter.
     *
     * @return boolean
     */
    function isClosedMathPrecedenceSymbol($symbol)
    {
        return array_search(trim($symbol), getMathStackPrecedenceSymbols(), true) === 'close';
    }
}
if (function_exists('getMathOperatorList') === false) {
    /**
     * Get mathematical operator list data collection.
     *
     * @param boolean $getPrecedenceLevel   Get the operator precedence level flag option parameter.
     * @param boolean $onlyArithmeticSymbol Get only arithmetic symbol flag option parameter.
     *
     * @return array
     */
    function getMathOperatorList($getPrecedenceLevel = false, $onlyArithmeticSymbol = false)
    {
        $allOperatorSymbols = [
            '+' => 2,
            '-' => 2,
            '*' => 3,
            '/' => 3,
            '%' => 3, # Integer modulo symbol. eg: 5 % 2 = 1
            '?' => 3, # Float modulo symbol. eg: 5.7 ? 1.3 = 0.5
            '^' => 4, # Math exponential symbol. eg: 5 ^ 2 = 25
            '|' => 4, # Square symbol. eg: 8 | 2 = 3
            '(' => 1,
            ')' => 1,
            '@' => 5, # Rounding-half-down the floating point with specified precision. eg: 1.55 @ 1 = 1.5
            '$' => 5, # Rounding-half-up the floating point with specified precision. eg: 1.55 @ 1 = 1.6
            '!' => 5, # Natural logarithm symbol. eg: 100 ! 2 = 10
            '~' => 5  # Get random number with max and min value. eg: 10 ~ 100 = 66
        ];
        $result = (array)getMappedValue(
            $onlyArithmeticSymbol,
            array_diff_key($allOperatorSymbols, array_flip(getMathStackPrecedenceSymbols())),
            $allOperatorSymbols
        );
        if ($getPrecedenceLevel === false) {
            $result = array_keys($result);
        }
        return $result;
    }
}
if (function_exists('isMathOperator') === false) {
    /**
     * Check if given symbol is a math operator or not.
     *
     * @param string  $symbol             Symbol character parameter.
     * @param boolean $isArithmeticSymbol Only check arithmetic symbol flag option parameter.
     *
     * @return boolean
     */
    function isMathOperator($symbol, $isArithmeticSymbol = false)
    {
        return in_array($symbol, getMathOperatorList(false, $isArithmeticSymbol), true);
    }
}
if (function_exists('isMathPrecedenceSymbol') === false) {
    /**
     * Check if given symbol is a math precedence symbol or not.
     *
     * @param string $symbol Symbol character parameter.
     *
     * @return boolean
     */
    function isMathPrecedenceSymbol($symbol)
    {
        return in_array($symbol, getMathStackPrecedenceSymbols(), true);
    }
}
if (function_exists('getMathOperatorPrecedenceLevel') === false) {
    /**
     * Get the precedence of given operator symbol.
     *
     * @param string $symbol Symbol character parameter.
     *
     * @return integer
     */
    function getMathOperatorPrecedenceLevel($symbol)
    {
        $operatorPrecedences = getMathOperatorList(true);
        if (array_key_exists($symbol, $operatorPrecedences) === true) {
            return $operatorPrecedences[$symbol];
        }
        return 0;
    }
}
if (function_exists('getParsedMathExpression') === false) {
    /**
     * Split the math expression string, break them and put into array.
     *
     * @param string $mathExpression Math expression string data parameter.
     *
     * @return array
     */
    function getParsedMathExpression($mathExpression)
    {
        $mathExpression = trim($mathExpression) . ' ';
        # Initialize local variables.
        $result = [];
        $indexCounter = 0;
        $arrParsedExpression = (array)str_split($mathExpression);
        $numberExpressionDelimiter = array_merge(getMathOperatorList(), [' ']);
        # Initialize the expression component.
        $expressionComponent = '';
        foreach ($arrParsedExpression as $expressionItem) {
            $reset = false;
            $expressionComponent .= $expressionItem;
            if (in_array($expressionItem, $numberExpressionDelimiter, true) === true) {
                $reset = true;
                $indexCounter++;
                $expressionComponent = $expressionItem;
            }
            $result[$indexCounter] = $expressionComponent;
            if ($reset === true) {
                $indexCounter++;
                $expressionComponent = '';
            }
        }
        return array_values(array_filter(array_map('trim', $result)));
    }
}
if (function_exists('doValidateInfixMathExpressionArr') === false) {
    /**
     * Determine if given math expression data collection is valid  for infix expression or not.
     *
     * @param array $arrMathExpression Math expression data collection parameter.
     *
     * @throws \RuntimeException If invalid math expression array data given.
     * @return void
     */
    function doValidateInfixMathExpressionArr(array $arrMathExpression)
    {
        # Initialize all local variables.
        $isValid = true;
        $invalidNearValue = '';
        $invalidNearKey = 0;
        $openedPrecedenceSymbolCounter = 0;
        $closedPrecedenceSymbolCounter = 0;
        foreach ($arrMathExpression as $key => $value) {
            $isOpenedPrecedenceSymbol = isOpenedMathPrecedenceSymbol($value);
            $isClosedPrecedenceSymbol = isClosedMathPrecedenceSymbol($value);
            if (($isClosedPrecedenceSymbol === true and $key === 0) or
                (
                    array_key_exists($key - 1, $arrMathExpression) === true and
                    (
                        (
                            is_numeric($value) === true and
                            isMathOperator($arrMathExpression[$key - 1]) === false
                        )
                        or
                        (
                            isMathOperator($value, true) === true and
                            isMathOperator($arrMathExpression[$key - 1], true) === true
                        )
                        or
                        (
                            $isOpenedPrecedenceSymbol === true and
                            isClosedMathPrecedenceSymbol($arrMathExpression[$key - 1]) === true
                        )
                        or
                        (
                            $isClosedPrecedenceSymbol === true and
                            (
                                isMathOperator($arrMathExpression[$key - 1], true) === true or
                                isOpenedMathPrecedenceSymbol($arrMathExpression[$key - 1]) === true
                            )
                        )
                    )
                )
            ) {
                $invalidNearValue = $value;
                $invalidNearKey = $key;
                $isValid = false;
                break;
            }
            # Start to checking the opened and closed precedence symbol amount is balanced.
            if ($isOpenedPrecedenceSymbol === true) {
                $openedPrecedenceSymbolCounter++;
            }
            if ($isClosedPrecedenceSymbol === true) {
                $closedPrecedenceSymbolCounter++;
            }
        }
        if ($isValid === false) {
            throw new \RuntimeException(
                'Invalid math expression data given: ' .
                implode(' ', $arrMathExpression) . ' near: ' . $invalidNearValue .
                ' at index position: ' . $invalidNearKey
            );
        }
        if ($openedPrecedenceSymbolCounter !== $closedPrecedenceSymbolCounter) {
            throw new \RuntimeException(
                'Open-close bracket not matched: ' .
                implode(' ', $arrMathExpression) . ' near: ' . $invalidNearValue .
                ' at index position: ' . $invalidNearKey
            );
        }
    }
}
if (function_exists('isMathOperatorPrecedenceLevelHigher') === false) {
    /**
     * Compare the precedence level between given math operator string.
     *
     * @param string $symbol1 Symbol character parameter 1.
     * @param string $symbol2 Symbol character parameter 2.
     *
     * @return boolean
     */
    function isMathOperatorPrecedenceLevelHigher($symbol1, $symbol2)
    {
        return getMathOperatorPrecedenceLevel($symbol1) > getMathOperatorPrecedenceLevel($symbol2);
    }
}
if (function_exists('getConvertedInfixToPostfixArray') === false) {
    /**
     * Get converted infix into postfix array data collection.
     *
     * @param string $infixExpression Infix math expression string data parameter.
     *
     * @throws \RuntimeException If invalid infix expression detected.
     * @return array
     */
    function getConvertedInfixToPostfixArray($infixExpression)
    {
        $infixArray = getParsedMathExpression($infixExpression);
        try {
            doValidateInfixMathExpressionArr($infixArray);
        } catch (\Exception $ex) {
            throw new \RuntimeException($ex->getMessage());
        }
        # Initialize all required local variables.
        $arrPostfix = [];
        $arrStack = [];
        $postfixIndexCounter = 0;
        $stackIndexCounter = 0;
        # Put # as the start stack item value.
        $arrStack[0] = '#';
        foreach ($infixArray as $infixExpressionItem) {
            if (isMathOperator($infixExpressionItem) === false) {
                $arrPostfix[$postfixIndexCounter++] = $infixExpressionItem;
            } else {
                if (isOpenedMathPrecedenceSymbol($infixExpressionItem) === true) {
                    $arrStack[++$stackIndexCounter] = $infixExpressionItem;
                } else {
                    if (isClosedMathPrecedenceSymbol($infixExpressionItem) === true) {
                        while (isOpenedMathPrecedenceSymbol($arrStack[$stackIndexCounter]) === false) {
                            $arrPostfix[$postfixIndexCounter++] = $arrStack[$stackIndexCounter--];
                        }
                        # Pop out the latest opened math precedence symbol.
                        unset($arrStack[$stackIndexCounter--]);
                    } else {
                        if (isMathOperatorPrecedenceLevelHigher(
                                $infixExpressionItem,
                                $arrStack[$stackIndexCounter]
                            ) === false
                        ) {
                            while (isMathOperatorPrecedenceLevelHigher(
                                    $infixExpressionItem,
                                    $arrStack[$stackIndexCounter]
                                ) === false) {
                                $arrPostfix[$postfixIndexCounter++] = $arrStack[$stackIndexCounter--];
                            }
                        }
                        $arrStack[++$stackIndexCounter] = $infixExpressionItem;
                    }
                }
            }
        }
        # Pop out the rest of stack into postfix array collection.
        while ($stackIndexCounter !== 0) {
            $arrPostfix[$postfixIndexCounter++] = $arrStack[$stackIndexCounter--];
        }
        return $arrPostfix;
    }
}
if (function_exists('getConvertedInfixToPostfixExpression') === false) {
    /**
     * Get converted infix into postfix expression.
     *
     * @param string $infixExpression Infix math expression string data parameter.
     *
     * @throws \RuntimeException If invalid infix expression detected.
     * @return string
     */
    function getConvertedInfixToPostfixExpression($infixExpression)
    {
        return implode('', getConvertedInfixToPostfixArray($infixExpression));
    }
}
if (function_exists('getMathCalcResult') === false) {
    /**
     * Get mathematical calculation result of given operand and operator.
     *
     * @param number $operand1 Operand 1 parameter.
     * @param number $operand2 Operand 2 parameter.
     * @param string $operator Math operator parameter.
     *
     * @throws \RuntimeException If invalid math operator given.
     * @throws \RuntimeException If failed to calculate the result.
     * @return number
     */
    function getMathCalcResult($operand1, $operand2, $operator)
    {
        if (isMathOperator($operator, true) === false) {
            throw new \RuntimeException('Invalid math operator given');
        }
        $result = null;
        try {
            switch ($operator) {
                case '+':
                    $result = $operand1 + $operand2;
                    break;
                case '-':
                    $result = $operand1 - $operand2;
                    break;
                case '*':
                    $result = $operand1 * $operand2;
                    break;
                case '/':
                    $result = $operand1 / $operand2;
                    break;
                case '%':
                    $result = $operand1 % $operand2;
                    break;
                case '?':
                    $result = fmod($operand1, $operand2);
                    break;
                case '^':
                    $result = $operand1 ** $operand2;
                    break;
                case '|':
                    $result = $operand1 ** (1 / $operand2);
                    break;
                case '@':
                    $result = round($operand1, $operand2, PHP_ROUND_HALF_DOWN);
                    break;
                case '$':
                    $result = round($operand1, $operand2, PHP_ROUND_HALF_UP);
                    break;
                case '!':
                    $result = log($operand1, $operand2);
                    break;
                case '~':
                    $result = mt_rand($operand1, $operand2);
                    break;
            }
        } catch (\Exception $ex) {
            throw new \RuntimeException($ex->getMessage());
        }
        return $result;
    }
}
if (function_exists('getEvaluatedMathExpressionValue') === false) {
    /**
     * Get evaluated math expression value.
     *
     * @param string $mathExpression Math expression parameter.
     * @param array  $passedParams   Passed variables data collection parameter.
     *
     * @throws \RuntimeException If invalid math expression detected.
     * @throws \RuntimeException If invalid math operator given.
     * @throws \RuntimeException If failed to calculate the result.
     * @throws \RuntimeException If invalid given variable parameter detected.
     * @return number
     */
    function getEvaluatedMathExpressionValue($mathExpression, array $passedParams = [])
    {
        # Initialize all required local variables.
        $arrStack = [];
        $stackIndex = -1;
        $arrPostfix = getConvertedInfixToPostfixArray($mathExpression);
        foreach ($arrPostfix as $value) {
            if (isMathOperator($value) === false) {
                $operand = $value;
                if (is_numeric($value) === false) {
                    if (array_key_exists($value, $passedParams) === true) {
                        $operand = $passedParams[$value];
                    } else {
                        throw new \RuntimeException('Invalid given variable parameter detected');
                    }
                }
                $arrStack[++$stackIndex] = $operand;
            } else {
                $operand2 = $arrStack[$stackIndex--];
                $operand1 = $arrStack[$stackIndex--];
                $arrStack[++$stackIndex] = getMathCalcResult($operand1, $operand2, $value);
            }
        }
        return $arrStack[$stackIndex];
    }
}
