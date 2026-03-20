<?php

/**
 * Functions
 * This class is used to to supply some commonly used functions
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   2021 Alabian Solutions Limited
 * @version     1.0 => August 2021
 * @link        alabiansolutions.com
 */

class Functions
{
    /** @var int indicate when an integer does not exist */
    public const NO_INT_VALUE = 9223372036854775807;

    /** @var int indicate when an integer does not exist */
    public const INFINITE = 9223372036854775807;

    /** @var int length of reset time in seconds - 12 hours */
    public const RESET_TIME_LIMIT = 1 * 60 * 60;

    /** @var int length of activation in seconds  - 12 hours */
    public const ACTIVATION_TIME_LIMIT = 12 * 60 * 60;

    /** @var int length of access token time in seconds - 30 minutes */
    public const ACCESS_TOKEN_LIMIT = 30 * 60;

    /** @var int length of refresh token in seconds  - 15 days */
    public const REFRESH_TOKEN_LIMIT = 15 * 24 * 60 * 60;

    /** @var int epsilon value for checking zerolisation of float value */
    public const EPSILON = 0.00001;

    /** @var string app logo */
    public const LOGO = 'logo.png';

    /** @var string app favicon */
    public const FAVICON = 'favicon.ico';

    /** @var string directory name where avatars are stored */
    public const AVATAR_DIRECTORY = 'avatar/';

    /** @var string default profile avatar */
    public const DEFAULT_AVATAR = 'default.png';

    /** @var string default profile avatar for male */
    public const DEFAULT_AVATAR_MALE = 'default-male.png';

    /** @var string default profile avatar female */
    public const DEFAULT_AVATAR_FEMALE = 'default-female.png';

    /** @var string directory where css files are stored */
    public const CSS_DIRECTORY = 'assets/css/';

    /** @var string directory where css files are stored */
    public const ASSET_DIRECTORY = 'assets/';

    /** @var string directory where js files are stored */
    public const JS_DIRECTORY = 'assets/js/';

    /** @var string directory where image files are stored */
    public const IMAGE_DIRECTORY = 'assets/image/';

    /** @var string directory where video files are stored */
    public const VIDEO_DIRECTORY = 'assets/video/';

    /** @var string directory where audio are stored */
    public const AUDIO_DIRECTORY = 'assets/audio/';

    /** @var string document directory where files are stored */
    public const DOCUMENT_DIRECTORY = 'document/';


    /**
     * get the name of the CSRF token
     *
     * @return string $string the CSRF token name
     */
    public static function getCsrfTokenSessionName(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $internalName = $Settings->getDetails()->sitenameInternal;
        return "{$internalName}_{$Settings->getDetails()->token}_csrf_token";
    }

    /**
     * get the name of the session id
     *
     * @return string $string the session id name
     */
    public static function getSessionIdName(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $internalName = $Settings->getDetails()->sitenameInternal;
        return "{$internalName}_{$Settings->getDetails()->token}_session_id";
    }

    /**
     * get the name of the session logger id name
     *
     * @return string $string the session logger id name
     */
    public static function getLoggerIdSessionName(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $internalName = $Settings->getDetails()->sitenameInternal;
        return "{$internalName}_{$Settings->getDetails()->token}_logger_id";
    }

    /**
     * get the name of the session fingerprint name
     *
     * @return string $string the session fingerprint name
     */
    public static function getFingerprintSessionName(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $internalName = $Settings->getDetails()->sitenameInternal;
        return "{$internalName}_{$Settings->getDetails()->token}_fingerprint";
    }

    /**
     * get the directory path to where document are stored
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the path to the directory where avatar are store
     */
    public static function getDocDirectoryPath(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        $path = $Settings->getDetails()->machine->path.$backend.$Settings->getDetails()->documentFolder.DIRECTORY_SEPARATOR;
        return $path;
    }

