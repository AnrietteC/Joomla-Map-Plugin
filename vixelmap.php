<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.vixelmap
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined( '_JEXEC' ) or die;

/**
 * Vixelmap plugin class.
 *
 * @since  1.5
 */

use Joomla\String\StringHelper;

class PlgContentVixelmap extends JPlugin
{
	const API_TYPE_EMBED  = 'embed';
	const API_TYPE_JS     = 'js';
	const API_TYPE_STATIC = 'static';
	const API_TYPE_STREET = 'street';

	public function onContentPrepare ( $context, &$article, $params, $page )
	{
		$app = JFactory::getApplication();

		// Don't run this plugin when the content is being indexed
		if ( $context == 'com_finder.indexer' || $app->isAdmin() ) {
			return true;
		}

		JLog::addLogger( [
			                 'text_file' => 'plg_content_vixelmap.log',
		                 ], JLog::ALL, [ 'vixel' ] );

		if ( $text = $article->text ) {
			$api_key = $this->params->get( 'api_key' );

			if ( !$api_key ) {
				JLog::add( 'Vixel Map: FAILED (No API key)', JLog::ERROR, 'vixel' );

				return false;
			}

			if ( StringHelper::strpos( $text, '{vixelmap' ) !== false ) {

				if ( preg_match_all( '/{vixelmap(.*)}/', $text, $matches ) ) {
					$complete_patterns = array_shift( $matches );
					$replacement       = '';
					$type              = self::API_TYPE_EMBED;

					foreach ( $matches as $match ) {
						$match   = array_shift( $match );
						$match   = trim( $match );
						$address = null;

						if ( preg_match( '/type=\"(.*)\"/', $match, $match_type ) ) {
							if ( isset( $match_type[ 1 ] ) ) {
								$type = $match_type[ 1 ];
							}
						}
						if ( preg_match( '/address=\"(.*)\"/', $match, $match_address ) ) {
							$address = isset( $match_address[ 1 ] ) ? $match_address[ 1 ] : null;
						}

						if ( !$address ) {
							JLog::add( 'Vixel Map: FAILED (No address)', JLog::DEBUG, 'vixel' );

							return false;
						}

						if ( $type == self::API_TYPE_EMBED ) {
							$replacement = '<iframe src="//www.google.com/maps/embed/v1/place?q=' . urlencode( $address ) . '&zoom=17&key=' . $api_key . '"></iframe>';

						}
					}

					foreach ( $complete_patterns as $pattern ) {
						$article->text = preg_replace( '/' . $pattern . '/', $replacement, $article->text );
					}
				}
			}
		}
	}

}
