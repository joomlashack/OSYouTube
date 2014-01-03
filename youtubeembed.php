<?php
/**
* @copyright Copyright (C) 2013 OSTraining.com
* @license GNU/GPL
*
*/

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
* YouTube Video Embedder Content Plugin
*
*/
class plgContentYoutubeEmbed extends JPlugin
{

	/**
	* Constructor
	*
	* @param object $subject The object to observe
	* @param object $params The object that holds the plugin parameters
	*/
	function plgContentYoutubeEmbed( &$subject, $params )
	{
		parent::__construct( $subject, $params );
	}

	/**
	* Example prepare content method
	*
	* Method is called by the view
	*
	* @param object The article object. Note $article->text is also available
	* @param object The article params
	* @param int The 'page' number
	*/
	function onContentPrepare( $context, &$article, &$params, $page = 0)
		{
		global $mainframe;
	
		if ( JString::strpos( $article->text, 'http://www.youtube.com/' ) === false ) {
		return true;
		}
	
		$article->text = preg_replace('|(http://www.youtube.com/watch\?v=([a-zA-Z0-9_-]+))|e', '$this->youtubeCodeEmbed("\2")', $article->text);

		return true;
	
	}

	function youtubeCodeEmbed( $vCode )
	{

		$output = '';
		$params = $this->params;

		$width = $params->get('width', 425);
		$height = $params->get('height', 344);
		$responsive = $params->get('responsive', 1);

		if( $responsive ){
		    $doc = JFactory::getDocument();
		    $doc->addStyleSheet(JURI::base() . "plugins/content/youtubeembed/style.css");
		    $output .= '<div class="video-responsive">';
		}

		$output .= '<iframe width="'.$width.'" height="'.$height.'" src="//www.youtube.com/embed/'.$vCode.'" frameborder="0" allowfullscreen></iframe>';

		if( $responsive ){
		    $output .= '</div>';
		}

		return $output;
	}

}
