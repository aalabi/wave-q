<?php

/**
 * AbstractWebPage
 *
 * An abstract class for handling html web page creation
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
*/

abstract class AbstractWebPage
{
    /** @var DbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var Query an instance of Query  */
    protected Query $query;

    /** @var int logger.id column from logger table*/
    protected int $loggerId;

    /** @var User an instance of the user accessing the webpage*/
    protected User $user;

    /** @var string the title of the webpage*/
    protected string $title;

    /** @var Settings an instance of Settings*/
    protected Settings $settings;

    /** @var string response card displays in positive */
    public const RESPONSE_POSITIVE = "success";

    /** @var string response card displays in negative */
    public const RESPONSE_NEGATIVE = "danger";

    /** @var string name for session variable that holds response after form processing */
    public const RESPONSE_SESSION = "responseData_";

    /**
     * instantiation of AbstractWebPage
     *
     * @param string $title the title of the webpage
     * @param  User $user the login user accessing this webpage
     */
    public function __construct(string $title, ?User $user = null)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
        $this->settings = new Settings(SETTING_FILE);
        $this->title = $title;
        if ($user) {
            $this->loggerId = $user->getInfo()['logger']['id'];
            $this->user = $user;
        }
    }

    /**
     * for creating the head tag on the web page
     *
     * @param array $cssFiles an array of CSS files
     * @param array $jsFiles an array of JS files
     * @param array $metaTags an array of meta tags to be inserted into the head tag
     * @return string
     */
    public function head(array $cssFiles=[], array $jsFiles=[], array $metaTags=[]):string
    {
        $styles = "";
        $scripts = "";
        $metas = "";
        $Settings = new Settings(SETTING_FILE, true);
        $time = $Settings->getAllDetails()->mode == "development" ? "?ver=" . time() : "";

        if ($cssFiles) {
            foreach ($cssFiles as $aCssFile) {
                $styles .= "<link rel='stylesheet' href='{$aCssFile}{$time}'>";
            }
        }
        if ($jsFiles) {
            foreach ($jsFiles as $aJsFile) {
                $scripts .= "<script src='{$aJsFile}{$time}{$time}'></script>";
            }
        }
        if ($metaTags) {
            foreach ($metaTags as $aMetaTag) {
                $metas .= $aMetaTag;
            }
        }

        $head = "
			<!DOCTYPE html>
			<html lang='en'>
			<head>
				<!-- Required meta tags -->
				<meta charset='utf-8'>
				<meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
				$metas
				<title>$this->title</title>
				<link rel='icon' href='" .Functions::getImageUrl().Functions::FAVICON. "' type='image/x-icon' />
				$styles
				$scripts
				<!--
				Developed by Alabian Solutions Limited
				Phone: 08034265103
				Email: info@alabiansolutions.com
				Lead Developer: Alabi A. (facebook.com/alabi.adebayo)
				-->
			</head>
		";
        return $head;
    }

    /**
     * for creating js files to be added into the web page just before close of body tag
     *
     * @param array $jsFiles an array of JS files
     * @param bool $isOutsidePage true if app homepage
     * @return string an html tag of js files
    */
    public function footerFiles(array $jsFiles=[], bool $isOutsidePage = false):string
    {
        $Settings = new Settings(SETTING_FILE, true);
        $time = $Settings->getAllDetails()->mode == "development" ? "?ver=" . time() : "";

        $scripts = "";
        if ($jsFiles) {
            foreach ($jsFiles as $aJsFile) {
                $scripts .= "<script src='{$aJsFile}{$time}'></script>";
            }
        }
        return $scripts;
    }
    
    /**
     * for the change the title of the webpage
     *
     * @param string $title the title of the webpage
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * store data in the response session variable
     *
     * @param string $title title of the message
     * @param array $message a collection of the exact message been store
     * @param string $status either the message is success or danger in nature
     * @return void
     */
    public static function setResponse(string $title, array $message, string $status = self::RESPONSE_POSITIVE)
    {
        $_SESSION[self::RESPONSE_SESSION] = ['title' => $title, 'messages' => $message, 'status' => $status];
    }

    /**
     * get the data store in the response session variable [title, message, status]
     *
     * @return array
     */
    public static function getResponse(): array
    {
        $response = isset($_SESSION[self::RESPONSE_SESSION]) ? $_SESSION[self::RESPONSE_SESSION] : [];
        unset($_SESSION[self::RESPONSE_SESSION]);
        return $response;
    }

    /**
     * generate the hidden input tag for passage of CSRF token
     *
     * @param string $name the value of the name attribute of the input tag
     * @return string
     */
    public static function getCSRFTokenInputTag(string $name = ""): string
    {
        if (!$name) {
            $name = Functions::getCsrfTokenSessionName();
        }
        $tag = "<input name='$name' value='" . Functions::getCSRFToken() . "' type='hidden'/>";
        return $tag;
    }
    
    /**
     * get an array of menu in the app
     *
     * @return array an array of the menu items [topMenu=>[subMenu=>link,subMenu=>link],topMenu=>link,...]
    */
    abstract protected function menuList():array;

    /**
     * for creating menu for the app
     *
     * @return string an html tag of the menu to be added into the web page
    */
    abstract public function createMenu():string;

    /**
     * for creating sidebar for the app
     *
     * @param string $activeMenu the menu for the active webpage
     * @param string $activeSubMenu the sub menu for the active webpage
     * @return string an html tag of the sidebar to be added into the web page
    */
    abstract public function createSideBar(string $activePage, ?string $activeSubMenu=null):string;

    /**
     * for creating footer for the app
     *
     * @return string an html tag of the footer to be added into the web page
    */
    abstract public function footer():string;

    /**
     * creation of response message tag
     * @param string $title the title of the response message
     * @param string $message exact response message
     * @param string $status either POSITIVE|postive or NEGATIVE|negative
     * @return string
     */
    abstract public static function responseTag(string $title, string $message, string $status = self::RESPONSE_POSITIVE): string;
}
