<?php

/**
 * QuasinInbox
 *
 * Handling of Quasi Inbox
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   2021 Alabian Solutions Limited
 * @version     1.0 => October 2022
 * @link        alabiansolutions.com
 */
class QuasiInbox
{
    /** @var string inbox directory for managing mail */
    private const INBOX = "inbox/";

    /** @var string inbox box directory for storing mails */
    private const BOX = "box/";

    /** @var string filename of the csv file where mail details are saved */
    private const CSV_FILENAME = "inbox.csv";

    /**
     * get the path to the quasi inbox directory
     *
     * @return string $string the path
     */
    private static function getInboxPath(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $path = $Settings->getDetails()->machine->path;
        if($Settings->getDetails()->machine->backend) {
            $path .= $Settings->getDetails()->machine->backend.DIRECTORY_SEPARATOR;
        }
        return $path.Self::INBOX;
    }

    /**
     * get the url to the quasi inbox on the browser
     *
     * @return string $string the url
     */
    public static function getInboxUrl(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        if($Settings->getDetails()->machine->backend) {
            $url .= $Settings->getDetails()->machine->backend.DIRECTORY_SEPARATOR;
        }
        return $url.Self::INBOX;
    }

    /**
     * get the path to the quasi box directory
     *
     * @return string $string the path
     */
    private static function getBoxPath(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $path = $Settings->getDetails()->machine->path;
        if($Settings->getDetails()->machine->backend) {
            $path .= $Settings->getDetails()->machine->backend.DIRECTORY_SEPARATOR;
        }
        return $path.Self::INBOX.Self::BOX;
    }

    /**
     * get the url to the quasi box on the browser
     *
     * @return string $string the url
     */
    public static function getBoxUrl(): string
    {
        $Settings = new Settings(SETTING_FILE);
        $url = $Settings->getDetails()->machine->url;
        if($Settings->getDetails()->machine->backend) {
            $url .= $Settings->getDetails()->machine->backend.DIRECTORY_SEPARATOR;
        }
        return $url.Self::INBOX.Self::BOX;
    }

    /**
     * Send email to Quasi Inbox
     * @param string $recipient the email been sent to
     * @param string  $subject subject of the email
     * @param string $message message been sent
     */
    public static function mailQuasiInbox(string $recipient, string $subject, string $message): void
    {
        static $kanter = 0;
        $mailFilename = ($kanter++) . time() . ".html";
        $emailFile = fopen(self::getBoxPath() . $mailFilename, "w");
        fputs($emailFile, $message);
        fclose($emailFile);

        $mailDbFile = fopen(self::getInboxPath() . self::CSV_FILENAME, "a");
        $data = [$recipient, $subject, self::getBoxUrl() . $mailFilename];
        fputcsv($mailDbFile, $data);
        fclose($mailDbFile);
    }

    /**
     * Retrieve the content of Quasi Inbox
     * @param boolean $returnString if true returns a string or array on false
     * @return mixed
     */
    public static function getQuasiInboxContent(bool $returnString = true)
    {
        $inBoxContent = [];
        $mailDbFile = self::getInboxPath() . self::CSV_FILENAME;
        if (file_exists($mailDbFile)) {
            if (($mailDbFileHandle = fopen($mailDbFile, "r")) !== false) {
                while (($data = fgetcsv($mailDbFileHandle, 1000, ",")) !== false) {
                    $inBoxContent[] = ["recipient" => $data[0], "subject" => $data[1], "url" => $data[2]];
                }
            }
            fclose($mailDbFileHandle);
        }

        $table = "";
        if ($returnString) {
            $table = "<table border='1'>
                <thead>
                    <tr>
                        <td>S/N</td>
                        <td>Recipient</td>
                        <td>Subject</td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>";
            if ($inBoxContent) {
                $sn = 0;
                foreach ($inBoxContent as $anInBoxContent) {
                    $table .= "<tr>
                        <td>" . (++$sn) . "</td>
                        <td>{$anInBoxContent['recipient']}</td>
                        <td>{$anInBoxContent['subject']}</td>
                        <td><a href='{$anInBoxContent['url']}' target='_blank'>view</a></td>
                    </tr>";
                }
            } else {
                $table .= "
                    <tr>
                        <td colspan='4'>Empty Inbox</td>
                    </tr>";
            }
            $table .= "</tbody></table>";
        }

        $return = ($returnString) ? $table : $inBoxContent;
        return $return;
    }
}
