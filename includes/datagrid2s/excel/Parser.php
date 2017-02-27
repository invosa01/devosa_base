<?php
/**
 *  Class for parsing Excel formulas
 *
 *  License Information:
 *
 *    Spreadsheet::WriteExcel:  A library for generating Excel Spreadsheets
 *    Copyright (C) 2002 Xavier Noguer xnoguer@rezebra.com
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 2.1 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library; if not, write to the Free Software
 *    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * @const ADD token identifier for character "+"
 */
define('ADD', "+");
/**
 * @const SUB token identifier for character "-"
 */
define('SUB', "-");
/**
 * @const EQUAL token identifier for character "="
 */
define('EQUAL', "=");
/**
 * @const MUL token identifier for character "*"
 */
define('MUL', "*");
/**
 * @const DIV token identifier for character "/"
 */
define('DIV', "/");
/**
 * @const OPEN token identifier for character "("
 */
define('OPEN', "(");
/**
 * @const CLOSE token identifier for character ")"
 */
define('CLOSE', ")");
/**
 * @const COMA token identifier for character ","
 */
define('COMA', ",");

/**
 * Class for parsing Excel formulas
 *
 * @author  Xavier Noguer <xnoguer@rezebra.com>
 * @package Spreadsheet_WriteExcel
 */
class Parser
{

    /**
     * The class constructor
     *
     * @param integer $byte_order The byte order (Little endian or Big endian) of the architecture
     *                            (optional). 1 => big endian, 0 (default) => little endian.
     */
    function Parser($byte_order = 0)
    {
        $this->_current_char = 0;        // The index of the character we are currently looking at.
        $this->_current_token = '';       // The token we are working on.
        $this->_formula = "";       // The formula to parse.
        $this->_lookahead = '';       // The character ahead of the current char.
        $this->_parse_tree = '';       // The parse tree to be generated.
        $this->_initialize_hashes();      // Initialize the hashes: ptg's and function's ptg's
        $this->_byte_order = $byte_order; // Little Endian or Big Endian
        $this->_func_args = 0;           // Number of arguments for the current function
        $this->_volatile = 0;
    }

    /**
     * Advance to the next valid token.
     */
    function _advance()
    {
        $i = $this->_current_char;
        // eat up white spaces
        if ($i < strlen($this->_formula)) {
            while ($this->_formula{$i} == " ") {
                $i++;
            }
            if ($i < strlen($this->_formula) - 1) {
                $this->_lookahead = $this->_formula{$i + 1};
            }
            $token = "";
        }
        while ($i < strlen($this->_formula)) {
            $token .= $this->_formula{$i};
            if ($this->_match($token) != '') {
                if ($i < strlen($this->_formula) - 1) {
                    $this->_lookahead = $this->_formula{$i + 1};
                }
                $this->_current_char = $i + 1;
                $this->_current_token = $token;
                return (1);
            }
            $this->_lookahead = $this->_formula{$i + 2};
            $i++;
        }
        //die("Lexical error ".$this->_current_char);
    }

    /**
     * pack() row and column into the required 3 byte format.
     *
     * @param string $cell The Excel cell reference to be packed
     */
    function _cell_to_packed_rowcol($cell)
    {
        list($row, $col, $row_rel, $col_rel) = $this->_cell_to_rowcol($cell);
        if ($col >= 256) {
            die("Column in: $cell greater than 255 ");
        }
        if ($row >= 16384) {
            die("Row in: $cell greater than 16384 ");
        }
        // Set the high bits to indicate if row or col are relative.
        $row |= $col_rel << 14;
        $row |= $row_rel << 15;
        $row = pack('v', $row);
        $col = pack('C', $col);
        return ([$row, $col]);
    }

    /**
     * Convert an Excel cell reference such as A1 or $B2 or C$3 or $D$4 to a zero
     * indexed row and column number. Also returns two boolean values to indicate
     * whether the row or column are relative references.
     *
     * @param string $cell The Excel cell reference in A1 format.
     */
    function _cell_to_rowcol($cell)
    {
        preg_match('/(\$)?([A-I]?[A-Z])(\$)?(\d+)/', $cell, $match);
        // return absolute column if there is a $ in the ref
        $col_rel = empty($match[1]) ? 1 : 0;
        $col_ref = $match[2];
        $row_rel = empty($match[3]) ? 1 : 0;
        $row = $match[4];
        // Convert base26 column string to a number.
        $expn = strlen($col_ref) - 1;
        $col = 0;
        for ($i = 0; $i < strlen($col_ref); $i++) {
            $col += (ord($col_ref{$i}) - ord('A') + 1) * pow(26, $expn);
            $expn--;
        }
        // Convert 1-index to zero-index
        $row--;
        $col--;
        return ([$row, $col, $row_rel, $col_rel]);
    }