    /**
     * get the url to the document directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the asset directory
     */
    public static function getDocUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        $url = $Settings->getDetails()->machine->url.$backend.$Settings->getDetails()->documentFolder."/";
        return $url;
    }

    /**
     * get the directory path to where purchase order document are stored
     *
     * @param bool $isBackend if the app is in a backend directory
     * @return string $string the path to the directory where purchase order document are store
     */
    public static function getPODocDirectoryPath(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $backend = $isBackend ? $Settings->getDetails()->machine->backend.DIRECTORY_SEPARATOR : "";
        $path = $Settings->getDetails()->machine->path.$backend.$Settings->getDetails()->documentFolder.DIRECTORY_SEPARATOR."purchase-order/";
        return $path;
    }

    /**
     * get the url to the purchase order document directory
     *
     * @param bool $isBackend if the app is in a backend directory
     * @return string $string the url to the purchase order directory
     */
    public static function getPODocUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        $url = $Settings->getDetails()->machine->url.$backend.$Settings->getDetails()->documentFolder."/purchase-order/";
        return $url;
    }

    /**
     * get the directory path to where avatar are stored
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the path to the directory where avatar are store
     */
    public static function getAvatarDirectoryPath(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $path = $Settings->getDetails()->machine->path;
        $documentFolder = $Settings->getDetails()->documentFolder.DIRECTORY_SEPARATOR;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend.DIRECTORY_SEPARATOR : "";
        return $path.$backend.$documentFolder.Functions::AVATAR_DIRECTORY;
    }


    /**
     * get the url to the asset directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the asset directory
     */
    public static function getAssetUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::ASSET_DIRECTORY;
    }

    /**
     * get the url to the css directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the css directory
     */
    public static function getCssUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::CSS_DIRECTORY;
    }

    /**
     * get the url to the js directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the js directory
     */
    public static function getJsUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::JS_DIRECTORY;
    }

    /**
     * get the url to the image directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the image directory
     */
    public static function getImageUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::IMAGE_DIRECTORY;
    }

    /**
     * get the directory path to the image directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the path to the image directory
     */
    public static function getImageDirectoryPath(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $path = $Settings->getDetails()->machine->path;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $path.$backend.Functions::IMAGE_DIRECTORY;
    }

    /**
     * get the url to the audio directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the audio directory
     */
    public static function getAudioUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::AUDIO_DIRECTORY;
    }

    /**
     * get the url to the video directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the video directory
     */
    public static function getVideoUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::VIDEO_DIRECTORY;
    }

    /**
     * get the url to the avatar in document directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the avatar document directory
     */
    public static function getDocAvatarsUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::DOCUMENT_DIRECTORY.self::AVATAR_DIRECTORY;
    }

    /**
     * get the url to the photo in document directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the photo document directory
     */
    public static function getDocPhotosUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::DOCUMENT_DIRECTORY."photos/";
    }

    /**
     * get the url to the video in document directory
     *
     * @param bool $isBackend if the path should redirect to the backend
     * @return string $string the url to the video document directory
     */
    public static function getDocVideosUrl(bool $isBackend = false): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        $backend = $isBackend ? $Settings->getDetails()->machine->backend."/" : "";
        return $url.$backend.Functions::DOCUMENT_DIRECTORY."videos/";
    }

    /**
     * generate the CSRF token
     *
     * @return string
     */
    public static function getCSRFToken(): string
    {
        if (!isset($_SESSION[Functions::getCsrfTokenSessionName()])) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[Functions::getCsrfTokenSessionName()] = $token;
        } else {
            $token = $_SESSION[Functions::getCsrfTokenSessionName()];
        }
        return $token;
    }

    /**
     * check if the active the CSRF token is ok
     *
     * @param string $token the CSRF token to be checked
     * @return bool
     */
    public static function checkCSRFToken(string $token): bool
    {
        $equal = false;
        $equal = hash_equals($token, Functions::getCSRFToken());
        return $equal;
    }

    /**
     * Generate the ASCII code of digits, alphabet upper & case
     * @return array $array an array that contains ASCII of digits, alphabet upper & lower case
     */
    public static function asciiTableDigitalAlphabet(): array
    {
        $array = array();
        //Digitals
        for ($kanter = 48; $kanter <= 57; $kanter++) {
            $array[] = $kanter;
        }
        //Uppercase
        for ($kanter = 65; $kanter <= 90; $kanter++) {
            $array[] = $kanter;
        }
        //Lowercase
        for ($kanter = 97; $kanter <= 122; $kanter++) {
            $array[] = $kanter;
        }
        shuffle($array);
        return $array;
    }

    /**
     * Generate the ASCII code of digits, alphabet upper & case
     * @param array $ASCIIArray an array that contains ASCII Code
     * @param string $dataFormat the format of the return value of array for array or other value for string
     * @return string $characters an array or string that contains character that matches the ASCII Code supplied
     */
    public static function characterFromASCII(array $ASCIIArray, string $dataFormat = 'array'): string
    {
        $max = count($ASCIIArray);
        for ($kanter = 0; $kanter < $max; $kanter++) {
            $array[] = chr($ASCIIArray[$kanter]);
        }
        if ($dataFormat == 'array') {
            $characters = $array;
        } else {
            $characters = "";
            foreach ($array as $anArrayValue) {
                $characters .= $anArrayValue;
            }
        }
        return $characters;
    }

    /**
     * Generate ASCII code
     *
     * @param int the no of item in the return array
     * @param boolean $onlyDigitAlphabet if true only code of digit, alphabet are return
     * @param array $range an array of start and end of of need ASCII code
     * @param boolean $shuffledIt if true return array is shuffled
     * @param boolean $isChar if true return character other returns the integer code
     * @return array $array an array that contains ASCII code
     */
    public static function asciiCollection(
        int $count = Functions::INFINITE,
        bool $onlyDigitAlphabet = true,
        array $range = [],
        bool $shuffledIt = true,
        bool $isChar = true
    ): array {
        $array = [];
        if ($onlyDigitAlphabet) {
            //Digitals
            for ($kanter = 48; $kanter <= 57; $kanter++) {
                $array[] = $kanter;
            }
            //Uppercase
            for ($kanter = 65; $kanter <= 90; $kanter++) {
                $array[] = $kanter;
            }
            //Lowercase
            for ($kanter = 97; $kanter <= 122; $kanter++) {
                $array[] = $kanter;
            }
        } elseif ($range) {
            for ($kanter = $range[0]; $kanter <= $range[1]; $kanter++) {
                $array[] = $kanter;
            }
        } else {
            for ($kanter = 0; $kanter <= 127; $kanter++) {
                $array[] = $kanter;
            }
        }
        if ($shuffledIt) {
            shuffle($array);
        }
        if ($count != Functions::INFINITE) {
            $array = array_slice($array, 0, ($count));
        }
        if ($isChar) {
            $array = array_map(function ($arr) {
                return chr($arr);
            }, $array);
        }
        return $array;
    }

    /**
     * for validating date
     *
     * @param string $date the date to be validated
     * @return boolean
     */
    public static function isValidDate($date)
    {
        return (strtotime($date) !== false);
    }

    /**
     * generates a single character for a year
     *
     * @param int $year the year in YYYY format
     * @return string the single character for the year
     */
    public static function yearToChr(int $year):string
    {
        $chrCollection = [];
        $settings = new Settings(SETTING_FILE);
        $lastYear = $settings->getDetails()->baseYear + 10 + 26;
        $digitBase = 0;
        $alphabetBase = 65;
        for ($index = $settings->getDetails()->baseYear; $index < $lastYear ; $index++) {
            if ($digitBase < 10) {
                $chrCollection[$index] = $digitBase;
                ++$digitBase;
            } else {
                $chrCollection[$index] = chr($alphabetBase);
                ++$alphabetBase;
            }
        }
        return $chrCollection[$year];
    }

    /**
     * generates a single character for a month
     *
     * @param int $month the month in either M or MM format
     * @return string the single character for the month
     */
    public static function monthToChr(int $month):string
    {
        $chrCollection = [];
        $digitBase = 0;
        $alphabetBase = 65;
        for ($index = 1; $index <= 12 ; $index++) {
            if ($digitBase < 10) {
                $chrCollection[$index] = $digitBase;
                ++$digitBase;
            } else {
                $chrCollection[$index] = chr($alphabetBase);
                ++$alphabetBase;
            }
        }
        return $chrCollection[$month];
    }

    /**
     * generates a single character for a day
     *
     * @param int $day the day in either D or DD format
     * @return string the single character for the day
     */
    public static function dayToChr(int $day):string
    {
        $chrCollection = [];
        $digitBase = 0;
        $alphabetBase = 65;
        for ($index = 1; $index <= 31 ; $index++) {
            if ($digitBase < 10) {
                $chrCollection[$index] = $digitBase;
                ++$digitBase;
            } else {
                $chrCollection[$index] = chr($alphabetBase);
                ++$alphabetBase;
            }
        }
        return $chrCollection[$day];
    }

    /**
     * Checks if a given string is a valid JSON string.
     *
     * @param string $string The string to check.
     * @return bool True if the string is valid JSON, false otherwise.
     */
    private static function isJson(string $string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Applies htmlspecialchars to all string values in an array, except JSON strings.
     *
     * @param array $row The associative array containing values to be escaped.
     * @return array The array with escaped string values, leaving JSON strings untouched.
     */
    public static function getHtmlSpecialRow(array $row): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return self::isJson($value) ? $value : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            return $value;
        }, $row);
    }
    

    /**
     * sorts an associative array by key
     *
     * @param array $row The associative array to be sorted.
     * @param string $key the key to be sorted
     * @return array The sorted array
     */
    public static function sortArrayByKey(array $array, string $key):array
    {
        usort($array, function ($a, $b) use ($key) {
            return strcasecmp($a[$key], $b[$key]); // Case-insensitive comparison
        });
        return $array;
    }

    /**
     * Encrypts a given URL using AES-256-CBC encryption.
     *
     * This function uses a secret key to encrypt the URL and returns a base64 encoded token.
     * The token is composed of the 16 bytes initialization vector (IV) followed by the encrypted URL.
     *
     * @param string $url The URL to be encrypted.
     * @param string $secretKey The secret key used for encryption.
     * @return string The encrypted token.
     */
    public static function encryptUrl(string $url, string $secretKey): string
    {
        $iv = openssl_random_pseudo_bytes(16); // CBC mode needs 16 bytes IV
        $key = hash('sha256', $secretKey, true); // Derive a 256-bit key from secretKey

        $encrypted = openssl_encrypt($url, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $token = base64_encode($iv . $encrypted);

        return $token;
    }

    /**
     * Decrypts an encrypted URL token using AES-256-CBC decryption.
     *
     * This function expects a base64 encoded token containing a 16-byte IV followed by the encrypted data.
     * It uses the given secret key to derive a 256-bit decryption key and returns the decrypted URL.
     *
     * @param string $token The base64 encoded token containing the IV and encrypted URL.
     * @param string $secretKey The secret key used for decryption.
     * @return string|null The decrypted URL, or null if decryption fails.
     */
    public static function decryptUrl(string $token, string $secretKey): ?string
    {
        $raw = base64_decode($token);
        if ($raw === false || strlen($raw) < 17) {
            return null;
        } // must include IV + some encrypted data

        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $key = hash('sha256', $secretKey, true);

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted ?: null;
    }

}
