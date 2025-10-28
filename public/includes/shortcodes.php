<?php
/*
 * File: shortcodes.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


function display_first_principal_report() {

	$args = array(
		'post_type'      => 'principal_report',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'post_date',
		'order'          => 'DESC',
	);

	$reports = get_posts( $args );

	$report = ! empty( $reports ) ? $reports[0] : null;

	if ( ! empty( $report ) ) {

		$title   = get_the_title( $report->ID );
		$date    = get_the_date( '', $report->ID );
		$content = wp_strip_all_tags( apply_filters( 'the_content', $report->post_content ) );
		$excerpt = wp_trim_words( $content, 60, '...' );

		// Avoid cutting mid-word.
		if ( mb_strlen( $content ) > 200 ) {
			$excerpt = preg_replace( '/\s+?(\S+)?$/', '', $excerpt ) . '...';
		}

		$permalink = get_permalink( $report->ID );

		$output  = '<div class="principal-report">';
		$output .= '<h2><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h2>';
		$output .= '<span class="report-date">' . esc_html( $date ) . '</span>';
		$output .= '<p>' . esc_html( $excerpt ) . '</p>';
		$output .= '<a class="button-default-red" href="' . esc_url( $permalink ) . '">Read more</a>';
		$output .= '</div>';

		return $output;

	}

	return 'No principal report found.';
}

	add_shortcode( 'principal_report_single', 'display_first_principal_report' );
