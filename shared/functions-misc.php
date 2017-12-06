<?php

function arve_get_options_defaults( $section ) {

	$options['main'] = array(
		'align_maxwidth'      => 400,
		'align'               => 'none',
		'autoplay'            => false,
		'mode'                => 'normal',
		'promote_link'        => false,
		'sandbox'             => false,
		'video_maxwidth'      => '',
		'last_settings_tab'   => '',
		'wp_video_override'   => true,
		'controlslist'        => 'nodownload',
	);

	$properties = arve_get_host_properties();
	unset( $properties['video'] );

	foreach ( $properties as $provider => $values ) {

		if ( ! empty( $values['embed_url'] ) ) {
			$options['shortcodes'][ $provider ] = $provider;
		}
		if ( isset( $values['default_params'] ) ) {
			$options['params'][ $provider ] = $values['default_params'];
		}
	}

	return $options[ $section ];
}

/**
 * Get options by merging possibly existing options with defaults
 */
function arve_get_options() {

	$options = wp_parse_args( get_option( 'arve_options_main', array() ), arve_get_options_defaults( 'main' ) );

	$supported_modes = arve_get_supported_modes();

	# legacy mode name
	if ( 'thumbnail' == $options['mode'] ) {

		$options['mode'] = 'lazyload';
		update_option( 'arve_options_main', $options );
	}

	if( ! in_array( $options['mode'], array( 'normal', 'lazyload', 'lazyload-lightbox', 'link-lightbox' ) ) ) {

		$options['mode'] = 'lazyload';
		update_option( 'arve_options_main', $options );
	}

	$options['shortcodes'] = wp_parse_args( get_option( 'arve_options_shortcodes', array() ), arve_get_options_defaults( 'shortcodes' ) );
	$options['params']     = wp_parse_args( get_option( 'arve_options_params',     array() ), arve_get_options_defaults( 'params' ) );

	return $options;
}

