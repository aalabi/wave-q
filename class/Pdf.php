<?php

/**
 * Pdf
 *
 * A class managing the Pdf document generation
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => November 2024,
 * @link        alabiansolutions.com
*/

class PdfException extends Exception
{
}

class Pdf
{
    /** @var dbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var query an instance of Query  */
    protected Query $query;

    /** @var Settings an instance of Settings  */
    protected Settings $settings;

    /** @var array generation format values*/
    public const FORMATS = ["browser", "download", "save", "string",  "browser"=>"browser", "download"=>"download", "save"=>"save", "string"=>"string"];

    /**
     * instantiation of Pdf
     *
     */
    public function __construct()
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->settings = new Settings(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
    }

    protected function requireMpdf():void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * for generating the HTML head tag content
     *
     * @return string
     */
    protected function generateHead():string
    {
        $head = "
            <head>
                <title>PDF Document</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; }
                    .container { max-width: 700px; margin: auto; padding: 20px; border: 1px solid #ccc; background-color: #fff; }
                    .header, .footer { text-align: center; padding: 10px 0; color: #666; font-size: 0.9em; }
                    .header { border-bottom: 2px solid #ddd; }
                    .title { text-transform: uppercase; font-size: 1.2em; font-weight: bold; color: #444; }
                    .details, .items { width: 100%; margin-top: 20px; }
                    .details th, .items th { background-color: #f7f7f7; color: #555; padding: 8px; text-align: left; font-weight: normal; border: 1px solid #101}
                    .details td, .items td { padding: 8px; border: 1px solid #101 }
                    .details { border-collapse: collapse; }
                    .label { font-weight: bold; color: #333; }
                    .value { color: #000; }
                    .amount { font-size: 1.1em; color: #d9534f; font-weight: bold; }
                    .footer { font-size: 0.8em; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
                </style>
            </head>";
        return $head;
    }

    /**
     * for generating the HTML header tag content
     *
     * @param User $owner the owner of the document
     * @param string $title the title of to be placed inside the header
     * @param string $no the document number
     * @param DateTime|null $date the date for the invoice
     * @return string
     */
    protected function generateMastHead(User $owner, string $title="", string $no="", ?DateTime $date=null):string
    {
        $logoUrl = $this->settings->getDetails()->machine->url.Functions::IMAGE_DIRECTORY.Functions::LOGO;
        $appSettings = AppSettings::getSettingsValue(['name', 'address', 'emails', 'phones', 'website']);
        $logoUrl = $logoUrl ? $logoUrl :  Functions::getImageUrl() . Functions::LOGO;
        $ownerInfo = $owner->getInfo();
        
        $head = "
            <div class='header'>
                <table style='width:100%;'>
                    <tr>
                        <td style='width:70%; vertical-align:top;'>
                            <span style='font-size:24px;'>{$appSettings['name']}</span><br/>
                            <div style='font-size:12px;'>
                                ".nl2br($appSettings['address'])."<br/>
                                ".($appSettings['emails'] ? "{$appSettings['emails']} <br/>" : "")."
                                ".($appSettings['phones'] ? "{$appSettings['phones']} <br/>" : "")."
                                ".($appSettings['website'] ? $appSettings['website'] : "")."
                            </div>
                        </td>
                        <td style='text-align: right; vertical-align:top;'>
                            <img src='$logoUrl' height=100, width=100/>
                        </td>
                    </tr>
                </table>                
                <div style='text-align:right;'>
                    <span style='text-transform: uppercase; font-weight:bold; font-size:20px;'>$title</span><br/>
                </div>
                <div class='header'>
                <table style='width:100%;'>
                    <tr>
                        <td style='width:50%; vertical-align:top;'>
                            <span style='font-size:24px;'>TO</pan><br/>
                            <span style='font-size:15px;'>{$ownerInfo['profile']['name']}</span><br/>
                            <div style='font-size:12px;'>
                                ".($ownerInfo['profile']['address'] ? nl2br($ownerInfo['profile']['address'])."<br/>" : "")."
                                ".($ownerInfo['profile']['emails'] ? implode(", ", json_decode($ownerInfo['profile']['emails'], true))."<br/>" : "")."
                                ".($ownerInfo['profile']['phones'] ? implode(", ", json_decode($ownerInfo['profile']['phones'], true))."<br/>" : "")."
                            </div>                            
                        </td>
                        <td style='text-align: right; vertical-align:top;'>
                            $title No: $no<br/>
                            ".($date ? $date->format('jS F Y') : (new DateTime())->format('jS F Y'))."
                        </td>
                    </tr>
                </table>
            </div>";
        return $head;
    }

    /**
     * for generating the HTML footer tag content
     *
     * @param User|null $creator the creator of the PDF document
     * @return string
     */
    protected function generateFooter(User|null $creator=null):string
    {
        $generator = "";
        if ($creator) {
            $generator = "generated by {$creator->getInfo()['profile']['name']} - ({$creator->getInfo()['profile']['profile_no']}) ";
        }

        $footer = "
            <!-- Footer -->
            <div class='footer'>Generated on " . date('F j, Y, g:i a') . " $generator</div>";

        return $footer;
    }

    /**
     * for generating the PDF document
     *
     * @param string $filename the filename of the pdf file
     * @param string $body the body of the pdf file
     * @param User $owner the owner of the pdf file
     * @param string $title the title of the pdf file
     * @param string $no the document number
     * @param User|null $creator the creator of the pdf
     * @param string $format the generation format browser, download, save or string
     * @return string|null string if generation formation is string
     */
    public function generate(
        string $filename,
        string $body,
        User $owner,
        string $title = "",
        string $no = "",
        User|null $creator = null,
        string $format = Pdf::FORMATS['browser'],
        ?DateTime $date = null
    ): string|null {
        $this->requireMpdf();
        $mpdf = new \Mpdf\Mpdf();

        $html = "
            <html>
                {$this->generateHead()}
                <body>
                    <div class='container'>
                        {$this->generateMastHead($owner, $title, $no, $date)}
                        $body
                        {$this->generateFooter($creator)}
                    </div>
                </body>
            </html>";
        $mpdf->WriteHTML($html);

        $formats = [
            self::FORMATS['browser'] => "I",
            self::FORMATS['download'] => "D",
            self::FORMATS['save'] => "F",
            self::FORMATS['string'] => "S"
        ];
        $format = $formats[$format];
    
        if ($format === "S") {
            return $mpdf->Output($filename, $format);
        } else {
            $mpdf->Output($filename, $format);
            return null;
        }
    }

}
