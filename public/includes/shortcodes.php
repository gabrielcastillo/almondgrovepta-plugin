<?php
/*
 * File: shortcodes.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


function display_first_principal_report() {
	global $wpdb;
	$tablename = $wpdb->prefix . 'posts';

	$sql = /** @lang sql */
		"SELECT * FROM {$tablename}
		WHERE post_type = 'principal_report'
		AND post_status = 'publish'
		ORDER BY post_date DESC
		LIMIT 1";

	$results = $wpdb->get_results( $sql );

	if ( ! empty( $results ) ) {
		$post    = $results[0];
		$title   = get_the_title( $post->ID );
		$date    = get_the_date( '', $post->ID );
		$content = wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) );
		$excerpt = mb_substr( $content, 0, 200 );

		// Avoid cutting mid-word
		if ( mb_strlen( $content ) > 200 ) {
			$excerpt = preg_replace( '/\s+?(\S+)?$/', '', $excerpt ) . '...';
		}

		$permalink = get_permalink( $post->ID );

		$output  = '<div class="principal-report">';
		$output .= '<h2><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h2>';
		$output .= '<p class="report-date">' . esc_html( $date ) . '</p>';
		$output .= '<p>' . esc_html( $excerpt ) . '</p>';
		$output .= '<a href="' . esc_url( $permalink ) . '">Read more</a>';
		$output .= '</div>';

		return $output;

	} else {
		return 'No principal report found.';
	}
}

	add_shortcode( 'principal_report_single', 'display_first_principal_report' );