function arve_get_settings_definitions() {

	$options         = arve_get_options();
	$supported_modes = arve_get_supported_modes();
	$properties      = arve_get_host_properties();

	foreach ( $properties as $provider => $values ) {

		if( ! empty( $values['auto_thumbnail'] ) ) {
			$auto_thumbs[] = $values['name'];
		}
		if( ! empty( $values['auto_title'] ) ) {
			$auto_title[] = $values['name'];
		}
		if( ! empty( $values['requires_src'] ) ) {
			$embed_code_only[] = $values['name'];
		}
	}

	$auto_thumbs      = implode( ', ', $auto_thumbs );
	$auto_title       = implode( ', ', $auto_title );
	$embed_code_only  = implode( ', ', $embed_code_only );

	if ( in_array( $options['mode'], $supported_modes ) ) {
		$current_mode_name = $supported_modes[ $options['mode'] ];
	} else {
		$current_mode_name = $options['mode'];
	}

	return array(
		array(
			'hide_from_settings' => true,
			'attr'  => 'url',
			'label' => esc_html__( 'URL / Embed Code', 'advanced-responsive-video-embedder' ),
			'type'  => 'text',
			'meta'  => array(
				'placeholder' => esc_attr__( 'Video URL / iframe Embed Code', 'advanced-responsive-video-embedder' ),
			),
			'description' => sprintf(
				__( 'Post the URL of the video here. For %s and any <a href="%s">unlisted</a> video hosts paste their iframe embed codes or its src URL in here (providers embeds need to be responsive).', 'advanced-responsive-video-embedder' ),
				$embed_code_only,
				'https://nextgenthemes.com/advanced-responsive-video-embedder-pro/#video-host-support'
			)
		),
		array(
			'attr'    => 'mode',
			'label'   => esc_html__( 'Mode', 'advanced-responsive-video-embedder' ),
			'type'    => 'select',
			'options' =>
				array( '' => sprintf( esc_html__( 'Default (current setting: %s)', 'advanced-responsive-video-embedder' ), $current_mode_name ) ) +
				arve_get_supported_modes(),
		),
		array(
			'attr'  => 'align',
			'label' => esc_html__('Alignment', 'advanced-responsive-video-embedder' ),
			'type'  => 'select',
			'options' => array(
				'' => sprintf( esc_html__( 'Default (current setting: %s)', 'advanced-responsive-video-embedder' ), $options['align'] ),
				'none'   => esc_html__( 'None', 'advanced-responsive-video-embedder' ),
				'left'   => esc_html__( 'Left', 'advanced-responsive-video-embedder' ),
				'right'  => esc_html__( 'Right', 'advanced-responsive-video-embedder' ),
				'center' => esc_html__( 'center', 'advanced-responsive-video-embedder' ),
			),
		),
		array(
			'attr'  => 'promote_link',
			'label' => esc_html__( 'ARVE Link', 'advanced-responsive-video-embedder' ),
			'type'  => 'select',
			'options' => array(
				'' => sprintf(
					__( 'Default (current setting: %s)', 'advanced-responsive-video-embedder' ),
					( $options['promote_link'] ) ? esc_html__( 'Yes', 'advanced-responsive-video-embedder' ) : esc_html__( 'No', 'advanced-responsive-video-embedder' )
				),
				'yes' => esc_html__( 'Yes', 'advanced-responsive-video-embedder' ),
				'no'  => esc_html__( 'No', 'advanced-responsive-video-embedder' ),
			),
			'description'  => esc_html__( "Shows a small 'ARVE' link below the videos. Be the most awesome person and help promoting this plugin.", 'advanced-responsive-video-embedder' ),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'thumbnail',
			'label' => esc_html__( 'Thumbnail', 'advanced-responsive-video-embedder' ),
			'type'  => 'attachment',
			'libraryType' => array( 'image' ),
			'addButton'   => esc_html__( 'Select Image', 'advanced-responsive-video-embedder' ),
			'frameTitle'  => esc_html__( 'Select Image', 'advanced-responsive-video-embedder' ),
			'description' => sprintf( esc_html__( 'Preview image for Lazyload modes, always used for SEO. The Pro Addon is able to get them from %s automatically.', 'advanced-responsive-video-embedder' ), $auto_thumbs ),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'title',
			'label' => esc_html__('Title', 'advanced-responsive-video-embedder'),
			'type'  => 'text',
			'description' => sprintf( esc_html__( 'Used for SEO, is visible on top of thumbnails in Lazyload modes, is used as link text in link-lightbox mode. The Pro Addon is able to get them from %s automatically.', 'advanced-responsive-video-embedder' ), $auto_title ),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'description',
			'label' => esc_html__('Description', 'advanced-responsive-video-embedder'),
			'type'  => 'text',
			'meta'  => array(
				'placeholder' => __( 'Description for SEO', 'advanced-responsive-video-embedder' ),
			)
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'upload_date',
			'label' => esc_html__( 'Upload Date', 'advanced-responsive-video-embedder' ),
			'type'  => 'text',
			'meta'  => array(
				'placeholder' => __( 'Upload Date for SEO, ISO 8601 format', 'advanced-responsive-video-embedder' ),
			)
		),
		array(
			'attr'  => 'autoplay',
			'label' => esc_html__('Autoplay', 'advanced-responsive-video-embedder' ),
			'type'  => 'select',
			'options' => array(
				'' => sprintf(
					__( 'Default (current setting: %s)', 'advanced-responsive-video-embedder' ),
					( $options['autoplay'] ) ? esc_html__( 'Yes', 'advanced-responsive-video-embedder' ) : esc_html__( 'No', 'advanced-responsive-video-embedder' )
				),
				'yes' => esc_html__( 'Yes', 'advanced-responsive-video-embedder' ),
				'no'  => esc_html__( 'No', 'advanced-responsive-video-embedder' ),
			),
			'description' => esc_html__( 'Autoplay videos in normal mode, has no effect on lazyload modes.', 'advanced-responsive-video-embedder' ),
		),
		array(
			'hide_from_sc'   => true,
			'attr'  => 'video_maxwidth',
			'label'       => esc_html__('Maximal Width', 'advanced-responsive-video-embedder'),
			'type'        =>  'number',
			'description' => esc_html__( 'Optional, if not set your videos will be the maximum size of the container they are in. If your content area has a big width you might want to set this. Must be 100+ to work.', 'advanced-responsive-video-embedder' ),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'maxwidth',
			'label' => esc_html__('Maximal Width', 'advanced-responsive-video-embedder'),
			'type'  =>  'number',
			'meta'  => array(
				'placeholder' => esc_attr__( 'in px - leave empty to use settings', 'advanced-responsive-video-embedder'),
			),
		),
		array(
			'hide_from_sc'   => true,
			'attr'  => 'align_maxwidth',
			'label' => esc_html__('Align Maximal Width', 'advanced-responsive-video-embedder'),
			'type'  => 'number',
			'description' => esc_attr__( 'In px, Needed! Must be 100+ to work.', 'advanced-responsive-video-embedder' ),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'aspect_ratio',
			'label' => __('Aspect Ratio', 'advanced-responsive-video-embedder'),
			'type'  => 'text',
			'meta'  => array(
				'placeholder' => __( 'Custom aspect ratio like 4:3, 21:9 ... Leave empty for default.', 'advanced-responsive-video-embedder'),
			),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'parameters',
			'label' => esc_html__('Parameters', 'advanced-responsive-video-embedder' ),
			'type'  => 'text',
			'meta'  => array(
				'placeholder' => __( 'provider specific parameters', 'advanced-responsive-video-embedder' ),
			),
			'description' => sprintf(
				__(
					'Note this values get merged with values set on the <a target="_blank" href="%s">ARVE setting page</a>. Example for YouTube <code>fs=0&start=30</code>. For reference: <a target="_blank" href="%s">Youtube Parameters</a>, <a target="_blank" href="%s">Dailymotion Parameters</a>, <a target="_blank" href="%s">Vimeo Parameters</a>.',
					'advanced-responsive-video-embedder'
				),
				admin_url( 'admin.php?page=advanced-responsive-video-embedder' ),
				'https://developers.google.com/youtube/player_parameters',
				'http://www.dailymotion.com/doc/api/player.html#parameters',
				'https://developer.vimeo.com/player/embedding',
				'TODO settings page link'
			),
		),
		array(
			'hide_from_sc' => true,
			'attr'  => 'wp_video_override',
			'label' => esc_html__( 'Use ARVE for HTML5 video embeds', 'advanced-responsive-video-embedder' ),
			'type'  => 'select',
			'options' => array(
				'yes' => esc_html__( 'Yes', 'advanced-responsive-video-embedder' ),
				'no'  => esc_html__( 'No', 'advanced-responsive-video-embedder' ),
			),
			'description' => esc_html__( "Use ARVE to embed HTML5 video files. ARVE uses the browsers players instead of loading the mediaelement player that WP uses.", 'advanced-responsive-video-embedder' ),
		),
		array(
			'attr'  => 'controlslist',
			'label' => esc_html__( 'Chrome HTML5 Player controlls', 'advanced-responsive-video-embedder' ),
			'type'  => 'text',
			'description' => __( "controlsList attribute on &lt;video&gt; for example use <code>nodownload nofullscreen noremoteplayback</code> to hide the download and the fullscreen button on the Chrome HTML5 video player and disable remote playback.", 'advanced-responsive-video-embedder' ),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'mp4',
			'label' => esc_html__('mp4 file', 'advanced-responsive-video-embedder'),
			'type'  => 'url',
			#'type'  => 'attachment',
			#'libraryType' => array( 'video' ),
			#'addButton'   => esc_html__( 'Select .mp4 file', 'advanced-responsive-video-embedder' ),
			#'frameTitle'  => esc_html__( 'Select .mp4 file', 'advanced-responsive-video-embedder' ),
			'meta'  => array(
				'placeholder' => __( '.mp4 file url for HTML5 video', 'advanced-responsive-video-embedder' ),
			),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'webm',
			'label' => esc_html__('webm file', 'advanced-responsive-video-embedder'),
			'type'  => 'url',
			#'type'  => 'attachment',
			#'libraryType' => array( 'video' ),
			#'addButton'   => esc_html__( 'Select .webm file', 'advanced-responsive-video-embedder' ),
			#'frameTitle'  => esc_html__( 'Select .webm file', 'advanced-responsive-video-embedder' ),
			'meta'  => array(
				'placeholder' => __( '.webm file url for HTML5 video', 'advanced-responsive-video-embedder' ),
			),
		),
		array(
			'hide_from_settings' => true,
			'attr'  => 'ogv',
			'label' => esc_html__('ogv file', 'advanced-responsive-video-embedder'),
			'type'  => 'url',
			#'type'  => 'attachment',
			#'libraryType' => array( 'video' ),
			#'addButton'   => esc_html__( 'Select .ogv file', 'advanced-responsive-video-embedder' ),
			#'frameTitle'  => esc_html__( 'Select .ogv file', 'advanced-responsive-video-embedder' ),
			'meta'  => array(
				'placeholder' => __( '.ogv file url for HTML5 video', 'advanced-responsive-video-embedder' ),
			),
		),
	);
}

	/**
	 *
	 *
	 * @since     5.4.0
	 */
