<?php
/**
 * @package    OSYouTube
 * @contact    www.joomlashack.com, help@joomlashack.com
 * @copyright  2017 Open Source Training, LLC. All rights reserved
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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
 * along with OSYouTube.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Alledia\OSYouTube;

use Alledia\Framework\Joomla\Extension\AbstractPlugin;
use JHtml;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die();

abstract class AbstractMethods
{
    /**
     * @var string
     */
    protected $tokenIgnore = '::ignore::';

    /**
     * @var Registry
     */
    protected $params = null;

    public function __construct(AbstractPlugin $parent)
    {
        $this->params = $parent->params;
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
        if (StringHelper::strpos($article->text, '://www.youtube.com/watch') === false) {
            return true;
        }

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
            JHtml::_('stylesheet', 'plugins/content/osyoutube/style.css');
            $output .= '<div class="video-responsive">';
        }

        $query     = explode('&', htmlspecialchars_decode($videoCode));
        $videoCode = array_shift($query);

        $attribs = array(
            'id'          => 'youtube_' . $videoCode,
            'width'       => $width,
            'height'      => $height,
            'frameborder' => '0',
            'src'         => $this->getUrl($params, $videoCode, $query, $urlHash)
        );

        $output .= '<iframe ' . ArrayHelper::toString($attribs) . ' allowfullscreen></iframe>';

        if ($responsive) {
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * @param Registry $params
     * @param array    $query
     * @param string   $videoCode
     *
     * @return array
     */
    protected static function buildUrlQuery($params, $query, $videoCode = null)
    {
        // Converts the query in an associative array
        $queryAssoc = array();

        if (!empty($query)) {
            foreach ($query as $key => $value) {
                if (is_numeric($key)) {
                    $value = explode('=', $value);

                    if (!isset($value[1])) {
                        $queryAssoc[$value[0]] = 'true';
                    } else {
                        $queryAssoc[$value[0]] = $value[1];
                    }
                }
            }
        }

        return $queryAssoc;
    }

    /**
     * @param Registry $params
     * @param string   $videoCode
     * @param array    $query
     * @param string   $hash
     *
     * @return string
     */
    public static function getUrl($params, $videoCode, $query = array(), $hash = null)
    {
        $url = 'https://www.youtube.com/embed/' . $videoCode . '?wmode=transparent';

        $query = static::buildUrlQuery($params, $query, $videoCode);

        if (!empty($query)) {
            $url .= '&' . http_build_query($query);
        }

        if (!empty($hash)) {
            $url .= $hash;
        }

        return $url;
    }
}