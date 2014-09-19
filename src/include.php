<?php
/**
 * @package   plg_content_osyoutube
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2013-2014 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

define('OSYOUTUBE_PLUGIN_PATH', JPATH_SITE . '/plugins/content/osyoutube');

// Alledia Library
if (!defined('ALLEDIA_LOADED')) {
    $allediaLibraryPath = JPATH_SITE . '/libraries/alledia/include.php';
    if (!file_exists($allediaLibraryPath)) {
        throw new Exception('Alledia library not found', 1);
    } else {
        require_once $allediaLibraryPath;
    }
}

// Detect Pro Code
$proLibraryPath = OSYOUTUBE_PLUGIN_PATH . '/library/pro/include.php';
define('OSYOUTUBE_PRO', file_exists($proLibraryPath));

if (OSYOUTUBE_PRO) {
    require_once $proLibraryPath;
}
