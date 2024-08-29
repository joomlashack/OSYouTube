<?php
/**
 * @package   OSYouTube
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2024 Joomlashack.com. All rights reserved
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

namespace Alledia\OSYouTube;

use Alledia\Framework\Joomla\Extension\AbstractPlugin;
use Alledia\OSYouTube\Free\Methods;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();

// phpcs:enable PSR1.Files.SideEffects

class BasePlugin extends AbstractPlugin
{
    /**
     * @inheritdoc
     * @var CMSApplication
     */
    protected $app = null;

    /**
     * @var string
     */
    protected $namespace = 'OSYouTube';

    /**
     * @var AbstractMethods
     */
    protected $methods = null;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __construct($subject, array $config = [])
    {
        parent::__construct($subject, $config);

        $this->init();

        $baseMethods = '\\Alledia\\OSYouTube\\%s\\Methods';

        $proMethods = sprintf($baseMethods, 'Pro');
        if (class_exists($proMethods)) {
            $this->methods = new $proMethods($this);
        } else {
            $this->methods = new Methods($this);
        }
    }

    /**
     * @param string   $context
     * @param object   $article
     * @param Registry $params
     * @param int      $page
     *
     * @return bool
     */
    public function onContentPrepare($context, $article, $params, $page = 0)
    {
        return $this->methods->onContentPrepare($context, $article, $params, $page);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function onAfterRender()
    {
        $this->methods->onAfterRender();
    }
}