    /**
     * Convert a token to the proper ptg value.
     *
     * @param mixed $token The token to convert.
     */
    function _convert($token)
    {
        if (is_numeric($token)) {
            return ($this->_convert_number($token));
        } // match references like A1
        elseif (preg_match("/^([A-I]?[A-Z])(\d+)$/", $token)) {
            return ($this->_convert_ref2d($token));
        } // match ranges like A1:B2
        elseif (preg_match("/^([A-I]?[A-Z])(\d+)\:([A-I]?[A-Z])(\d+)$/", $token)) {
            return ($this->_convert_range2d($token));
        } // match ranges like A1..B2
        elseif (preg_match("/^([A-I]?[A-Z])(\d+)\.\.([A-I]?[A-Z])(\d+)$/", $token)) {
            return ($this->_convert_range2d($token));
        } elseif (isset($this->ptg[$token])) // operators (including parentheses)
        {
            return (pack("C", $this->ptg[$token]));
        } elseif (preg_match("/[A-Z0-9�-�\.]+/", $token)) {
            return ($this->_convert_function($token, $this->_func_args));
        } // if it's an argument, ignore the token (the argument remains)
        elseif ($token == 'arg') {
            $this->_func_args++;
            return ('');
        }
        die("Unknown token $token");
    }

    /**
     * Convert a function to a ptgFunc or ptgFuncVarV depending on the number of
     * args that it takes.
     *
     * @param string  $token    The name of the function for convertion to ptg value.
     * @param integer $num_args The number of arguments the function recieves.
     */
    function _convert_function($token, $num_args)
    {
        $this->_func_args = 0; // re initialize the number of arguments
        $args = $this->_functions[$token][1];
        $volatile = $this->_functions[$token][3];
        if ($volatile) {
            $this->_volatile = 1;
        }
        // Fixed number of args eg. TIME($i,$j,$k).
        if ($args >= 0) {
            return (pack("Cv", $this->ptg['ptgFuncV'], $this->_functions[$token][0]));
        }
        // Variable number of args eg. SUM($i,$j,$k, ..).
        if ($args == -1) {
            return (pack("CCv", $this->ptg['ptgFuncVarV'], $num_args, $this->_functions[$token][0]));
        }
    }

    /**
     * Convert a number token to ptgInt or ptgNum
     *
     * @param mixed $num an integer or double for conersion to its ptg value
     */
    function _convert_number($num)
    {
        // Integer in the range 0..2**16-1
        if ((preg_match("/^\d+$/", $num)) and ($num <= 65535)) {
            return pack("Cv", $this->ptg['ptgInt'], $num);
        } else // A float
        {
            if ($this->_byte_order) // if it's Big Endian
            {
                $num = strrev($num);
            }
            return pack("Cd", $this->ptg['ptgNum'], $num);
        }
    }

    /**
     * Convert an Excel range such as A1:D4 to a ptgRefV.
     *
     * @param string $range An Excel range in the A1:A2 or A1..A2 format.
     */
    function _convert_range2d($range)
    {
        $class = 2; // as far as I know, this is magick.
        // Split the range into 2 cell refs
        if (preg_match("/^([A-I]?[A-Z])(\d+)\:([A-I]?[A-Z])(\d+)$/", $range)) {
            list($cell1, $cell2) = split(':', $range);
        } elseif (preg_match("/^([A-I]?[A-Z])(\d+)\.\.([A-I]?[A-Z])(\d+)$/", $range)) {
            list($cell1, $cell2) = split('\.\.', $range);
        } else {
            die("Unknown range separator");
        }
        // Convert the cell references
        list($row1, $col1) = $this->_cell_to_packed_rowcol($cell1);
        list($row2, $col2) = $this->_cell_to_packed_rowcol($cell2);
        // The ptg value depends on the class of the ptg.
        if ($class == 0) {
            $ptgArea = pack("C", $this->ptg['ptgArea']);
        } elseif ($class == 1) {
            $ptgArea = pack("C", $this->ptg['ptgAreaV']);
        } elseif ($class == 2) {
            $ptgArea = pack("C", $this->ptg['ptgAreaA']);
        } else {
            die("Unknown class ");
        }
        return ($ptgArea . $row1 . $row2 . $col1 . $col2);
    }

