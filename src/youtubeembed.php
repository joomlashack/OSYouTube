<?php
/**
 * @package   plg_content_youtubeembed
 * @contact   www.ostraining.com, support@ostraining.com
 * @copyright 2013 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

/**
 * YouTube Video Embedder Content Plugin
 *
 */
class plgContentYoutubeEmbed extends JPlugin
{
    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);

        $lang = JFactory::getLanguage();
        $lang->load('plg_content_youtubeembed.sys', __DIR__);
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
        if (JString::strpos($article->text, 'http://www.youtube.com/') === false) {
            return true;
        }

        $bbcode = array(
            '|(http://www.youtube.com/watch\?v=([a-zA-Z0-9_-]+))|e' => '$this->youtubeCodeEmbed("\2")', //http
            '|(https://www.youtube.com/watch\?v=([a-zA-Z0-9_-]+))|e' => '$this->youtubeCodeEmbed("\2")'  //https
        );
        $article->text = preg_replace(array_keys($bbcode), array_values($bbcode), $article->text);

        return true;
    }

    protected function youtubeCodeEmbed($vCode)
    {
        $output = '';
        $params = $this->params;

        $width      = $params->get('width', 425);
        $height     = $params->get('height', 344);
        $responsive = $params->get('responsive', 1);

        if ($responsive) {
            $doc = JFactory::getDocument();
            $doc->addStyleSheet(JURI::base() . "plugins/content/youtubeembed/style.css");
            $output .= '<div class="video-responsive">';
        }

        $output .= '<iframe width="'
            . $width . '" height="'
            . $height . '" src="//www.youtube.com/embed/'
            . $vCode . '" frameborder="0" allowfullscreen></iframe>';

        if ($responsive) {
            $output .= '</div>';
        }

        return $output;
    }
}
