<?php

namespace WMAIGEN\Admin;

use WMAIGEN\Infrastructure\NoticeRepository;

/**
 * Render flash notices across admin redirects.
 */
final class AdminNotices {
	/**
	 * @var NoticeRepository
	 */
	private $notice_repository;

	public function __construct( NoticeRepository $notice_repository ) {
		$this->notice_repository = $notice_repository;
	}

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	public function render(): void {
		$notices = $this->notice_repository->pull();

		foreach ( $notices as $notice ) {
			$type    = isset( $notice['type'] ) ? (string) $notice['type'] : 'info';
			$message = isset( $notice['message'] ) ? (string) $notice['message'] : '';

			if ( '' === $message ) {
				continue;
			}

			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				wp_kses(
					$message,
					array(
						'br'     => array(),
						'code'   => array(),
						'strong' => array(),
						'em'     => array(),
					)
				)
			);
		}
	}
}