    /**
     * Convert an Excel reference such as A1, $B2, C$3 or $D$4 to a ptgRefV.
     *
     * @param string $cell An Excel cell reference
     */
    function _convert_ref2d($cell)
    {
        $class = 2; // as far as I know, this is magick.
        // Convert the cell reference
        list($row, $col) = $this->_cell_to_packed_rowcol($cell);
        // The ptg value depends on the class of the ptg.
        if ($class == 0) {
            $ptgRef = pack("C", $this->ptg['ptgRef']);
        } elseif ($class == 1) {
            $ptgRef = pack("C", $this->ptg['ptgRefV']);
        } elseif ($class == 2) {
            $ptgRef = pack("C", $this->ptg['ptgRefA']);
        } else {
            die("Unknown class ");
        }
        return $ptgRef . $row . $col;
    }

    /**
     * Creates a tree. In fact an array which may have one or two arrays (sub-trees)
     * as elements.
     *
     * @param mixed $value The value of this node.
     * @param mixed $left  The left array (sub-tree) or a final node.
     * @param mixed $right The right array (sub-tree) or a final node.
     */
    function _create_tree($value, $left, $right)
    {
        return ['value' => $value, 'left' => $left, 'right' => $right];
    }

    /**
     * It parses a expression. It assumes the following rule:
     * Expr -> Term [("+" | "-") Term]
     *
     * @return mixed The parsed ptg'd tree
     */
    function _expression()
    {
        $result = $this->_term();
        while ($this->_current_token == ADD or $this->_current_token == SUB) {
            if ($this->_current_token == ADD) {
                $this->_advance();
                $result = $this->_create_tree('ptgAdd', $result, $this->_term());
            } else {
                $this->_advance();
                $result = $this->_create_tree('ptgSub', $result, $this->_term());
            }
        }
        return $result;
    }

    /**
     * It parses a factor. It assumes the following rule:
     * Fact -> ( Expr )
     *       | CellRef
     *       | CellRange
     *       | Number
     *       | Function
     *
     * @return mixed The parsed ptg'd tree
     */
    function _fact()
    {
        if ($this->_current_token == OPEN) {
            $this->_advance();         // eat the "("
            $result = $this->_parenthesized_expression();//$this->_expression();
            if ($this->_current_token != CLOSE) {
                die("')' token expected.");
            }
            $this->_advance();         // eat the ")"
            return ($result);
        }
        // if it's a reference
        if (eregi("^[A-I]?[A-Z][0-9]+$", $this->_current_token)) {
            $result = $this->_create_tree($this->_current_token, '', '');
            $this->_advance();
            return ($result);
        } // if it's a range
        elseif (eregi("^[A-I]?[A-Z][0-9]+:[A-I]?[A-Z][0-9]+$", $this->_current_token) or
            eregi("^[A-I]?[A-Z][0-9]+\.\.[A-I]?[A-Z][0-9]+$", $this->_current_token)
        ) {
            $result = $this->_current_token;
            $this->_advance();
            return ($result);
        } elseif (is_numeric($this->_current_token)) {
            $result = $this->_create_tree($this->_current_token, '', '');
            $this->_advance();
            return ($result);
        } // if it's a function call
        elseif (eregi("^[A-Z0-9�-�\.]+$", $this->_current_token)) {
            $result = $this->_func();
            return ($result);
        }
        die("Sintactic error: " . $this->_current_token . ", lookahead: " .
            $this->_lookahead . ", current char: " . $this->_current_char);
    }

    /**
     * It parses a function call. It assumes the following rule:
     * Func -> ( Expr [,Expr]* )
     *
     */
    function _func()
    {
        $num_args = 0; // number of arguments received
        $function = $this->_current_token;
        $this->_advance();
        $this->_advance();         // eat the "("
        while ($this->_current_token != ')') {
            if ($num_args > 0) {
                if ($this->_current_token == COMA) {
                    $this->_advance();  // eat the ","
                } else {
                    die("Sintactic error: coma expected $num_args");
                }
                $result = $this->_create_tree('arg', $result, $this->_expression());
            } else {
                $result = $this->_create_tree('arg', '', $this->_expression());
            }
            $num_args++;
        }
        $args = $this->_functions[$function][1];
        // If fixed number of args eg. TIME($i,$j,$k). Check that the number of args is valid.
        if (($args >= 0) and ($args != $num_args)) {
            die("Incorrect number of arguments in function $function() ");
        }
        $result = $this->_create_tree($function, $result, '');
        $this->_advance();         // eat the ")"
        return ($result);
    }

