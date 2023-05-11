<?php
/**
 * Created by PhpStorm.
 * User: Joey Rivera
 * Date: 7/19/2015
 * Time: 1:04 PM
 */

namespace Hondros\Api\Util\Helper;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

trait StringUtil
{
    /**
     * Takes in a string and looks for special characters using coming from Word to convert
     *
     * @see https://www.browserling.com/tools/utf8-encode
     * @see http://www.fileformat.info/info/unicode/<NUM>/ <NUM> = 2018
     * @see http://www.utf8-chartable.de/unicode-utf8-table.pl
     * @param string $string
     * @return string
     */
    function convertStringToUtf8($string)
    {
        $string = trim($string);

        if (empty($string)) {
            return $string;
        }

        $search = [
            "\xC2\xAB",     // � (U+00AB) in UTF-8
            "\xC2\xBB",     // � (U+00BB) in UTF-8
            "\xE2\x80\x98", // � (U+2018) in UTF-8
            "\xE2\x80\x99", // � (U+2019) in UTF-8
            "\xE2\x80\x9A", // � (U+201A) in UTF-8
            "\xE2\x80\x9B", // ? (U+201B) in UTF-8
            "\xE2\x80\x9C", // � (U+201C) in UTF-8
            "\xE2\x80\x9D", // � (U+201D) in UTF-8
            "\xE2\x80\x9E", // � (U+201E) in UTF-8
            "\xE2\x80\x9F", // ? (U+201F) in UTF-8
            "\xE2\x80\xB9", // � (U+2039) in UTF-8
            "\xE2\x80\xBA", // � (U+203A) in UTF-8
            "\xE2\x80\x93", // � (U+2013) in UTF-8
            "\xE2\x80\x94", // � (U+2014) in UTF-8
            "\xE2\x80\xA6",  // � (U+2026) in UTF-8
            "\xC2\xA0",  // space in UTF-8
            "\xC2\xA7", // �
            "\xC2\xA9", // �
            "\xC2\xAE", // �
            "\xC2\xB0", // �
            "\xc3\xb7", // divide sign
            "\xc2\xbc", // 1/4
            "\xc2\xbd", // 1/2
            "\xc2\xbe", // 3/4
            "\xc3\x90", // fancy D letter
        ];

        $replacements = [
            "<<",
            ">>",
            "'",
            "'",
            "'",
            "'",
            '"',
            '"',
            '"',
            '"',
            "<",
            ">",
            "-",
            "-",
            "...",
            " ",
            "&sect;",
            "&copy;",
            "&reg;",
            "&deg;",
            "&divide;",
            "&frac14;",
            "&frac12;",
            "&frac34;",
            "&#xD0;"
        ];

        return utf8_encode(str_replace($search, $replacements, $string));
    }

    /**
     * Check all identified invalid MySQL characters within string
     *
     * @param $string
     * @return bool
     */
    function containInvalidMySQLChar($string)
    {
        $invalid = [
            'Ã',
            'Â'
        ];

        foreach ($invalid as $char) {
            if (strpos($string, $char) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * When importing content from excel, we need to check for a few things.
     * Some cells are being converted from true/false string into boolean values
     *
     * @param Cell $cell
     * @return string
     */
    function getValueFromExcelCell(Cell $cell)
    {
        // check for boolean first
        if ($cell->getDataType() == 'b') {
            return $cell->getValue() ? 'TRUE' : 'FALSE';
        }

        return $this->convertStringToUtf8(trim($cell->getValue()));
    }

    /**
     * Clean up data as it's not always consistent
     *
     * answer string should usually only be one character and an a-j
     *
     * @param $answerString string
     * @return int
     */
    function getExcelAnswerChoiceIndex($answerString)
    {
        $letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
        $letter = strtolower(filter_var(substr(trim($answerString), 0, 1)));

        // make sure there is a match
        return array_search($letter, $letters);
    }

    /**
     * For a row, extract all the variables we need for the different pieces of information. Works for multi sheet
     * and vocab sheet
     *
     * @param Worksheet $sheet
     * @param $row
     * @return array
     * @throws SpreadsheetException
     */
    function excelRowToVariables(Worksheet $sheet, $row)
    {
        $answerColumns = 0;
        $letter = 'B';

        for ($x = 0; $x < 10; $x++) {
            $label = $this->getValueFromExcelCell($sheet->getCell("{$letter}1"));

            if (strtolower(substr($label, 0, 6)) !== 'answer'
                && strtolower(substr($label, 0, 10)) !== 'definition') {
                break;
            }

            $answerColumns++;
            $letter = chr(ord($letter) + 1);
        }

        $question = $this->getValueFromExcelCell($sheet->getCell("A{$row}"));
        $answers = [];

        $columnLetter = 'B';
        for ($y = 0; $y < $answerColumns; $y++) {
            $value = $this->getValueFromExcelCell($sheet->getCell("{$columnLetter}{$row}"));

            if (!is_null($value) && $value !== '') {
                $answers[$y] = $value;
            }

            $columnLetter = $this->incrementLetter($columnLetter);
        }

        $feedbackColumn = $columnLetter;
        $feedback = $this->getValueFromExcelCell($sheet->getCell("{$feedbackColumn}{$row}"));

        $correctAnswerColumn = chr(ord($columnLetter) + 1);
        $correctAnswer = $this->getValueFromExcelCell($sheet->getCell("{$correctAnswerColumn}{$row}"));

        return [$question, $answers, $feedback, $correctAnswer, $answerColumns];
    }

    /**
     * @param string $letter
     * @param int $times
     * @return string
     */
    function incrementLetter($letter, $times = 1)
    {
        return chr(ord($letter) + $times);
    }

    /**
     * @param string $letter
     * @param int $times
     * @return string
     */
    function decrementLetter($letter, $times = 1)
    {
        return chr(ord($letter) - $times);
    }

    /**
     * @param int $size
     * @param int $precision
     * @return string
     */
    function formatBytes($size, $precision = 2) {
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}

// @todo add test where the correct answer is a letter that has no answer