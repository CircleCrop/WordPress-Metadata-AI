<?php

namespace WMAIGEN\Support;

/**
 * Convert timestamps into site-local admin display strings.
 */
final class TimeFormatter {
	public function format( int $timestamp ): string {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		return wp_date( $format, $timestamp );
	}
}