    /**
     * Initialize the ptg and function hashes.
     */
    function _initialize_hashes()
    {
        // The Excel ptg indices
        $this->ptg = [
            'ptgExp'       => 0x01,
            'ptgTbl'       => 0x02,
            'ptgAdd'       => 0x03,
            'ptgSub'       => 0x04,
            'ptgMul'       => 0x05,
            'ptgDiv'       => 0x06,
            'ptgPower'     => 0x07,
            'ptgConcat'    => 0x08,
            'ptgLT'        => 0x09,
            'ptgLE'        => 0x0A,
            'ptgEQ'        => 0x0B,
            'ptgGE'        => 0x0C,
            'ptgGT'        => 0x0D,
            'ptgNE'        => 0x0E,
            'ptgIsect'     => 0x0F,
            'ptgUnion'     => 0x10,
            'ptgRange'     => 0x11,
            'ptgUplus'     => 0x12,
            'ptgUminus'    => 0x13,
            'ptgPercent'   => 0x14,
            'ptgParen'     => 0x15,
            'ptgMissArg'   => 0x16,
            'ptgStr'       => 0x17,
            'ptgAttr'      => 0x19,
            'ptgSheet'     => 0x1A,
            'ptgEndSheet'  => 0x1B,
            'ptgErr'       => 0x1C,
            'ptgBool'      => 0x1D,
            'ptgInt'       => 0x1E,
            'ptgNum'       => 0x1F,
            'ptgArray'     => 0x20,
            'ptgFunc'      => 0x21,
            'ptgFuncVar'   => 0x22,
            'ptgName'      => 0x23,
            'ptgRef'       => 0x24,
            'ptgArea'      => 0x25,
            'ptgMemArea'   => 0x26,
            'ptgMemErr'    => 0x27,
            'ptgMemNoMem'  => 0x28,
            'ptgMemFunc'   => 0x29,
            'ptgRefErr'    => 0x2A,
            'ptgAreaErr'   => 0x2B,
            'ptgRefN'      => 0x2C,
            'ptgAreaN'     => 0x2D,
            'ptgMemAreaN'  => 0x2E,
            'ptgMemNoMemN' => 0x2F,
            'ptgNameX'     => 0x39,
            'ptgRef3d'     => 0x3A,
            'ptgArea3d'    => 0x3B,
            'ptgRefErr3d'  => 0x3C,
            'ptgAreaErr3d' => 0x3D,
            'ptgArrayV'    => 0x40,
            'ptgFuncV'     => 0x41,
            'ptgFuncVarV'  => 0x42,
            'ptgNameV'     => 0x43,
            'ptgRefV'      => 0x44,
            'ptgAreaV'     => 0x45,
            'ptgMemAreaV'  => 0x46,
            'ptgMemErrV'   => 0x47,
            'ptgMemNoMemV' => 0x48,
            'ptgMemFuncV'  => 0x49,
            'ptgRefErrV'   => 0x4A,
            'ptgAreaErrV'  => 0x4B,
            'ptgRefNV'     => 0x4C,
            'ptgAreaNV'    => 0x4D,
            'ptgMemAreaNV' => 0x4E,
            'ptgMemNoMemN' => 0x4F,
            'ptgFuncCEV'   => 0x58,
            'ptgNameXV'    => 0x59,
            'ptgRef3dV'    => 0x5A,
            'ptgArea3dV'   => 0x5B,
            'ptgRefErr3dV' => 0x5C,
            'ptgAreaErr3d' => 0x5D,
            'ptgArrayA'    => 0x60,
            'ptgFuncA'     => 0x61,
            'ptgFuncVarA'  => 0x62,
            'ptgNameA'     => 0x63,
            'ptgRefA'      => 0x64,
            'ptgAreaA'     => 0x65,
            'ptgMemAreaA'  => 0x66,
            'ptgMemErrA'   => 0x67,
            'ptgMemNoMemA' => 0x68,
            'ptgMemFuncA'  => 0x69,
            'ptgRefErrA'   => 0x6A,
            'ptgAreaErrA'  => 0x6B,
            'ptgRefNA'     => 0x6C,
            'ptgAreaNA'    => 0x6D,
            'ptgMemAreaNA' => 0x6E,
            'ptgMemNoMemN' => 0x6F,
            'ptgFuncCEA'   => 0x78,
            'ptgNameXA'    => 0x79,
            'ptgRef3dA'    => 0x7A,
            'ptgArea3dA'   => 0x7B,
            'ptgRefErr3dA' => 0x7C,
            'ptgAreaErr3d' => 0x7D
        ];
        // Thanks to Michael Meeks and Gnumeric for the initial arg values.
        //
        // The following hash was generated by "function_locale.pl" in the distro.
        // Refer to function_locale.pl for non-English function names.
        //
        // The array elements are as follow:
        // ptg:   The Excel function ptg code.
        // args:  The number of arguments that the function takes:
        //           >=0 is a fixed number of arguments.
        //           -1  is a variable  number of arguments.
        // class: The reference, value or array class of the function args.
        // vol:   The function is volatile.
        //
        $this->_functions = [
            // function                  ptg  args  class  vol
            'COUNT'        => [0, -1, 0, 0],
            'IF'           => [1, -1, 1, 0],
            'ISNA'         => [2, 1, 1, 0],
            'ISERROR'      => [3, 1, 1, 0],
            'SUM'          => [4, -1, 0, 0],
            'AVERAGE'      => [5, -1, 0, 0],
            'MIN'          => [6, -1, 0, 0],
            'MAX'          => [7, -1, 0, 0],
            'ROW'          => [8, -1, 0, 0],
            'COLUMN'       => [9, -1, 0, 0],
            'NA'           => [10, 0, 0, 0],
            'NPV'          => [11, -1, 1, 0],
            'STDEV'        => [12, -1, 0, 0],
            'DOLLAR'       => [13, -1, 1, 0],
            'FIXED'        => [14, -1, 1, 0],
            'SIN'          => [15, 1, 1, 0],
            'COS'          => [16, 1, 1, 0],
            'TAN'          => [17, 1, 1, 0],
            'ATAN'         => [18, 1, 1, 0],
            'PI'           => [19, 0, 1, 0],
            'SQRT'         => [20, 1, 1, 0],
            'EXP'          => [21, 1, 1, 0],
            'LN'           => [22, 1, 1, 0],
            'LOG10'        => [23, 1, 1, 0],
            'ABS'          => [24, 1, 1, 0],
            'INT'          => [25, 1, 1, 0],
            'SIGN'         => [26, 1, 1, 0],
            'ROUND'        => [27, 2, 1, 0],
            'LOOKUP'       => [28, -1, 0, 0],
            'INDEX'        => [29, -1, 0, 1],
            'REPT'         => [30, 2, 1, 0],
            'MID'          => [31, 3, 1, 0],
            'LEN'          => [32, 1, 1, 0],
            'VALUE'        => [33, 1, 1, 0],
            'TRUE'         => [34, 0, 1, 0],
            'FALSE'        => [35, 0, 1, 0],
            'AND'          => [36, -1, 0, 0],
            'OR'           => [37, -1, 0, 0],
            'NOT'          => [38, 1, 1, 0],
            'MOD'          => [39, 2, 1, 0],
            'DCOUNT'       => [40, 3, 0, 0],
            'DSUM'         => [41, 3, 0, 0],
            'DAVERAGE'     => [42, 3, 0, 0],
            'DMIN'         => [43, 3, 0, 0],
            'DMAX'         => [44, 3, 0, 0],
            'DSTDEV'       => [45, 3, 0, 0],
            'VAR'          => [46, -1, 0, 0],
            'DVAR'         => [47, 3, 0, 0],
            'TEXT'         => [48, 2, 1, 0],
            'LINEST'       => [49, -1, 0, 0],
            'TREND'        => [50, -1, 0, 0],
            'LOGEST'       => [51, -1, 0, 0],
            'GROWTH'       => [52, -1, 0, 0],
            'PV'           => [56, -1, 1, 0],
            'FV'           => [57, -1, 1, 0],
            'NPER'         => [58, -1, 1, 0],
            'PMT'          => [59, -1, 1, 0],
            'RATE'         => [60, -1, 1, 0],
            'MIRR'         => [61, 3, 0, 0],
            'IRR'          => [62, -1, 0, 0],
            'RAND'         => [63, 0, 1, 1],
            'MATCH'        => [64, -1, 0, 0],
            'DATE'         => [65, 3, 1, 0],
            'TIME'         => [66, 3, 1, 0],
            'DAY'          => [67, 1, 1, 0],
            'MONTH'        => [68, 1, 1, 0],
            'YEAR'         => [69, 1, 1, 0],
            'WEEKDAY'      => [70, -1, 1, 0],
            'HOUR'         => [71, 1, 1, 0],
            'MINUTE'       => [72, 1, 1, 0],
            'SECOND'       => [73, 1, 1, 0],
            'NOW'          => [74, 0, 1, 1],
            'AREAS'        => [75, 1, 0, 1],
            'ROWS'         => [76, 1, 0, 1],
            'COLUMNS'      => [77, 1, 0, 1],
            'OFFSET'       => [78, -1, 0, 1],
            'SEARCH'       => [82, -1, 1, 0],
            'TRANSPOSE'    => [83, 1, 1, 0],
            'TYPE'         => [86, 1, 1, 0],
            'ATAN2'        => [97, 2, 1, 0],
            'ASIN'         => [98, 1, 1, 0],
            'ACOS'         => [99, 1, 1, 0],
            'CHOOSE'       => [100, -1, 1, 0],
            'HLOOKUP'      => [101, -1, 0, 0],
            'VLOOKUP'      => [102, -1, 0, 0],
            'ISREF'        => [105, 1, 0, 0],
            'LOG'          => [109, -1, 1, 0],
            'CHAR'         => [111, 1, 1, 0],
            'LOWER'        => [112, 1, 1, 0],
            'UPPER'        => [113, 1, 1, 0],
            'PROPER'       => [114, 1, 1, 0],
            'LEFT'         => [115, -1, 1, 0],
            'RIGHT'        => [116, -1, 1, 0],
            'EXACT'        => [117, 2, 1, 0],
            'TRIM'         => [118, 1, 1, 0],
            'REPLACE'      => [119, 4, 1, 0],
            'SUBSTITUTE'   => [120, -1, 1, 0],
            'CODE'         => [121, 1, 1, 0],
            'FIND'         => [124, -1, 1, 0],
            'CELL'         => [125, -1, 0, 1],
            'ISERR'        => [126, 1, 1, 0],
            'ISTEXT'       => [127, 1, 1, 0],
            'ISNUMBER'     => [128, 1, 1, 0],
            'ISBLANK'      => [129, 1, 1, 0],
            'T'            => [130, 1, 0, 0],
            'N'            => [131, 1, 0, 0],
            'DATEVALUE'    => [140, 1, 1, 0],
            'TIMEVALUE'    => [141, 1, 1, 0],
            'SLN'          => [142, 3, 1, 0],
            'SYD'          => [143, 4, 1, 0],
            'DDB'          => [144, -1, 1, 0],
            'INDIRECT'     => [148, -1, 1, 1],
            'CALL'         => [150, -1, 1, 0],
            'CLEAN'        => [162, 1, 1, 0],
            'MDETERM'      => [163, 1, 2, 0],
            'MINVERSE'     => [164, 1, 2, 0],
            'MMULT'        => [165, 2, 2, 0],
            'IPMT'         => [167, -1, 1, 0],
            'PPMT'         => [168, -1, 1, 0],
            'COUNTA'       => [169, -1, 0, 0],
            'PRODUCT'      => [183, -1, 0, 0],
            'FACT'         => [184, 1, 1, 0],
            'DPRODUCT'     => [189, 3, 0, 0],
            'ISNONTEXT'    => [190, 1, 1, 0],
            'STDEVP'       => [193, -1, 0, 0],
            'VARP'         => [194, -1, 0, 0],
            'DSTDEVP'      => [195, 3, 0, 0],
            'DVARP'        => [196, 3, 0, 0],
            'TRUNC'        => [197, -1, 1, 0],
            'ISLOGICAL'    => [198, 1, 1, 0],
            'DCOUNTA'      => [199, 3, 0, 0],
            'ROUNDUP'      => [212, 2, 1, 0],
            'ROUNDDOWN'    => [213, 2, 1, 0],
            'RANK'         => [216, -1, 0, 0],
            'ADDRESS'      => [219, -1, 1, 0],
            'DAYS360'      => [220, -1, 1, 0],
            'TODAY'        => [221, 0, 1, 1],
            'VDB'          => [222, -1, 1, 0],
            'MEDIAN'       => [227, -1, 0, 0],
            'SUMPRODUCT'   => [228, -1, 2, 0],
            'SINH'         => [229, 1, 1, 0],
            'COSH'         => [230, 1, 1, 0],
            'TANH'         => [231, 1, 1, 0],
            'ASINH'        => [232, 1, 1, 0],
            'ACOSH'        => [233, 1, 1, 0],
            'ATANH'        => [234, 1, 1, 0],
            'DGET'         => [235, 3, 0, 0],
            'INFO'         => [244, 1, 1, 1],
            'DB'           => [247, -1, 1, 0],
            'FREQUENCY'    => [252, 2, 0, 0],
            'ERROR.TYPE'   => [261, 1, 1, 0],
            'REGISTER.ID'  => [267, -1, 1, 0],
            'AVEDEV'       => [269, -1, 0, 0],
            'BETADIST'     => [270, -1, 1, 0],
            'GAMMALN'      => [271, 1, 1, 0],
            'BETAINV'      => [272, -1, 1, 0],
            'BINOMDIST'    => [273, 4, 1, 0],
            'CHIDIST'      => [274, 2, 1, 0],
            'CHIINV'       => [275, 2, 1, 0],
            'COMBIN'       => [276, 2, 1, 0],
            'CONFIDENCE'   => [277, 3, 1, 0],
            'CRITBINOM'    => [278, 3, 1, 0],
            'EVEN'         => [279, 1, 1, 0],
            'EXPONDIST'    => [280, 3, 1, 0],
            'FDIST'        => [281, 3, 1, 0],
            'FINV'         => [282, 3, 1, 0],
            'FISHER'       => [283, 1, 1, 0],
            'FISHERINV'    => [284, 1, 1, 0],
            'FLOOR'        => [285, 2, 1, 0],
            'GAMMADIST'    => [286, 4, 1, 0],
            'GAMMAINV'     => [287, 3, 1, 0],
            'CEILING'      => [288, 2, 1, 0],
            'HYPGEOMDIST'  => [289, 4, 1, 0],
            'LOGNORMDIST'  => [290, 3, 1, 0],
            'LOGINV'       => [291, 3, 1, 0],
            'NEGBINOMDIST' => [292, 3, 1, 0],
            'NORMDIST'     => [293, 4, 1, 0],
            'NORMSDIST'    => [294, 1, 1, 0],
            'NORMINV'      => [295, 3, 1, 0],
            'NORMSINV'     => [296, 1, 1, 0],
            'STANDARDIZE'  => [297, 3, 1, 0],
            'ODD'          => [298, 1, 1, 0],
            'PERMUT'       => [299, 2, 1, 0],
            'POISSON'      => [300, 3, 1, 0],
            'TDIST'        => [301, 3, 1, 0],
            'WEIBULL'      => [302, 4, 1, 0],
            'SUMXMY2'      => [303, 2, 2, 0],
            'SUMX2MY2'     => [304, 2, 2, 0],
            'SUMX2PY2'     => [305, 2, 2, 0],
            'CHITEST'      => [306, 2, 2, 0],
            'CORREL'       => [307, 2, 2, 0],
            'COVAR'        => [308, 2, 2, 0],
            'FORECAST'     => [309, 3, 2, 0],
            'FTEST'        => [310, 2, 2, 0],
            'INTERCEPT'    => [311, 2, 2, 0],
            'PEARSON'      => [312, 2, 2, 0],
            'RSQ'          => [313, 2, 2, 0],
            'STEYX'        => [314, 2, 2, 0],
            'SLOPE'        => [315, 2, 2, 0],
            'TTEST'        => [316, 4, 2, 0],
            'PROB'         => [317, -1, 2, 0],
            'DEVSQ'        => [318, -1, 0, 0],
            'GEOMEAN'      => [319, -1, 0, 0],
            'HARMEAN'      => [320, -1, 0, 0],
            'SUMSQ'        => [321, -1, 0, 0],
            'KURT'         => [322, -1, 0, 0],
            'SKEW'         => [323, -1, 0, 0],
            'ZTEST'        => [324, -1, 0, 0],
            'LARGE'        => [325, 2, 0, 0],
            'SMALL'        => [326, 2, 0, 0],
            'QUARTILE'     => [327, 2, 0, 0],
            'PERCENTILE'   => [328, 2, 0, 0],
            'PERCENTRANK'  => [329, -1, 0, 0],
            'MODE'         => [330, -1, 2, 0],
            'TRIMMEAN'     => [331, 2, 0, 0],
            'TINV'         => [332, 2, 1, 0],
            'CONCATENATE'  => [336, -1, 1, 0],
            'POWER'        => [337, 2, 1, 0],
            'RADIANS'      => [342, 1, 1, 0],
            'DEGREES'      => [343, 1, 1, 0],
            'SUBTOTAL'     => [344, -1, 0, 0],
            'SUMIF'        => [345, -1, 0, 0],
            'COUNTIF'      => [346, 2, 0, 0],
            'COUNTBLANK'   => [347, 1, 0, 0],
            'ROMAN'        => [354, -1, 1, 0]
        ];
    }

