<?php
/**
 * @package   OSYouTube
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016 Joomlashack.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

// Alledia Framework
if (!defined('ALLEDIA_FRAMEWORK_LOADED')) {
    $allediaFrameworkPath = JPATH_SITE . '/libraries/allediaframework/include.php';

    if (file_exists($allediaFrameworkPath)) {
        require_once $allediaFrameworkPath;
    }
}

if (defined('ALLEDIA_FRAMEWORK_LOADED')) {
    \Alledia\Framework\AutoLoader::register('Alledia\\OSYouTube', __DIR__ . '/library');

    class PlgContentOSYoutube extends \Alledia\OSYouTube\BasePlugin
    {

    }

} else {
    JFactory::getApplication()
        ->enqueueMessage('[OSYouTube] Alledia framework not found', 'error');
}