function arve_get_mode_options( $selected ) {

	$modes = arve_get_supported_modes();

	$out = '';

	foreach( $modes as $mode => $desc ) {

		$out .= sprintf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $mode ),
			selected( $selected, $mode, false ),
			$desc
		);
	}

	return $out;
}

function arve_get_supported_modes() {
	return apply_filters( 'arve_modes', array( 'normal' => __( 'Normal', 'advanced-responsive-video-embedder' ) ) );
}

function arve_get_iframe_providers() {

}

function arve_attr( $attr = array() ) {

	if ( empty( $attr ) ) {
		return '';
	}

	$html = '';

	foreach ( $attr as $key => $value ) {

		if ( false === $value || null === $value ) {
			continue;
		} elseif ( '' === $value || true === $value ) {
			$html .= sprintf( ' %s', esc_html( $key ) );
		} elseif ( in_array( $key, array( 'href', 'data-href', 'src', 'data-src' ) ) ) {
			$html .= sprintf( ' %s="%s"', esc_html( $key ), arve_esc_url( $value ) );
		} else {
			$html .= sprintf( ' %s="%s"', esc_html( $key ), esc_attr( $value ) );
		}
	}

	return $html;
}

function arve_esc_url( $url ) {
	return str_replace( 'jukebox?list%5B0%5D', 'jukebox?list[]', esc_url( $url ) );
}