    /**
     * Checks if it's a valid token.
     *
     * @param mixed $token The token to check.
     */
    function _match($token)
    {
        switch ($token) {
            case ADD:
                return ($token);
                break;
            case SUB:
                return ($token);
                break;
            case MUL:
                return ($token);
                break;
            case DIV:
                return ($token);
                break;
            case OPEN:
                return ($token);
                break;
            case CLOSE:
                return ($token);
                break;
            case COMA:
                return ($token);
                break;
            default:
                // if it's a reference
                if (eregi("^[A-I]?[A-Z][0-9]+$", $token) and
                    !ereg("[0-9]", $this->_lookahead) and
                    ($this->_lookahead != ':') and ($this->_lookahead != '.')
                ) {
                    return ($token);
                } // if it's a range (A1:A2)
                elseif (eregi("^[A-I]?[A-Z][0-9]+:[A-I]?[A-Z][0-9]+$", $token) and
                    !ereg("[0-9]", $this->_lookahead)
                ) {
                    return ($token);
                } // if it's a range (A1..A2)
                elseif (eregi("^[A-I]?[A-Z][0-9]+\.\.[A-I]?[A-Z][0-9]+$", $token) and
                    !ereg("[0-9]", $this->_lookahead)
                ) {
                    return ($token);
                } elseif (is_numeric($token) and !is_numeric($token . $this->_lookahead)) {
                    return ($token);
                } // if it's a function call
                elseif (eregi("^[A-Z0-9�-�\.]+$", $token) and ($this->_lookahead == "(")) {
                    return ($token);
                }
                return '';
        }
    }

