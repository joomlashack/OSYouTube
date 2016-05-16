<?php
/**
 * @package   OSYouTube
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2016 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Alledia\Framework\Joomla\Extension\AbstractPlugin;

defined('_JEXEC') or die();

require_once 'include.php';

if (defined('ALLEDIA_FRAMEWORK_LOADED')) {
    /**
     * OSYouTube Content Plugin
     *
     */
    class PlgContentOSYoutube extends AbstractPlugin
    {
        protected $namespace = 'OSYouTube';

        protected $tokenIgnore = '::ignore::';

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
            if (JString::strpos($article->text, '://www.youtube.com/watch') === false) {
                return true;
            }

            $this->init();


            // Hey, the order of these expressions matters!
            $regex = array(
                '#(?:<a.*?href=["\'](?:https?://(?:www\.)?youtube.com/watch\?v=([^\'"\#]+)(\#[^\'"\#]*)?[\'"][^>]*>(.+)?(?:</a>)))#',
                '#(?<!' . $this->tokenIgnore . ')https?://(?:www\.)?youtube.com/watch\?v=([a-zA-Z0-9-_&;=]+)(\#[a-zA-Z0-9-_&;=]*)?#'
            );

            $ignoreHtmlLinks = $this->params->get('ignore_html_links', 0);
            foreach ($regex as $i => $r) {
                if (preg_match_all($r, $article->text, $matches)) {
                    foreach ($matches[0] as $k => $source) {
                        if ($i == 0 && $ignoreHtmlLinks) {
                            // Attach the token to ignore the URL
                            $this->addTokenToIgnoreURL($source, $article->text);
                        } else {
                            // Parse the URL
                            $urlHash   = @$matches[2][$k];
                            $videoCode = $matches[1][$k];
                            $embedCode = $this->youtubeCodeEmbed($videoCode, $urlHash);

                            if ($ignoreHtmlLinks) {
                                // Must pay attention to ignored links here
                                $matchString = '#(?<!' . $this->tokenIgnore . ')' . preg_quote($source, '#') . '#';

                                $article->text = preg_replace($matchString, $embedCode, $article->text);

                            } else {
                                // Don't care, do the faster replace
                                $article->text = str_replace($source, $embedCode, $article->text);
                            }
                        }
                    }
                }
            }

            // Remove all "ignore" tokens from the text
            if ($ignoreHtmlLinks) {
                $this->removeTokensToIgnoreURL($article->text);
            }

            return true;
        }

        protected function addTokenToIgnoreURL($tag, &$text)
        {
            $newTag = preg_replace('#(https?://)#i', $this->tokenIgnore . '$1', $tag);
            $text   = str_replace($tag, $newTag, $text);
        }

        protected function removeTokensToIgnoreURL(&$text)
        {
            $text = str_replace($this->tokenIgnore, '', $text);
        }

        protected function youtubeCodeEmbed($videoCode, $urlHash = null)
        {
            $output = '';
            $params = $this->params;

            $width      = $params->get('width', 425);
            $height     = $params->get('height', 344);
            $responsive = $params->get('responsive', 1);

            if ($responsive) {
                $doc = JFactory::getDocument();
                $doc->addStyleSheet(JURI::base() . "plugins/content/osyoutube/style.css");
                $output .= '<div class="video-responsive">';
            }

            $query     = explode('&', htmlspecialchars_decode($videoCode));
            $videoCode = array_shift($query);

            $attribs = array(
                'width'       => $width,
                'height'      => $height,
                'frameborder' => '0'
            );

            if ($this->isPro()) {
                $attribs['src'] = Alledia\OSYouTube\Pro\Embed::getUrl($params, $videoCode, $query, $urlHash);
            } else {
                $attribs['src'] = Alledia\OSYouTube\Free\Embed::getUrl($params, $videoCode, $query, $urlHash);
            }

            $output .= '<iframe ' . JArrayHelper::toString($attribs) . ' allowfullscreen></iframe>';

            if ($responsive) {
                $output .= '</div>';
            }

            return $output;
        }
    }
}
