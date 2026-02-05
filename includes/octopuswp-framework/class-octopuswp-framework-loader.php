<?php
if(!defined('ABSPATH')) exit;

if(!defined('OctopusWP_Framework_Path'))
    define('OctopusWP_Framework_Path', plugin_dir_path(__FILE__));

if(!defined('OctopusWP_Framework_Log_Dir'))
    define('OctopusWP_Framework_Log_Dir', 'octopuswp-logs');

if(!defined('OctopusWP_Framework_Site_Url_Source')) {
    define('OctopusWP_Framework_Site_Url_Source', 'default');
}

$dir = plugin_dir_path(__FILE__);

require_once $dir . 'trait-octopuswp-plugin-prefix-accessible.php';
require_once $dir . 'trait-octopuswp-loggable.php';

require_once $dir . 'trait-octopuswp-plugin-instance.php';
require_once $dir . 'trait-octopuswp-wc-settings-accessible.php';
require_once $dir . 'trait-octopuswp-remote-api-accssible.php';

require_once $dir . 'class-octopuswp-framework.php';
require_once $dir . 'class-octopuswp-framework-backend.php';
require_once $dir . 'class-octopuswp-logger.php';
require_once $dir . 'class-octopuswp-wc-order-compatible.php';
require_once $dir . 'class-octopuswp-csv-downloader.php';
require_once $dir . 'class-octopuswp-plugin-updater.php';
require_once $dir . 'class-octopuswp-plugin.php';


