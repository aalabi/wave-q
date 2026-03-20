<?php

/**
 * SqlType
 * This class is used to to interact with sql data types
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   2021 Alabian Solutions Limited
 * @version     1.0 => August 2021 2.0 => October 2022
 * @link        alabiansolutions.com
 */

class SqlType
{
    /** @var int max SQL unsighted tiny int */
    public const TINY_INT_MAX = 255;

    /** @var int min SQL unsighted tiny int */
    public const TINY_INT_MIN = 0;

    /** @var int max SQL sighted tiny int */
    public const S_TINY_INT_MAX = 127;

    /** @var int min SQL sighted tiny int */
    public const S_TINY_INT_MIN = -128;
    
    /** @var int max SQL unsighted small int */
    public const SMALL_INT_MAX = 65535;

    /** @var int min SQL unsighted small int */
    public const SMALL_INT_MIN = 0;

    /** @var int max SQL sighted small int */
    public const S_SMALL_INT_MAX = 32767;

    /** @var int min SQL sighted small int */
    public const S_SMALL_INT_MIN = -32768;
    
    /** @var int max SQL unsighted medium int */
    public const MEDIUM_INT_MAX = 16777215;

    /** @var int min SQL unsighted medium int */
    public const MEDIUM_INT_MIN = 0;

    /** @var int max SQL sighted medium int */
    public const S_MEDIUM_INT_MAX = 8388607;

    /** @var int min SQL sighted medium int */
    public const S_MEDIUM_INT_MIN = -8388608;
    
    /** @var int max SQL unsighted int */
    public const INT_MAX = 4294967295;

    /** @var int min SQL unsighted int */
    public const INT_MIN = 0;

    /** @var int max SQL sighted int */
    public const S_INT_MAX = 2147483647;

    /** @var int min SQL sighted int */
    public const S_INT_MIN = -2147483648;
    
    /** @var int max SQL unsighted big int */
    public const BIG_INT_MAX = 18446744073709551615;

    /** @var int min SQL unsighted big int */
    public const BIG_INT_MIN = 0;

    /** @var int max SQL sighted big int */
    public const S_BIG_INT_MAX = 9223372036854775807;

    /** @var int min SQL sighted big int */
    public const S_BIG_INT_MIN = -9223372036854775808;

    /** @var string max SQL Date */
    public const DATE_MAX = '9999-12-31';

    /** @var string min SQL Date */
    public const DATE_MIN = '1000-01-01';

    /** @var string max SQL DateTime */
    public const DATETIME_MAX = '9999-12-31 23:59:59';

    /** @var string min SQL DateTime */
    public const DATETIME_MIN = '1000-01-01 00:00:00';

    /** @var int max varchar string length */
    public const VARCHAR_LENGTH = 65535;

    /** @var int max char string length */
    public const CHAR_LENGTH = 255;

    /** @var int max text string length */
    public const TEXT_LENGTH = 2**16 - 1;

    /** @var int max medium text string length */
    public const MEDIUM_TEXT_LENGTH = 2**24 - 1;

    /** @var int max long text string length */
    public const LONG_TEXT_LENGTH = 2**32 - 1;

    /**
     * validates unsigned tiny integer
     *
     * @param integer $int the tiny integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isTinyIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::TINY_INT_MIN && $int > SqlType::TINY_INT_MAX) {
            $ok = false;
        }
        if(!is_int($int)) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates signed tiny integer
     *
     * @param integer $int the signed tiny integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isSignedTinyIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::S_TINY_INT_MIN && $int > SqlType::S_TINY_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates signed small integer
     *
     * @param integer $int the signed small integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isSignedSmallIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::S_SMALL_INT_MIN && $int > SqlType::S_SMALL_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates small integer
     *
     * @param integer $int the small integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isSmallIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::SMALL_INT_MIN && $int > SqlType::SMALL_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates signed medium integer
     *
     * @param integer $int the signed medium integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isSignedMediumIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::S_MEDIUM_INT_MIN && $int > SqlType::S_MEDIUM_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates medium integer
     *
     * @param integer $int the medium integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isMediumIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::MEDIUM_INT_MIN && $int > SqlType::MEDIUM_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates unsigned integer
     *
     * @param integer $int the integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::INT_MIN && $int > SqlType::INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates signed integer
     *
     * @param integer $int the signed integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isSignedIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::S_INT_MIN && $int > SqlType::S_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates unsigned big integer
     *
     * @param integer $int the  big integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isBigIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::BIG_INT_MAX && $int > SqlType::BIG_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates signed big integer
     *
     * @param integer $int the signed big integer value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isSignedBigBigIntOk(int $int):bool
    {
        $ok = true;
        if($int < SqlType::S_BIG_INT_MIN && $int > SqlType::S_BIG_INT_MAX) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates date
     *
     * @param DateTime $date the date value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isDateOk(DateTime $date):bool
    {
        $ok = true;
        if($date < new DateTime(SqlType::DATE_MIN) && $date > new DateTime(SqlType::DATE_MAX)) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates datetime
     *
     * @param DateTime $date the datetime value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isDateTimeOk(DateTime $date):bool
    {
        $ok = true;
        if($date < new DateTime(SqlType::DATETIME_MIN) && $date > new DateTime(SqlType::DATETIME_MAX)) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates char
     *
     * @param string $char the char value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isCharOk(string $char):bool
    {
        $ok = true;
        if(strlen($char) > SqlType::CHAR_LENGTH) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates varchar
     *
     * @param string $varchar the varchar value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isVarcharOk(string $varchar):bool
    {
        $ok = true;
        if(strlen($varchar) > SqlType::VARCHAR_LENGTH) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates text
     *
     * @param string $text the text value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isTextOk(string $text):bool
    {
        $ok = true;
        if(strlen($text) > SqlType::TEXT_LENGTH) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates medium text
     *
     * @param string $text the medium text value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isMediumTextOk(string $text):bool
    {
        $ok = true;
        if(strlen($text) > SqlType::MEDIUM_TEXT_LENGTH) {
            $ok = false;
        }
        return $ok;
    }

    /**
     * validates long text
     *
     * @param string $text the long text value
     * @return boolean true if the value is valid, false otherwise
     */
    public static function isLongTextOk(string $text):bool
    {
        $ok = true;
        if(strlen($text) > SqlType::LONG_TEXT_LENGTH) {
            $ok = false;
        }
        return $ok;
    }

}
