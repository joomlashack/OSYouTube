<?php
/**
 * @package   OSYouTube
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2016-2018 Joomlashack.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace Alledia\OSYouTube;

use Alledia\Framework\Joomla\Extension\AbstractPlugin;
use Alledia\OSYouTube\Free\Methods;
use Exception;
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;

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

    /**
     * BasePlugin constructor.
     *
     * @param Dispatcher $subject
     * @param array      $config
     */
    public function __construct($subject, array $config = array())
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
    public function onContentPrepare($context, &$article, &$params, $page = 0)
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
