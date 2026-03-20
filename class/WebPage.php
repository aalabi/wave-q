<?php

/**
 * WebPage
 *
 * A class for handling html web page creation
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
*/

class WebPageExpection extends Exception
{
}

class WebPage extends AbstractWebPage
{
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
        $settings = $this->settings->getDetails();
        $time = $settings->mode == "development" ? "?ver=" . time() : "";
        $backend = $settings->machine->backend;

        if ($cssFiles) {
            foreach ($cssFiles as $aCssFile) {
                $styles .= "<link rel='stylesheet' href='{$aCssFile}{$time}'>";
            }
        }
        if ($jsFiles) {
            foreach ($jsFiles as $aJsFile) {
                $scripts .= "<script src='{$aJsFile}{$time}'></script>";
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
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <meta name='author' content='Alabi A.'>
				$metas
				<title>$this->title</title>
				<link rel='icon' href='" .Functions::getImageUrl(true).Functions::FAVICON. "' type='image/x-icon' />
                <link rel='stylesheet' href='".Functions::getCssUrl(true)."vendors_css.css{$time}'>
                <link rel='stylesheet' href='".Functions::getCssUrl(true)."style.css{$time}'>
                <link rel='stylesheet' href='".Functions::getCssUrl(true)."skin_color.css{$time}'>
				$styles
				$scripts
				<!--
				Developed by Alabian Solutions Limited
				Phone: 08034265103
	    		Email: info@alabiansolutions.com
				Lead Developer: Alabi A. (facebook.com/alabi.adebayo)
				-->
			</head>";
        return $head;
    }

    /**
     * Get an array of menu items in the app
     *
     * @return array an array of the menu items [top=>[icon=>i, url=>u ,sub=>[sub=>url]/null,...]
     */
    protected function menuList(): array
    {
        $url = $this->settings->getDetails()->machine->url . $this->settings->getDetails()->machine->backend;
        $generalMenu = ["Home", "Profile", "Logout"];
        $menu = [
            'Home' => ['icon' => 'fa fa-home', 'url' => $url . "home", 'sub' => null, 'display' => 'Home'],
            'Revenue' => [
                'icon' => 'fa fa-money',
                'url' => null,
                'sub' => [
                    'Revenue-Sales' => ['url' => $url . "revenue-sales", 'display' => 'Sales'],
                    'Revenue-Invoice' => ['url' => $url . "revenue-invoice", 'display' => 'Invoice'],
                    'Revenue-Profoma-Invoice' => ['url' => $url . "revenue-profoma-invoice", 'display' => 'Profoma Invoice'],
                    'Revenue-Invoice-Register' => ['url' => $url . "revenue-invoice-register", 'display' => 'Invoice Register'],
                    'Revenue-Debtor-Register' => ['url' => $url . "revenue-debtor-register", 'display' => 'Debtor Register'],
                    'Revenue-Refund' => ['url' => $url . "revenue-refund", 'display' => 'Refund'],
                ],
                'display' => 'Revenue'
            ],
            'Services' => [
                'icon' => 'fa fa-file',
                'url' => null,
                'sub' => [
                    'Services-Find' => ['url' => $url . "services-find", 'display' => 'Find'],
                    'Services' => ['url' => $url . "services", 'display' => 'Services'],
                    'Services-Category' => ['url' => $url . "services-category", 'display' => 'Category'],
                ],
                'display' => 'Services'
            ],
            'Products' => [
                'icon' => 'fa fa-shopping-basket',
                'url' => null,
                'sub' => [
                    'Products-Find' => ['url' => $url . "products-find", 'display' => 'Find'],
                    'Products' => ['url' => $url . "products", 'display' => 'Products'],
                    'Products-Category' => ['url' => $url . "products-category", 'display' => 'Category'],
                ],
                'display' => 'Products'
            ],
            'Expense' => [
                'icon' => 'fa fa-arrows-alt',
                'url' => null,
                'sub' => [
                    'Expense-Creation-Page' => ['url' => $url . "expense-creation-page", 'display' => 'Create'],
                    'Expense-Find' => ['url' => $url . "expense-find", 'display' => 'Find'],
                    'Expense' => ['url' => $url . "expense", 'display' => 'Expense'],
                    'Expense-Payment' => ['url' => $url . "expense-payment", 'display' => 'Payment'],
                    'Expense-Item' => ['url' => $url . "expense-item", 'display' => 'Item'],
                    'Expense-Approval-List' => ['url' => $url . "expense-approval-list", 'display' => 'Approval'],
                ],
                'display' => 'Expense'
            ],
            'Ledgers' => [
                'icon' => 'fa fa-file-text',
                'url' => null,
                'sub' => [
                    'Ledgers-Post' => ['url' => $url . "ledgers-post", 'display' => 'Post'],
                    'Ledgers-Find' => ['url' => $url . "ledgers-find", 'display' => 'Find'],
                    'Ledgers' => ['url' => $url . "ledgers", 'display' => 'Ledgers'],
                    'Ledgers-Bank' => ['url' => $url . "ledgers-bank", 'display' => 'Banks'],
                    'Ledgers-InterBank-Transfer' => ['url' => $url . "ledgers-interbank-transfer", 'display' => 'Inter Bank Transfer'],
                ],
                'display' => 'Ledgers'
            ],
            'Collections' => [
                'icon' => 'fa fa-list',
                'url' => null,
                'sub' => [
                    'tax' => ['url' => $url . "tax", 'display' => 'Tax'],
                ],
                'display' => 'Collections'
            ],
            'Report' => [
                'icon' => 'fa fa-briefcase',
                'url' => null,
                'sub' => [
                    'report-ledgers-transaction' => ['url' => $url . "report-ledgers-transaction", 'display' => "Ledger's Transaction"],
                    'report-transaction-log' => ['url' => $url . "report-transaction-log", 'display' => "Transaction Log"],
                    'report-trial-balance' => ['url' => $url . "report-trial-balance", 'display' => "Trial Balance"],
                ],
                'display' => 'Report'
            ],
            'Fixed Asset' => [
                'icon' => 'fa fa-car',
                'url' => null,
                'sub' => [
                    'Fixed-Asset-Register' => ['url' => $url . "fixed-asset-register", 'display' => 'Register'],
                    'Fixed-Asset-Booking' => ['url' => $url . "fixed-asset-booking", 'display' => 'Booking'],
                    'Fixed-Asset-Purchase' => ['url' => $url . "fixed-asset-purchase", 'display' => 'Purchase'],
                    'Fixed-Asset-Payment' => ['url' => $url . "fixed-asset-payment", 'display' => 'Payment'],
                    'Fixed-Asset-Purchase-Item' => ['url' => $url . "fixed-asset-purchase-item", 'display' => 'Purchasable Item'],
                    'Fixed-Asset-Category' => ['url' => $url . "fixed-asset-category", 'display' => 'Category'],
                ],
                'display' => 'Fixed Asset'
            ],
            'Users' => [
                'icon' => 'fa fa-users',
                'url' => null,
                'sub' => [
                    'Vendors' => ['url' => $url . "vendors", 'display' => 'Vendors'],
                    'Customers' => ['url' => $url . "customers", 'display' => 'Customers'],
                    'Admins' => ['url' => $url . "admins", 'display' => 'Admins'],
                    'Staffs' => ['url' => $url . "staffs", 'display' => 'Staffs'],
                ],
                'display' => 'Users'
            ],
            'Payroll' => [
                'icon' => 'fa fa-calculator',
                'url' => null,
                'sub' => [
                    'Payroll' => ['url' => $url . "payroll", 'display' => 'Payroll'],
                    'Payroll-Grades' => ['url' => $url . "payroll-grades", 'display' => 'Grades'],
                    'Payroll-Templates' => ['url' => $url . "payroll-templates", 'display' => 'Templates'],
                    'Payroll-Deduction' => ['url' => $url . "payroll-deduction", 'display' => 'Deduction'],
                ],
                'display' => 'Payroll'
            ],
            'Profile' => ['icon' => 'fa fa-vcard-o', 'url' => $url . "profile", 'sub' => null, 'display' => 'Profile'],
            'App-Settings' => [
                'icon' => 'fa fa-cogs', 
                'url' => $url . "app-settings", 
                'sub' => [
                    'App-Settings' => ['url' => $url . "app-settings", 'display' => 'App Settings'],
                    'Tasks' => ['url' => $url . "tasks", 'display' => 'Tasks'],
                    'Groups' => ['url' => $url . "groups", 'display' => 'Groups']
                ],
                'display' => 'App Settings'],
            'Logout' => ['icon' => 'fa fa-sign-out', 'url' => $url . "logout", 'sub' => null, 'display' => 'Logout']
        ];

        $myWebPages = [];
        if ($tasks = Task::getDoerTasks(Task::DOER['profile'], $this->user->getInfo()['profile']['id'])) {
            foreach ($tasks as $aRow) {
                $myWebPages[$aRow['name']] = $aRow['id'];
            }
        }

        foreach ($menu as $menuName => &$menuContent) {
            if ($menuContent['sub'] === null) {
                if (!isset($myWebPages[strtolower($menuName)]) && !in_array($menuName, $generalMenu)) {
                    unset($menu[$menuName]);
                }
            } else {
                foreach ($menuContent['sub'] as $subMenuName => $subMenuUrl) {
                    if (!isset($myWebPages[strtolower($subMenuName)])) {
                        unset($menuContent['sub'][$subMenuName]);
                    }
                }

                if (empty($menuContent['sub'])) {
                    unset($menu[$menuName]);
                }
            }
        }
        unset($menuContent);

        return $menu;
    }

    /**
     * for creating menu for the app
     *
     * @return string an html tag of the menu to be added into the web page
     */
    public function createMenu():string
    {
        $tag = "";

        return $tag;
    }

    /**
     * for creating tags for the masthead of the app
     *
     * @return string an html tag of the masthead to be added into the web page
     */
    public function mastHead():string
    {
        $tag = "";
        $backend =  $this->settings->getDetails()->machine->url.$this->settings->getDetails()->machine->backend;
        $tag = "
            <header class='main-header'>
                <div class='d-flex align-items-center logo-box justify-content-center'>
                    <!-- Logo -->
                    <a href='{$backend}home' class='logo'>
                        <!-- logo-->
                        <div class='logo-mini'>
                            <span class='light-logo'><img src='".Functions::getImageUrl(true)."side-logo.png' width='50' alt='logo'></span>
                            <span class='dark-logo'><img src='".Functions::getImageUrl(true)."side-logo.png' width='50' alt='logo'></span>
                        </div>
                        <!-- logo-->
                        <div class='logo-lg' style='height:60px;'> 
                            <span class='light-logo'><img src='".Functions::getImageUrl(true)."side-logo.png' width='50' alt='logo'></span>
                        </div>
                    </a>
                </div>
                <!-- Header Navbar -->
                <nav class='navbar navbar-static-top pl-10'>
                    <!-- Sidebar toggle button-->
                    <div class='app-menu'>
                        <ul class='header-megamenu nav'>
                            <li class='btn-group nav-item'>
                                <a href='#' class='waves-effect waves-light nav-link rounded push-btn' data-toggle='push-menu' role='button'>
                                    <span class='icon-Align-left'><span class='path1'></span><span class='path2'></span><span class='path3'></span></span>
                                </a>
                            </li>                         
                            <li class='btn-group nav-item d-none d-xl-inline-block' >
                                <a href='#' class='waves-effect waves-light nav-link rounded svg-bt-icon' title='' style='width:auto'>                                    
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class='navbar-custom-menu r-side'>
                        <ul class='nav navbar-nav'>
                            <li class='btn-group nav-item d-lg-inline-flex d-none'>                                                          
                            </li>
                            <li class='btn-group nav-item d-lg-inline-flex d-none'>
                                <a href='#' data-provide='fullscreen'
                                    class='waves-effect waves-light nav-link rounded full-screen' title='Full Screen'>
                                    <i class='icon-Expand-arrows'><span class='path1'></span><span class='path2'></span></i>
                                </a>
                            </li>						
                            
                            <!-- User Account-->
                            <li class='dropdown user user-menu'>
                                <a href='#' class='waves-effect waves-light dropdown-toggle' data-toggle='dropdown'
                                    title='User'>
                                    <i class='icon-User'><span class='path1'></span><span class='path2'></span></i>
                                </a>
                                <ul class='dropdown-menu animated flipInX'>
                                    <li class='user-body'>                                        
                                        <div class='dropdown-divider'></div>
                                        <a class='dropdown-item' href='".$backend."logout'>
                                            <i class='ti-lock text-muted mr-2'></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
        ";

        return $tag;
    }

    /**
     * for creating sidebar for the app
     *
     * @param string $activeMenu the menu for the active webpage
     * @param string $activeSubMenu the sub menu for the active webpage
     * @return string an html tag of the sidebar to be added into the web page
    */
    public function createSideBar(string $activeMenu, string|null $activeSubMenu=null):string
    {

        $tag = "
            <aside class='main-sidebar'>
                <!-- sidebar-->
                <section class='sidebar'>                    
                    <!-- sidebar menu-->
                    <ul class='sidebar-menu' data-widget='tree'>
                        <li class='header'>Menu </li>";
        foreach ($this->menuList() as $mainMenu=>$mainMenuContent) {
            $active = strtolower($activeMenu) == strtolower($mainMenu) ? 'active' : '';
            if ($mainMenuContent['sub']) {
                $tag .="                    
                    <li class='treeview $active'>
                        <a href='#'>
                            <i class='{$mainMenuContent['icon']}'><span class='path1'></span><span class='path2'></span></i>
                            <span>{$mainMenuContent['display']}</span>
                            <span class='pull-right-container'>
                                <i class='fa fa-angle-right pull-right'></i>
                            </span>
                        </a>
                        <ul class='treeview-menu'>";
                $active = "";
                foreach ($mainMenuContent['sub'] as $subMenu => $subMenuContent) {
                    $active = $activeSubMenu && strtolower($activeSubMenu) == strtolower($subMenu) ? 'active' : '';
                    $tag .= "<li class='$active'>
                            <a href='{$subMenuContent['url']}'><i class='icon-Commit'><span class='path1'></span><span class='path2'></span></i>{$subMenuContent['display']}</a>
                        </li>";
                    $active = "";
                }
                        
                $tag .="</ul> </li>";
            } else {
                $tag .= "
                    <li class='$active'>
                        <a href='{$mainMenuContent['url']}'>
                            <i class='{$mainMenuContent['icon']}'><span class='path1'></span><span class='path2'></span></i>
                            <span>{$mainMenuContent['display']}</span>
                        </a>
                    </li>
                ";
            }
            $active = "";
        }
        
        $tag .=  "</ul> </section> </aside>";

        return $tag;
    }

    /**
     * for creating tags for the footer credit section for the app
     *
     * @return string the tag for the footer credit section
     */
    public function footerCredit():string
    {
        $tag = "
            <footer class='main-footer'>
                <div class='pull-right d-none d-sm-inline-block'>
                    <ul class='nav nav-primary nav-dotted nav-dot-separated justify-content-center justify-content-md-end'>
                        <li class='nav-item'>
                            <a class='nav-link' href='javascript:void(0)'></a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='#'></a>
                        </li>
                    </ul>
                </div>
                &copy; ".date("Y")." <a href=''>".$this->settings->getDetails()->sitename." Team</a>. All Rights Reserved.
            </footer>
        ";
        return $tag;
    }

    /**
     * for creating tags for the chat section for the app
     *
     * @return string the tag for the chat section
     */
    public function chatTag():string
    {
        $tag = "
            <div id='chat-box-body'>
                <div id='chat-circle' class='waves-effect waves-circle btn btn-circle btn-lg btn-warning l-h-70'>
                    <div id='chat-overlay'></div>
                    <span class='icon-Group-chat font-size-30'><span class='path1'></span><span class='path2'></span></span>
                </div>

                <div class='chat-box'>
                    <div class='chat-box-header p-15 d-flex justify-content-between align-items-center'>
                        <div class='btn-group'>
                            <button
                                class='waves-effect waves-circle btn btn-circle btn-primary-light h-40 w-40 rounded-circle l-h-45'
                                type='button' data-toggle='dropdown'>
                                <span class='icon-Add-user font-size-22'><span class='path1'></span><span
                                        class='path2'></span></span>
                            </button>
                            <div class='dropdown-menu min-w-200'>						
                                <a class='dropdown-item font-size-16' href='#'>
                                    <span class='icon-User mr-15'><span class='path1'></span><span
                                            class='path2'></span><span class='path3'></span><span class='path4'></span></span>
                                    User</a>
                                <a class='dropdown-item font-size-16' href='#'>
                                    <span class='icon-Group mr-15'><span class='path1'></span><span class='path2'></span></span>
                                    All Users</a>						
                            </div>
                        </div>
                        <div class='text-center flex-grow-1'>
                            <div class='text-dark font-size-18'>Mayra Sibley</div>
                            <div>
                                <span class='badge badge-sm badge-dot badge-primary'></span>
                                <span class='text-muted font-size-12'>Active</span>
                            </div>
                        </div>
                        <div class='chat-box-toggle'>
                            <button id='chat-box-toggle'
                                class='waves-effect waves-circle btn btn-circle btn-danger-light h-40 w-40 rounded-circle l-h-45'
                                type='button'>
                                <span class='icon-Close font-size-22'><span class='path1'></span><span
                                        class='path2'></span></span>
                            </button>
                        </div>
                    </div>
                    <div class='chat-box-body'>
                        <div class='chat-box-overlay'>
                        </div>
                        <div class='chat-logs'>
                            <div class='chat-msg user'>
                                <div class='d-flex align-items-center'>
                                    <span class='msg-avatar'>
                                        <img src='../images/avatar/2.jpg' class='avatar avatar-lg'>
                                    </span>
                                    <div class='mx-10'>
                                        <a href='#' class='text-dark hover-primary font-weight-bold'>Mayra Sibley</a>
                                        <p class='text-muted font-size-12 mb-0'>2 Hours</p>
                                    </div>
                                </div>
                                <div class='cm-msg-text'>
                                    Hi there, I'm Jesse and you?
                                </div>
                            </div>
                            <div class='chat-msg self'>
                                <div class='d-flex align-items-center justify-content-end'>
                                    <div class='mx-10'>
                                        <a href='#' class='text-dark hover-primary font-weight-bold'>You</a>
                                        <p class='text-muted font-size-12 mb-0'>3 minutes</p>
                                    </div>
                                    <span class='msg-avatar'>
                                        <img src='../images/avatar/3.jpg' class='avatar avatar-lg'>
                                    </span>
                                </div>
                                <div class='cm-msg-text'>
                                    My name is Anne Clarc.
                                </div>
                            </div>
                            <div class='chat-msg user'>
                                <div class='d-flex align-items-center'>
                                    <span class='msg-avatar'>
                                        <img src='../images/avatar/2.jpg' class='avatar avatar-lg'>
                                    </span>
                                    <div class='mx-10'>
                                        <a href='#' class='text-dark hover-primary font-weight-bold'>Mayra Sibley</a>
                                        <p class='text-muted font-size-12 mb-0'>40 seconds</p>
                                    </div>
                                </div>
                                <div class='cm-msg-text'>
                                    Nice to meet you Anne.<br>How can i help you?
                                </div>
                            </div>
                        </div>
                        <!--chat-log -->
                    </div>
                    <div class='chat-input'>
                        <form>
                            <input type='text' id='chat-input' placeholder='Send a message...' />
                            <button type='submit' class='chat-submit' id='chat-submit'>
                                <span class='icon-Send font-size-22'></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        ";
        $tag = "";
        return $tag;
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
        $time = $this->settings->getAllDetails()->mode == "development" ? "?ver=" . time() : "";
        $vendorFiles = $isOutsidePage ? "" : "<script src='".Functions::getJsUrl(true)."template.js$time'></script>";
        $scripts = "
            <script src='".Functions::getJsUrl(true)."vendors.min.js$time'></script>
            <script src='".Functions::getAssetUrl(true)."icons/feather-icons/feather.min.js$time'></script>            
            $vendorFiles   
        ";
        if ($jsFiles) {
            foreach ($jsFiles as $aJsFile) {
                $scripts .= "<script src='{$aJsFile}{$time}'></script>";
            }
        }
        return $scripts;
    }

    /**
     * for creating footer for the app
     *
     * @return string an html tag of the footer to be added into the web page
     */
    public function footer():string
    {
        $tag = "";

        return $tag;
    }

    /**
     * create of response message tag
     * @param string $title the title of the response message
     * @param string $message exact response message
     * @param string $status either POSITIVE|postive or NEGATIVE|negative
     * @return string
     */
    public static function responseTag(string $title, string $message, string $status = parent::RESPONSE_POSITIVE): string
    {
        $tag = "
            <div class=''>
                <div class=''>
                    <h3>$title</h3>                    
                    <div class='clearfix'></div>
                </div>
                <div class='bs-example-popovers'>
                    <div class='alert alert-$status alert-dismissible ' role='alert'>
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span>
                        </button>
                        $message
                    </div>                    
                </div>
            </div>
        ";
        return $tag;
    }
}
