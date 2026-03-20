<?php

/**
 * Notification
 *
 * This class is used for user interaction
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   2021 Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
 */

class NotificationException extends Exception
{
}

class Notification
{
    /** @var DbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var Query an instance of Query  */
    protected Query $query;

    /** @var Settings an instance of setting  */
    protected Settings $settings;

    /** @var int in-built php mail function */
    public const MAIL = 1;

    /** @var int php mailer class */
    public const PHPMAILER = 2;

    /** @var int the method for sending mail  */
    protected int $mailMethod;

    /** @var array mail method collection */
    private const MAIL_COLLECTION = [Notification::MAIL, Notification::PHPMAILER];

    /** @var  int php mailer class */
    public const SMSAPI_1 = 1;

    /** @var int the API for sending SMS */
    protected int $smsAPI;

    /** @var  array mail method collection */
    private const SMSAPI_COLLECTION = [Notification::SMSAPI_1];

    /**
     * Setup up Notification
     * @param int $mailMethod the mail functionality to be used
     * @param int $smsAPI the SMS API to be used
     */
    public function __construct(int $mailMethod = Notification::MAIL, int $smsAPI = Notification::SMSAPI_1)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
        $this->settings = new Settings(SETTING_FILE, true);
        $this->mailMethod = $mailMethod;
        $this->smsAPI = $smsAPI;
    }

    /**
     *
     * for sending of email
     *
     * @param array $emails ['to'=>[name=>email,...],'from'=>[],'cc'=>[],'bcc'=>[], 'reply-to'=>[]]
     * @param  string $subject the subject of the mail
     * @param string $body the body of the mail can in HTML format
     * @return void
     */
    public function sendMail(array $emails, string $subject, string $body)
    {
        if (!in_array($this->mailMethod, Notification::MAIL_COLLECTION)) {
            throw new NotificationException("invalid mail method");
        }
        $exceptions = [];
        if (!isset($emails['to'])) {
            $exceptions[] = "'to' key missing in 1st parameter";
        }
        if (isset($emails['to']) && !is_array($emails['to'])) {
            $exceptions[] = "'to' key must be an array in 1st parameter";
        }
        if (isset($emails['from']) && !is_array($emails['from'])) {
            $exceptions[] = "'from' key must be an array in 1st parameter";
        }
        if (isset($emails['cc']) && !is_array($emails['cc'])) {
            $exceptions[] = "'cc' key must be an array in 1st parameter";
        }
        if (isset($emails['bcc']) && !is_array($emails['bcc'])) {
            $exceptions[] = "'bcc' key must be an array in 1st parameter";
        }
        if (isset($emails['reply-to']) && !is_array($emails['bcc'])) {
            $exceptions[] = "'reply-to' key must be an array in 1st parameter";
        }
        if ($exceptions) {
            $exceptionMsg = implode(", ", $exceptions);
            $exceptionMsg = rtrim($exceptionMsg, ", ");
            throw new NotificationException($exceptionMsg);
        }

        if ($this->mailMethod == Notification::MAIL) {
            $to = "";
            foreach ($emails['to'] as $name => $address) {
                $to .= !is_numeric($name) ? "$name <$address>, " : "$address, ";
            }
            $to = rtrim($to, ", ", );
            $receipents = $emails['to'];
            unset($emails['to']);

            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=utf-8';
            foreach ($emails as $anEmailKey => $emailArray) {
                if ($emailArray) {
                    $headers[] = $this->getAdditionalHeader($anEmailKey, $emailArray);
                }
            }

            $from = "";
            if (isset($emails['from']) && !array_keys($emails['from'], 0)) {
                foreach ($emails['from'] as $key => $value) {
                    $from = $key;
                }
            }
            $body = $this->bodyContentHead() . $body . $this->bodyContentFooter($from);
            if ($this->settings->getAllDetails()->mode == "development") {
                foreach ($receipents as $aReceipent) {
                    QuasiInbox::mailQuasiInbox($aReceipent, $subject, $body);
                }
            } else {
                mail($to, $subject, $body, implode("\r\n", $headers));
            }
        }

        if ($this->mailMethod == Notification::PHPMAILER) {
            //TODO mail code
        }
    }

    /**
     * generation of php mail additional header parameter
     *
     * @param string $key
     * @param array $array
     * @return string
     */
    private function getAdditionalHeader(string $key, array $array): string
    {
        $header = ($key == 'reply-to') ? "Reply-To : " : ucfirst($key) . ": ";
        foreach ($array as $name => $address) {
            $header .= !is_numeric($name) ? "$name <$address>, " : "$address, ";
        }
        $header = rtrim($header, ", ", );
        return $header;
    }

    /**
     * Simply generate the head part of the HTML string for generation of email template
     * @param string $logoUrl the url to the logo of the site
     * @return string
     */
    private function bodyContentHead(string $logoUrl = ""): string
    {
        $settings = new Settings(SETTING_FILE, true);
        $thereIsBackend = $this->settings->getDetails()->machine->backend ? true : false;
        $logoUrl = $logoUrl ? $logoUrl :  Functions::getImageUrl($thereIsBackend) . Functions::LOGO;
        $headEmail = "
            <html>
                <head>
                    <title></title>
                </head>
                <body>
                    <div style='width:88%; color: #fff; background-color: #3300c9;'>
                        <div style='
                            background-color: #fefefe;
                            border-bottom:2px solid #fbd602;
                            padding:7px 1% 7px;
                            margin-bottom:15px;
                            text-align: center;
                            '>
                                <div>
                                    <img src='$logoUrl' style='height:auto; width:100%; max-width:250px;'/>
                                </div>
                                <div style='clear:both;'>
                        </div>
                    </div>
                    <div style='padding:5px 1%; color:#fff; font-size:12px; font-family:Arial;'>";
        return $headEmail;
    }

    /**
     * Simply generate the footer part of the HTML string for generation of email template
     * @return string $footerEmail the footer part of the HTML string
     */
    private function bodyContentFooter(string $senderName = ""): string
    {
        $senderName = ($senderName) ? $senderName : "System Admin";
        $footerEmail = "
            </div>
            <div style='margin-bottom:60px; margin-top:30px; padding: 0px 1%;'>
                $senderName<br/>
                <a href='" . $this->settings->getDetails()->machine->url . "' style='color:#f0f0f0;'>For " . $this->settings->getDetails()->sitename . "</a>
            </div>
            <div style='font-size:9px; background-color: #fefefe; border:1px solid #fbd602; padding-top:10px; padding-bottom:10px;'>
                <div style='font-size:9px; float:left; color:#999; padding-left:5px' >
                    <a style='color:#999; text-decoration:none;' rel='nofollow' href='https://alabiansolutions.com'>powered by alabian</a>
                </div>
                <div style='font-size:9px; float:right; color:#999; padding-right:5px' >
                    &copy; " . date("Y") . " " . $this->settings->getDetails()->sitename . "
                </div>
                <div style='clear:both;'></div>
            </div>
            </div>
        </body>
        </html>
        ";
        return $footerEmail;
    }

    /**
     * for sending of SMS
     *
     * @param array $phones the receipents phones
     * @param string $content the SMS content
     * @return void
     */
    public function sendSMS(array $phones, string $content)
    {
        if (!in_array($phones, Notification::SMSAPI_COLLECTION)) {
            throw new NotificationException("invalid SMS API");
        }

        if ($this->smsAPI == Notification::SMSAPI_1) {
            //TODO mail code
        }
    }
}
