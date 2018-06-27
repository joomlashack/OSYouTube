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
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die();

abstract class AbstractMethods
{
    /**
     * @var Registry
     */
    protected $params = null;

    /**
     * @var string[]
     */
    protected $videoIds = array();

    /**
     * AbstractMethods constructor.
     *
     * @param AbstractPlugin $parent
     */
    public function __construct(AbstractPlugin $parent)
    {
        $this->params = $parent->params;
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
        $search = $this->getSearches();

        $ignoreHtmlLinks = $this->params->get('ignore_html_links', 0);
        $replacements    = array();
        foreach ($search as $regex) {
            $linkRegex = '#(?:<a.*(href)=[\'"])?' . addcslashes($regex, '#') . '(?:[\'"].*>.*</a>)?#';

            if (preg_match_all($linkRegex, $article->text, $matches)) {
                foreach ($matches[0] as $k => $source) {
                    $replaceKey = sprintf('{{%s}}', md5($source));

                    if (!isset($replacements[$replaceKey])) {
                        if ($ignoreHtmlLinks && !empty($matches[1][$k])) {
                            $replacements[$replaceKey] = $source;

                        } else {
                            // Convert to embedded iframe
                            $sourceUrl = $matches[2][$k];
                            $videoCode = $matches[3][$k];
                            $query     = html_entity_decode($matches[4][$k]);
                            $urlHash   = $matches[5][$k];

                            if ($query && $query[0] == '?') {
                                $query = substr($query, 1);
                            }
                            parse_str($query, $query);

                            $url       = $this->getUrl($sourceUrl, $videoCode, $query, $urlHash);
                            $embedCode = $this->youtubeCodeEmbed($videoCode, $url);

                            $replacements[$replaceKey] = $embedCode;
                        }

                        $article->text = str_replace($source, $replaceKey, $article->text);
                    }
                }
            }
        }

        if ($replacements) {
            $article->text = str_replace(array_keys($replacements), $replacements, $article->text);
        }

        return true;
    }

    /**
     * Load the regular expressions to search for in the text
     *
     * @return array
     */
    protected function getSearches()
    {
        $searches = array(
            '(https?://(?:www\.)?youtube.com/embed/([a-zA-Z0-9-_&;=]+))(\?[a-zA-Z0-9-_&;=]*)?(#[a-zA-Z0-9-_&;=]*)?',
            '(https?://(?:www\.)?youtube.com/watch\?v=([a-zA-Z0-9-_;]+))(&[a-zA-Z0-9-_&;=]*)?(#[a-zA-Z0-9-_&;=]*)?'
        );

        return $searches;
    }

    /**
     * @param string $videoCode
     * @param string $iframeSrc
     *
     * @return string
     */
    protected function youtubeCodeEmbed($videoCode, $iframeSrc)
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

        // The "Load after page load" feature is only available in Pro
        // but iframe is loaded in Free, so this is needed here
        $afterLoad = $this->params->get('load_after_page_load', 0);

        if ($afterLoad) {
            // This is used as a placeholder for the "Load after page load" feature in Pro
            $iframeDataSrc = $iframeSrc;
            $iframeSrc     = '';
        }

        $id = 'youtube_' . $videoCode;
        if (!empty($this->videoIds[$videoCode])) {
            $id .= '_' . ($this->videoIds[$videoCode]++);
        } else {
            $this->videoIds[$videoCode] = 1;
        }

        $attribs = array(
            'id'          => $id,
            'width'       => $width,
            'height'      => $height,
            'frameborder' => '0',
            'src'         => $iframeSrc
        );

        if (!empty($iframeDataSrc)) {
            $attribs['data-src'] = $iframeDataSrc;
        }

        $output .= '<iframe ' . ArrayHelper::toString($attribs) . ' allowfullscreen></iframe>';

        if ($responsive) {
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * @param string $sourceUrl
     * @param string $videoCode
     * @param array  $query
     * @param string $hash
     *
     * @return string
     */
    protected function getUrl($sourceUrl, $videoCode, array $query = array(), $hash = null)
    {
        $query = array_merge(array('wmode' => 'transparent'), $query);

        $url = sprintf(
            'https://www.youtube.com/embed/%s?%s%s',
            $videoCode,
            http_build_query($query),
            $hash
        );

        return $url;
    }
}
