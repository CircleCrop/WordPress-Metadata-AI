<?php

namespace WMAIGEN\Infrastructure;

use WMAIGEN\Domain\LogEntry;

/**
 * Persistent log storage for recent plugin activity.
 */
final class LogRepository {
	public const OPTION_NAME = 'wmaigen_logs';
	public const MAX_ENTRIES = 200;

	/**
	 * Store a new log entry.
	 */
	public function add( LogEntry $entry ): void {
		$logs = $this->get_all();
		array_unshift( $logs, $entry->to_array() );
		$logs = array_slice( $logs, 0, self::MAX_ENTRIES );

		update_option( self::OPTION_NAME, $logs, false );
		$this->mirror_to_error_log( $entry );
	}

	/**
	 * Convenience logging helper.
	 *
	 * @param array<string, mixed> $context Log context.
	 */
	public function log( string $level, string $action, string $result, string $message, array $context = array() ): void {
		$entry = new LogEntry(
			time(),
			$level,
			$action,
			$result,
			$message,
			isset( $context['object_kind'] ) ? (string) $context['object_kind'] : '',
			isset( $context['object_subtype'] ) ? (string) $context['object_subtype'] : '',
			isset( $context['object_id'] ) ? (int) $context['object_id'] : 0,
			isset( $context['object_name'] ) ? (string) $context['object_name'] : ''
		);

		$this->add( $entry );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent( int $limit = 50 ): array {
		return array_slice( $this->get_all(), 0, max( 1, $limit ) );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_all(): array {
		$logs = get_option( self::OPTION_NAME, array() );

		return is_array( $logs ) ? $logs : array();
	}

	private function mirror_to_error_log( LogEntry $entry ): void {
		if ( ! $this->should_mirror_to_error_log() ) {
			return;
		}

		$data = $entry->to_array();
		error_log( wp_json_encode( array( 'wmaigen' => $data ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	private function should_mirror_to_error_log(): bool {
		if ( defined( 'WMAIGEN_DEBUG' ) && WMAIGEN_DEBUG ) {
			return true;
		}

		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}
