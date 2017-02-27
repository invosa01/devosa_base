<?php

/*
   Invosa's class date
   version 1.0
   PT. Invosa Systems
   All right reserved.
*/

class clsDate
{

    var $intDay;

    var $intHour;

    var $intMinute;

    var $intMonth;

    var $intSecond;

    var $intTimeStamp;

    var $intYear;

    // Class constructor

    function clsDate($year = 0, $month = 0, $day = 0, $hour = 0, $minute = 0, $second = 0)
    {
        if ($year == 0 && $month == 0 && $day == 0) {
            $this->getCurrentDate();
        } else {
            $this->intYear = $year;
            $this->intMonth = $month;
            $this->intDay = $day;
            $this->intHour = $hour;
            $this->intMinute = $minute;
            $this->intSecond = $second;
            $this->intTimeStamp = mktime($hour, $minute, $second, $month, $day, $year);
        }
    }

    function Day()
    {
        return $this->intDay;
    }

    function DayofWeek()
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
        return intval(date("w", $this->intTimeStamp));
    }

    function DayofWeekName($isShortName = false)
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
        if ($isShortName) {
            return date("D", $this->intTimeStamp);
        } else {
            return date("l", $this->intTimeStamp);
        }
    }

    function Hour()
    {
        return $this->intHour;
    }

    function Minute()
    {
        return $this->intMinute;
    }

    function Month()
    {
        return $this->intMonth;
    }

    function Second()
    {
        return $this->intSecond;
    }

    function Year()
    {
        return $this->intYear;
    }

    //0 = sunday...6 = saturday

    function addDays($intDay)
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay + $intDay,
            $this->intYear
        );
        $this->convertTimeStampToDate();
        return $this;
    }

    //default date is now if no parameter provided

    function addHours($intHour)
    {
        $this->intTimeStamp = mktime(
            $this->intHour + $intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
        $this->convertTimeStampToDateTime();
        return $this;
    }

    //addDays
    //tomorrow = addDays(1)
    //yesterday = addDays(-1)

    function addMinutes($intMinutes)
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute + $intMinutes,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
        $this->convertTimeStampToDateTime();
        return $this;
    }

    //addDays
    //next month = addMonths(1)
    //last month = addMonths(-1)

    function addMonths($intMonth)
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth + $intMonth,
            $this->intDay,
            $this->intYear
        );
        $this->convertTimeStampToDate();
        return $this;
    }

    //addDays
    //next year = addYears(1)
    //last year = addYears(-1)

    function addSeconds($intSeconds)
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond + $intSeconds,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
        $this->convertTimeStampToDateTime();
        return $this;
    }

    //addDays
    //next Hour = addHours(1)
    //last Hour = addHours(-1)

    function addYears($intYear)
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear + $intYear
        );
        $this->intYear += $intYear;
        return $this;
    }

    function convertTimeStampToDate()
    {
        $temp = date("Y-m-d", $this->intTimeStamp);
        $currDate = explode("-", $temp);
        $this->intYear = intval($currDate[0]);
        $this->intMonth = intval($currDate[1]);
        $this->intDay = intval($currDate[2]);
    }

    function convertTimeStampToDateTime()
    {
        $temp = date("Y-m-d H:i:s", $this->intTimeStamp);
        $arrData = explode(" ", $temp);
        $currDate = explode("-", $arrData[0]);
        $currTime = explode(":", $arrData[1]);
        $this->intYear = intval($currDate[0]);
        $this->intMonth = intval($currDate[1]);
        $this->intDay = intval($currDate[2]);
        $this->intHour = intval($currTime[0]);
        $this->intMinute = intval($currTime[1]);
        $this->intSecond = intval($currTime[2]);
    }

    function dateDiff($dateToDiff, $interval = "day", $isAbsolute = true)
    {
        if (is_a($dateToDiff, 'clsDate')) {
            if ($isAbsolute) {
                $selisih = abs($this->intTimeStamp - $dateToDiff->intTimeStamp);
            } else {
                $selisih = $this->intTimeStamp - $dateToDiff->intTimeStamp;
            }
            switch ($interval) {
                case "year" :
                    if ($isAbsolute) {
                        $selisih = abs($this->intYear - $dateToDiff->intYear);
                    } else {
                        $selisih = $this->intYear - $dateToDiff->intYear;
                    }
                    $intervalValue = 1;
                    break;
                case "month" :
                    if ($isAbsolute) {
                        $selisih = abs($this->intMonth - $dateToDiff->intMonth);
                    } else {
                        $selisih = $this->intMonth - $dateToDiff->intMonth;
                    }
                    return $selisih;
                    break;
                case "week" :
                    $weekNo = date("w", $this->intTimeStamp);
                    $intervalValue = 604800;
                    $sisaSelisih = $selisih % $intervalValue;
                    if ($sisaSelisih == 0) {
                        $selisih = floor($selisih / $intervalValue);
                    } else {
                        $sisaSelisih = ceil($sisaSelisih / 86400);
                        $weekNo += $sisaSelisih;
                        if ($weekNo > 6) {
                            $selisih = floor($selisih / $intervalValue) + 1;
                        } else {
                            $selisih = floor($selisih / $intervalValue);
                        }
                    }
                    return $selisih;
                    break;
                case "hour" :
                    $intervalValue = 3600;
                    $sisaSelisih = $selisih % $intervalValue;
                    if ($sisaSelisih == 0) {
                        $selisih = floor($selisih / $intervalValue);
                    } else {
                        $tempDate = $this->intTimeStamp + $sisaSelisih;
                        if (intval(date("H", $tempDate)) != $this->intHour) {
                            $selisih = floor($selisih / $intervalValue) + 1;
                        } else {
                            $selisih = floor($selisih / $intervalValue);
                        }
                    }
                    return $selisih;
                    break;
                case "minute" :
                    $intervalValue = 60;
                    $sisaSelisih = $selisih % $intervalValue;
                    if ($sisaSelisih == 0) {
                        $selisih = floor($selisih / $intervalValue);
                    } else {
                        $tempDate = $this->intTimeStamp + $sisaSelisih;
                        if (intval(date("i", $tempDate)) != $this->intMinute) {
                            $selisih = floor($selisih / $intervalValue) + 1;
                        } else {
                            $selisih = floor($selisih / $intervalValue);
                        }
                    }
                    return $selisih;
                    break;
                case "second" :
                    return $selisih;
                    break;
                default :
                    $intervalValue = 86400;
                    $sisaSelisih = $selisih % $intervalValue;
                    if ($sisaSelisih == 0) {
                        $selisih = floor($selisih / $intervalValue);
                    } else {
                        $tempDate = $this->intTimeStamp + $sisaSelisih;
                        if (intval(date("j", $tempDate)) != $this->intDay) {
                            $selisih = floor($selisih / $intervalValue) + 1;
                        } else {
                            $selisih = floor($selisih / $intervalValue);
                        }
                    }
                    return $selisih;
            }
            return ceil($selisih / $intervalValue);
        } else {
            return 0;
        }
    }

    function daysInMonth()
    {
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
        return intval(date("t", $this->intTimeStamp));
    }

    function equals($dateToCompare)
    {
        if (is_a($dateToCompare, 'clsDate')) {
            return ($dateToCompare->intTimeStamp === $this->intTimeStamp);
        } else {
            return false;
        }
    }

    function format($format = "Y-m-d")
    {
        if ($format == "") {
            $format = "Y-m-d";
        }
        return date($format, $this->intTimeStamp);
    }

    function getCurrentDate()
    {
        $temp = date("Y-m-d H:i:s");
        $arrData = explode(" ", $temp);
        $currDate = explode("-", $arrData[0]);
        $currTime = explode(":", $arrData[1]);
        $this->intYear = intval($currDate[0]);
        $this->intMonth = intval($currDate[1]);
        $this->intDay = intval($currDate[2]);
        $this->intHour = intval($currTime[0]);
        $this->intMinute = intval($currTime[1]);
        $this->intSecond = intval($currTime[2]);
        $this->intTimeStamp = mktime(
            $this->intHour,
            $this->intMinute,
            $this->intSecond,
            $this->intMonth,
            $this->intDay,
            $this->intYear
        );
    }

    /*Fungsi berikut akan menghitung selisih date berdasarkan interval
    interval :
    "day" = default = interval hari
    "hour" = interval jam
    "minute" = interval menit
    "second" = interval detik
    "week" = interval minggu
    "month" = interval bulan
    "year" = interval tahun

    NB: Jika terjadi hasil pecahan maka selisih akan di bulatkan ke atas (CEIL),
           Masukkan parameter $isAbsolute = false untuk menghasilkan selisih angka negative/positive dari selisih tsb
    */

    function isLeapYear()
    {
        return ((date("L", $this->intTimeStamp)) === "1");
    }

    /*FORMAT same with format date in PHP (See PHP documentation)
    -------------------------------------------------------
      a Lowercase Ante meridiem and Post meridiem am or pm
      A Uppercase Ante meridiem and Post meridiem AM or PM
      B Swatch Internet time 000 through 999
      c ISO 8601 date (added in PHP 5) 2004-02-12T15:19:21+00:00
      d Day of the month, 2 digits with leading zeros 01 to 31
      D A textual representation of a day, three letters Mon through Sun
      F A full textual representation of a month, such as January or March January through December
      g 12-hour format of an hour without leading zeros 1 through 12
      G 24-hour format of an hour without leading zeros 0 through 23
      h 12-hour format of an hour with leading zeros 01 through 12
      H 24-hour format of an hour with leading zeros 00 through 23
      i Minutes with leading zeros 00 to 59
      I (capital i) Whether or not the date is in daylights savings time 1 if Daylight Savings Time, 0 otherwise.
      j Day of the month without leading zeros 1 to 31
      l (lowercase 'L') A full textual representation of the day of the week Sunday through Saturday
      L Whether it's a leap year 1 if it is a leap year, 0 otherwise.
      m Numeric representation of a month, with leading zeros 01 through 12
      M A short textual representation of a month, three letters Jan through Dec
      n Numeric representation of a month, without leading zeros 1 through 12
      O Difference to Greenwich time (GMT) in hours Example: +0200
      r � RFC 2822 formatted date Example: Thu, 21 Dec 2000 16:01:07 +0200
      s Seconds, with leading zeros 00 through 59
      S English ordinal suffix for the day of the month, 2 characters st, nd, rd or th. Works well with j
      t Number of days in the given month 28 through 31
      T Timezone setting of this machine Examples: EST, MDT ...
      U Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) See also time()
      w Numeric representation of the day of the week 0 (for Sunday) through 6 (for Saturday)
      W ISO-8601 week number of year, weeks starting on Monday (added in PHP 4.1.0) Example: 42 (the 42nd week in the year)
      Y A full numeric representation of a year, 4 digits Examples: 1999 or 2003
      y A two digit representation of a year Examples: 99 or 03
      z The day of the year (starting from 0) 0 through 365
      Z Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive. -43200 through 43200
      */

    function set($year, $month, $day, $hour = 0, $minute = 0, $second = 0)
    {
        $this->intYear = $year;
        $this->intMonth = $month;
        $this->intDay = $day;
        $this->intHour = $hour;
        $this->intMinute = $minute;
        $this->intSecond = $second;
        $this->intTimeStamp = mktime($hour, $minute, $second, $month, $day, $year);
    }
}

?>