function arve_starts_with( $haystack, $needle ) {
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos( $haystack, $needle, -strlen( $haystack ) ) !== false;
}

function arve_ends_with( $haystack, $needle ) {
	// search forward starting from end minus needle length characters
	return $needle === "" || ( ( $temp = strlen($haystack) - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== false );
}

function arve_contains( $haystack, $needle ) {
  return strpos( $haystack, $needle ) !== false;
}

function arve_register_asset( $args ) {

	$defaults = array(
		'handle'     => null,
		'src'        => null,
		'deps'       => array(),
		'in_footer'  => true,
		'media'      => null,
		'ver'        => ARVE_VERSION,
		'automin'    => false,
	);

	$args = wp_parse_args( $args, $defaults );

	if ( $args['automin'] && ! defined( 'WP_DEBUG' ) && ! WP_DEBUG ) {

		$args['src'] = str_replace( '.css', '.min.css', $args['src'] );
		$args['src'] = str_replace( '.js',  '.min.js',  $args['src'] );
	}

	if ( arve_ends_with( $args['src'], '.css' ) ) {
		wp_register_style( $args['handle'], $args['src'], $args['deps'], $args['ver'], $args['media'] );
	} else {
		wp_register_script( $args['handle'], $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
	}
}

function arve_get_min_suffix() {
	return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';
}
