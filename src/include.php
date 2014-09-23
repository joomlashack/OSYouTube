<?php
/**
 * @package   OSYouTube
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2013-2014 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Alledia\Factory;

defined('_JEXEC') or die();

define('OSYOUTUBE_PLUGIN_PATH', __DIR__);

// Alledia Library
if (!defined('ALLEDIA_LOADED')) {
    $allediaLibraryPath = JPATH_SITE . '/libraries/alledia/include.php';

    if (!file_exists($allediaLibraryPath)) {
        throw new Exception('Alledia library not found');
    }

    require_once $allediaLibraryPath;
}
