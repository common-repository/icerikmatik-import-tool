<?php
/**
 * @package Icerikmatik
 * @version 1.4
 */
/*
Plugin Name: İçerikmatik Import Tool
Plugin URI: http://icerikmatik.com
Description: İçerikmatik post importer tool
Author: Prev Content Solutions
Version: 1.4
Author URI: http://prev.com.tr/
*/

if (!defined('ABSPATH') || !function_exists( 'add_action' ) ) {
	echo 'Called directly';
	exit;
}

ob_start();

if (!session_id())
    session_start();

define('ICERIKMATIK_INIT', true);
define('ICERIKMATIK_VERSION', '1.4');
define('ICERIKMATIK_ABSPATH', __DIR__);

if (is_admin()) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'class.plugin.php';
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'class.api.php';

    $icerikmatik = new IcerikmatikPlugin;

}
