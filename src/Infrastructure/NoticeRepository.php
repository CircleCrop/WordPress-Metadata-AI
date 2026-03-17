<?php

namespace WMAIGEN\Infrastructure;

/**
 * Flash-style admin notices stored per user.
 */
final class NoticeRepository {
	public const META_KEY = 'wmaigen_admin_notices';

	/**
	 * Add a notice for the current user.
	 */
	public function add( string $type, string $message ): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$notices   = get_user_meta( $user_id, self::META_KEY, true );
		$notices   = is_array( $notices ) ? $notices : array();
		$notices[] = array(
			'type'    => $this->normalize_type( $type ),
			'message' => $message,
		);

		update_user_meta( $user_id, self::META_KEY, $notices );
	}

	/**
	 * Pull and clear notices for the current user.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function pull(): array {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return array();
		}

		$notices = get_user_meta( $user_id, self::META_KEY, true );
		delete_user_meta( $user_id, self::META_KEY );

		return is_array( $notices ) ? $notices : array();
	}

	private function normalize_type( string $type ): string {
		$allowed = array( 'success', 'error', 'warning', 'info' );

		return in_array( $type, $allowed, true ) ? $type : 'info';
	}
}
