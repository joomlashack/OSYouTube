<?php
/**
 * @package   OSYouTube
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2017-2018 Joomlashack.com. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
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
use Exception;
use JFactory;
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
     * @var bool
     */
    protected $ignoreLinks = false;

    /**
     * @var string[]
     */
    protected $searches = null;

    /**
     * @var string[]
     */
    protected $replacements = null;

    /**
     * @var string[]
     */
    protected $videoIds = array();

    /**
     * @var array
     */
    protected $debugLog = array();

    /**
     * @var array
     */
    protected $renderedLogs = array();

    /**
     * @var int
     */
    protected static $instance = 0;

    /**
     * @var int
     */
    protected $called = 0;

    /**
     * AbstractMethods constructor.
     *
     * @param AbstractPlugin $parent
     */
    public function __construct(AbstractPlugin $parent)
    {
        $this->params      = $parent->params;
        $this->ignoreLinks = $this->params->get('ignore_html_links', 0);
        $this->searches    = $this->getSearches();

        static::$instance++;
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
        $this->called++;
        $this->addLogEntry('OSYoutube BEGIN - ' . $context, true);
        $this->addLogEntry('Caller: ' . $this->getCaller());
        $this->addLogEntry('Hash: ' . md5($article->text));

        $this->replacements = array();

        // Hide any youtube links already embedded with <iframe>
        if (preg_match_all('#<iframe.*src=["\']\S*youtube\S*\.com.*</iframe>#', $article->text, $iframes)) {
            foreach ($iframes[0] as $source) {
                $this->addLogEntry('Skipped iframe: ' . $source);

                $replaceKey = sprintf('{{%s}}', md5($source));
                if (!isset($this->replacements[$replaceKey])) {
                    $this->replacements[$replaceKey] = $source;

                    $article->text = str_replace($source, $replaceKey, $article->text);
                }
            }
        }

        // Do links first to hide them from plain url searches
        foreach ($this->searches as $regex) {
            $linkRegex = '#(?:<a.*href=[\'"])' . addcslashes($regex, '#') . '(?:[\'"].*>.*</a>)#';
            $this->createPlaceholders($linkRegex, $article->text, true);
        }

        // Now we can safely look for non-link instances
        foreach ($this->searches as $regex) {
            $plainRegex = '#' . addcslashes($regex, '#') . '#';
            $this->createPlaceholders($plainRegex, $article->text);
        }

        if ($this->replacements) {
            $article->text = str_replace(array_keys($this->replacements), $this->replacements, $article->text);
        }

        $this->addLogEntry('Hash: ' . md5($article->text));
        $this->addLogEntry('OSYoutube END - ' . $context, true);
        if ($this->params->get('debug')) {
            $article->text .= $this->renderDebugLog();
        }

        return true;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function onAfterRender()
    {
        if ($this->renderedLogs) {
            $app = JFactory::getApplication();

            $app->setBody(str_replace(array_keys($this->renderedLogs), $this->renderedLogs, $app->getBody()));
        }
    }

    /**
     * Replace matches in the text with placeholders
     * and add to replacement list for later updating
     *
     * @param string $regex
     * @param string $text
     * @param bool   $links
     *
     * @return void
     */
    protected function createPlaceholders($regex, &$text, $links = false)
    {
        if (preg_match_all($regex, $text, $matches)) {
            $this->addLogEntry($links ? 'Hide Links' : 'Create embed code');
            foreach ($matches[0] as $k => $source) {
                $this->addLogEntry('Source: ' . $source);

                $sourceUrl  = $matches[1][$k];
                $videoCode  = $matches[2][$k];
                $querString = html_entity_decode($matches[3][$k]);
                $hash       = $matches[4][$k];

                $replaceKey = sprintf('{{%s}}', md5($source));

                if (!isset($this->replacements[$replaceKey])) {
                    if ($this->ignoreLinks && $links) {
                        // Hide the link temporarily to avoid crashes
                        $this->replacements[$replaceKey] = $source;

                    } else {
                        // Convert to embedded iframe
                        if ($querString && $querString[0] == '?') {
                            $querString = substr($querString, 1);
                        }
                        parse_str($querString, $query);

                        $url       = $this->getUrl($sourceUrl, $videoCode, $query, $hash);
                        $embedCode = $this->youtubeCodeEmbed($videoCode, $url);

                        $this->replacements[$replaceKey] = $embedCode;

                        $this->addLogEntry('Embed: ' . $embedCode);
                    }

                    $text = str_replace($source, $replaceKey, $text);
                }
            }
        }
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

    /**
     * @param string $message
     * @param bool   $heading
     * @param bool   $prepend
     */
    protected function addLogEntry($message, $heading = false, $prepend = false)
    {
        if ($heading) {
            $entry = sprintf('*** %s (%03d/%03d) ***', $message, static::$instance, $this->called);

        } else {
            $entry = $message;
        }

        $key = md5(static::$instance . '.' . $this->called);

        if (!isset($this->debugLog[$key])) {
            $this->debugLog[$key] = array();
        }

        if ($prepend) {
            array_unshift($this->debugLog[$key], $entry);

        } else {
            $this->debugLog[$key][] = $entry;
        }
    }

    /**
     * @return string
     */
    protected function renderDebugLog($reset = true)
    {
        $renderedLog = '';

        foreach ($this->debugLog as $entries) {
            $renderedLog .= '<ul><li>' . join('</li><li>', array_map('htmlspecialchars', $entries)) . '</li></ul>';
        }

        if ($reset) {
            $this->debugLog = array();
        }

        $renderKey                      = md5($renderedLog);
        $this->renderedLogs[$renderKey] = $renderedLog;
        return "\n" . $renderKey;
    }

    /**
     * Attempt to retrieve a useful originator of the invocation
     * Assumed stack under normal circumstances:
     *
     * 0. self::getCaller
     * 1. self::onContentPrepare
     * 2. Pro\Methods\onContentPrepare (So free version will be off
     * 3. BasePlugin::onContentPrepare
     * 4. \JEvent::update
     * 5. \JEventDispatcher::trigger
     *
     * @param int $stackIndex
     *
     * @return string
     */
    protected function getCaller($stackIndex = 6)
    {
        $stackItem = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[$stackIndex];

        $caller = (empty($stackItem['class']) ? '' : $stackItem['class'])
            . (empty($stackItem['function']) ? '' : '::' . $stackItem['function'] . '() ')
            . (empty($stackItem['file']) ? '' : ' - ' . str_replace(JPATH_SITE . '/', '', $stackItem['file']))
            . (empty($stackItem['line']) ? '' : ':' . $stackItem['line']);

        return $caller;
    }
}
