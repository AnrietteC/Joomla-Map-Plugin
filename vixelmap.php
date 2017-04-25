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
	const MAP_ID          = 'vixle_map';

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
					$width             = null;
					$height            = null;

					foreach ( $matches as $match ) {
						$match   = array_shift( $match );
						$match   = trim( $match );
						$address = null;

						if ( preg_match( '/width="(.*?)"/', $match, $match_width ) ) {
							$width = isset( $match_width[ 1 ] ) ? $match_width[ 1 ] : $width;
						}

						$height = $this->params->get( 'default_height_js' );
						if ( preg_match( '/height="(.*?)"/', $match, $match_height ) ) {
							$height = isset( $match_height[ 1 ] ) ? $match_height[ 1 ] : $height;
						}

						if ( preg_match( '/type="(.*?)"/', $match, $match_type ) ) {
							if ( isset( $match_type[ 1 ] ) ) {
								$type = $match_type[ 1 ];
							}
						}
						if ( preg_match( '/address="(.*?)"/', $match, $match_address ) ) {
							$address = isset( $match_address[ 1 ] ) ? $match_address[ 1 ] : $address;
						}

						if ( !$address ) {
							JLog::add( 'Vixel Map: FAILED (No address)', JLog::DEBUG, 'vixel' );

							return false;
						}

						if ( $type == self::API_TYPE_EMBED ) {
							$width  = !$width ? $this->params->get( 'default_width_iframe' ) : $width;
							$height = !$height ? $this->params->get( 'default_height_iframe') : $height;

							$encoded_address = urlencode( $address );
							$replacement     = <<<HTML
<iframe src="//www.google.com/maps/embed/v1/place?q=$encoded_address&zoom=17&key=$api_key" style="width: $width; height: $height;"></iframe>
HTML;
						} else if ( $type == self::API_TYPE_JS ) {

							$width = !$width ? $this->params->get( 'default_width_js' ) : $width;
							$height = !$height ? $this->params->get( 'default_height_js' ) : $height;

							$zoom = 4;
							if ( preg_match( '/zoom="(.*?)"/', $match, $match_zoom ) ) {
								$zoom = isset( $match_zoom[ 1 ] ) ? $match_zoom[ 1 ] : null;
							}

							$lat = null;
							if ( preg_match( '/lat="(.*?)"/', $match, $match_lat ) ) {
								$lat = isset( $match_lat[ 1 ] ) ? $match_lat[ 1 ] : null;
							}

							$lng = null;
							if ( preg_match( '/lng="(.*?)"/', $match, $match_lng ) ) {
								$lng = isset( $match_lng[ 1 ] ) ? $match_lng[ 1 ] : null;
							}

							$map_id             = self::MAP_ID;
							$replacement        = <<<HTML
<div id="$map_id"></div>
HTML;
							$map_init_object    = new stdClass();
							$marker_object      = new stdClass();
							$marker_object->map = 'map';

							if ( $zoom ) {
								$map_init_object->zoom = intval( $zoom );
							}

							if ( $lat && $lng ) {
								$center_point_object      = new stdClass();
								$center_point_object->lat = floatval( $lat );
								$center_point_object->lng = floatval( $lng );
								$map_init_object->center  = $center_point_object;
								$marker_object->position  = $center_point_object;
							} else {
								JLog::add( 'Vixel Map: FAILED (No center point coordinates)', JLog::WARNING, 'vixel' );

								return false;
							}

							$map_init     = json_encode( $map_init_object );
							$center_point = json_encode( $center_point_object );

							$js = <<<JS
function initMap() {
	var marker = new google.maps.Marker({"map":new google.maps.Map(document.getElementById('$map_id'), $map_init), position:$center_point});
}
JS;

							$replacement .= <<<HTML
<script type="text/javascript">
	$js
</script>
HTML;
							$replacement .= <<<HTML
<script src="https://maps.googleapis.com/maps/api/js?key=$api_key&callback=initMap" async defer></script>
HTML;

							$css = <<<CSS
#$map_id {
	height: $height;
	width: $width;
}
CSS;

							JFactory::getDocument()->addStyleDeclaration( $css );
						}
					}

					foreach ( $complete_patterns as $pattern ) {
						$article->text = preg_replace( '/' . preg_quote( $pattern ) . '/', $replacement, $article->text );
					}
				}
			}
		}
	}

}