    /**
     * This function just introduces a ptgParen element in the tree, so that Excel
     * doesn't get confused when working with a parenthesized formula afterwards.
     *
     * @see _fact
     * @return mixed The parsed ptg'd tree
     */
    function _parenthesized_expression()
    {
        $result = $this->_create_tree('ptgParen', $this->_expression(), '');
        return ($result);
    }

    /**
     * It parses a term. It assumes the following rule:
     * Term -> Fact [("*" | "/") Fact]
     *
     * @return mixed The parsed ptg'd tree
     */
    function _term()
    {
        $result = $this->_fact();
        while ($this->_current_token == MUL || $this->_current_token == DIV) {
            if ($this->_current_token == MUL) {
                $this->_advance();
                $result = $this->_create_tree('ptgMul', $result, $this->_fact());
            } else {
                $this->_advance();
                $result = $this->_create_tree('ptgDiv', $result, $this->_fact());
            }
        }
        return ($result);
    }

    /**
     * The parsing method. It parses a formula.
     *
     * @access public
     *
     * @param string $formula The formula to parse, without the initial equal sign (=).
     */
    function parse($formula)
    {
        $this->_current_char = 0;
        $this->_formula = $formula;
        $this->_lookahead = $formula{1};
        $this->_advance();
        $this->_parse_tree = $this->_expression();
    }

