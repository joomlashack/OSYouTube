<?php
/**
* @copyright Copyright (C) 2008 Cory Webb. All rights reserved.
* @license GNU/GPL
*
* Special thanks to Simon Tiplady (http://www.stiplady.net) for help with the regular expressions.
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
	function onPrepareContent( &$article, &$params, $limitstart )
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

		$plugin =& JPluginHelper::getPlugin('content', 'youtubeembed');
	 	$params = new JParameter( $plugin->params );

		$width = $params->get('width', 425);
		$height = $params->get('height', 344);
	
		return '<object width="'.$width.'" height="'.$height.'"><param name="movie" value="http://www.youtube.com/v/'.$vCode.'"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/'.$vCode.'" type="application/x-shockwave-flash" allowfullscreen="true" width="'.$width.'" height="'.$height.'"></embed></object>';
	}

}
