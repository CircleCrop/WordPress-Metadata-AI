<?php

namespace WMAIGEN\Infrastructure;

/**
 * Persist the latest batch scan per admin user so previews survive redirects.
 */
final class BatchStateRepository {
	public const META_KEY = 'wmaigen_batch_state';
	public const TTL      = 1800;

	/**
	 * @param array<string, mixed> $state Batch preview state.
	 */
	public function store( array $state ): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$state['saved_at'] = time();
		update_user_meta( $user_id, self::META_KEY, $state );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get() {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return null;
		}

		$state = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $state ) ) {
			return null;
		}

		$saved_at = isset( $state['saved_at'] ) ? (int) $state['saved_at'] : 0;

		if ( $saved_at > 0 && ( time() - $saved_at ) > self::TTL ) {
			$this->clear();
			return null;
		}

		return $state;
	}

	public function clear(): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		delete_user_meta( $user_id, self::META_KEY );
	}
}