    /**
     * Builds a string containing the tree in reverse polish notation (What you
     * would use in a HP calculator stack).
     * The following tree:
     *
     *    +
     *   / \
     *  2   3
     *
     * produces: "23+"
     *
     * The following tree:
     *
     *    +
     *   / \
     *  3   *
     *     / \
     *    6   A1
     *
     * produces: "36A1*+"
     *
     * In fact all operands, functions, references, etc... are written as ptg's
     *
     * @access public
     *
     * @param array $tree The optional tree to convert.
     */
    function to_reverse_polish($tree = [])
    {
        $polish = ""; // the string we are going to return
        if (empty($tree)) // If it's the first call use _parse_tree
        {
            $tree = $this->_parse_tree;
        }
        if (is_array($tree['left'])) {
            $polish .= $this->to_reverse_polish($tree['left']);
        } elseif ($tree['left'] != '') // It's a final node
        {
            $polish .= $this->_convert($tree['left']); //$tree['left'];
        }
        if (is_array($tree['right'])) {
            $polish .= $this->to_reverse_polish($tree['right']);
        } elseif ($tree['right'] != '') // It's a final node
        {
            $polish .= $this->_convert($tree['right']);
        }
        $polish .= $this->_convert($tree['value']);
        return $polish;
    }
}

?>