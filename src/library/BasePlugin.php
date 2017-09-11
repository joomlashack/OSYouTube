<?php
/**
 * @package   OSYouTube
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016 Joomlashack.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace Alledia\OSYouTube;

use Alledia\Framework\Joomla\Extension\AbstractPlugin;
use Alledia\OSYouTube\Free\Methods;

defined('_JEXEC') or die();

class BasePlugin extends AbstractPlugin
{
    /**
     * @var string
     */
    protected $namespace = 'OSYouTube';

    /**
     * @var AbstractMethods
     */
    protected $methods = null;

    public function __construct($subject, array $config = array())
    {
        parent::__construct($subject, $config);

        $this->init();

        $this->methods = new Methods($this);
    }

    /**
     * @param string $context
     * @param object $article
     * @param object $params
     * @param int    $page
     *
     * @return bool
     */
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        return $this->methods->onContentPrepare($context, $article, $params, $page);
    }
}
