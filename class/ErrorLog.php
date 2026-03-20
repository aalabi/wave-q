<?php

/**
 * ErrorLog
 *
 * A class for error logging
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
 */

class ErrorLog
{
    /** @var boolean send mail if set to true */
    public $sentMail = true;

    /** @var boolean send SMS if set to true */
    public $sendSMS = false;

    /** @var boolean log error message if set to true */
    public $logMessage = true;

    /** @var  string directory location for error log file */
    private const DIRECTORY  = "error/";

    /** @var  string file for error log */
    private const FILE = "error.log";

    /** @var  string error message that occur */
    private $message;

    /** @var  string filename in which the error occur */
    private $file;

    /** @var  int the line no where the error occur */
    private $line;

    /** @var Settings an instance of Settings */
    protected Settings $settings;

    /**
     * instantiation of ErrorLog
     *
     * @param string $message the error message
     * @param string $file the file where the error occurred
     * @param int $line the line no where the error occurred
     */
    public function __construct(string $message, string $file, int $line)
    {
        $this->settings = new Settings(SETTING_FILE);
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->logMessage ? $this->log() : null;
        $this->sendSMS ? $this->sendSMS() : null;
        $this->sentMail ? $this->sentMail() : null;
        if ((new Settings(SETTING_FILE))->getAllDetails()->mode == "development") {
            echo $this->message;
        }
    }

    /**
     * get the directory path to where error log is stored
     *
     * @return string $string the path to the directory
     */
    public static function getErrorDirectoryPath(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $path = $Settings->getDetails()->machine->path;
        return $path.ErrorLog::DIRECTORY;
    }

    /**
     * simply call the method that writes to file
     *
     * @return void
     */
    private function log()
    {
        $this->writeToFile($this->message, $this->file, $this->line);
    }

    /**
     * write the error message to file
     *
     * @param string $msg the message to be written
     * @param string $file the file where the error occur
     * @param integer $line line where the error logger was called
     * @return void
     */
    private static function writeToFile(string $msg, string $file, int $line)
    {
        $handle = fopen(ErrorLog::getErrorDirectoryPath() . ErrorLog::FILE, "a+");
        $message = "$msg\t $line\t $file\t " . date("Y-m-d h:ia") . "\n";
        fputs($handle, $message);
        fclose($handle);
    }

    /**
     * Form the error message to be sent out
     * @return string $message the error message to be sent out
     */
    private function setEmailMsg(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $error = $this->message;
        $message = "
            <p style='margin-bottom:10px; margin-top:10px;'>Good Day Admin</p>
            <p style='margin-bottom:10px;'>
                This is to inform you that something went wrong on " . $Settings->getDetails()->sitename . ". This error has 
                been log to file on server, which you can review anytime.  
            </p>
            <p style='margin-bottom:60px;'>
                <strong>Error Message</strong><br/>
                $error<br/>
                {$_SERVER['REMOTE_ADDR']}<br/>
                {$_SERVER['HTTP_USER_AGENT']}<br/>"
                . date('l F jS, Y - g:ia') . "<br/>
            </p>
        ";
        return $message;
    }

    /**
     * send the error message to some emails
     *
     * @return void
     */
    private function sentMail()
    {
        $Settings = new Settings(SETTING_FILE);
        $emails = $Settings->getDetails()->errorNotifier->email;
        if ($emails) {
            $Notification = new Notification();
            $subject = $Settings->getDetails()->sitename . " System Error";
            foreach ($emails as $anEmail) {
                $Notification->sendMail(['to' => [$anEmail]], $subject, $this->setEmailMsg());
            }
        }
    }

    /**
     * send the error message to some phones
     *
     * @return void
     */
    private function sendSMS()
    {
        $Settings = new Settings(SETTING_FILE);
        $phones = $Settings->getDetails()->errorNotifier->phone;
        if ($phones) {
            foreach ($phones as $aPhoneNo) {
                $Notification = new Notification();
                $message = "System error occurred on " . $Settings->getDetails()->sitename . " by " . date('l F jS, Y - g:ia') . "check error log for more details";
                $Notification->sendSMS([$aPhoneNo], $message);
            }
        }
    }
}
