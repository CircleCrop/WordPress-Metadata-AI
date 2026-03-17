<?php

namespace WMAIGEN\Support;

use WMAIGEN\Domain\GenerationResult;

/**
 * Convert generation results into admin notice payloads.
 */
final class GenerationNoticeFormatter {
	public function get_type( GenerationResult $result ): string {
		if ( $result->is_saved() ) {
			return 'success';
		}

		if ( $result->is_dry_run() ) {
			return 'info';
		}

		if ( $result->is_skipped() ) {
			return 'warning';
		}

		return 'error';
	}

	public function get_message( GenerationResult $result ): string {
		if ( $result->is_dry_run() || $result->is_saved() ) {
			return sprintf(
				'%1$s<br><strong>%2$s</strong> <code>%3$s</code>',
				esc_html( $result->get_message() ),
				esc_html__( 'Generated description:', 'wordpress-metadata-aigen' ),
				esc_html( $result->get_description() )
			);
		}

		return esc_html( $result->get_message() );
	}
}
