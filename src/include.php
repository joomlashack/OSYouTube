<?php
/**
 * @package   OSYouTube
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2021-2022 Joomlashack.com. All rights reserved
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of OSYouTube.
 *
 * OSYouTube is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * OSYouTube is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSYouTube.  If not, see <https://www.gnu.org/licenses/>.
 */

use Alledia\Framework\AutoLoader;

defined('_JEXEC') or die();

if (!defined('ALLEDIA_FRAMEWORK_LOADED')) {
    $allediaFrameworkPath = JPATH_SITE . '/libraries/allediaframework/include.php';

    if (is_file($allediaFrameworkPath)) {
        require_once $allediaFrameworkPath;
    }
}

if (!defined('OSYOUTUBE_LOADED')) {
    define('OSYOUTUBE_LOADED', true);

    AutoLoader::register('Alledia\\OSYouTube', __DIR__ . '/library');
}
