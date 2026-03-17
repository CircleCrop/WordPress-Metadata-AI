<?php

namespace WMAIGEN\Support;

/**
 * Normalize WordPress errors for notices and logs.
 */
final class ErrorMessageFormatter {
	/**
	 * @param \WP_Error|string $error Error payload.
	 */
	public static function from_wp_error( $error, string $fallback ): string {
		if ( $error instanceof \WP_Error ) {
			$message = $error->get_error_message();

			return '' !== $message ? $message : $fallback;
		}

		$error = trim( (string) $error );

		return '' !== $error ? $error : $fallback;
	}
}
