<?php

namespace WMAIGEN\Admin;

use WMAIGEN\Infrastructure\NoticeRepository;
use WMAIGEN\Support\TimeFormatter;
use WMAIGEN\Support\ViewRenderer;

/**
 * Shared helpers for simple manage_options admin pages.
 */
abstract class AbstractManageOptionsPage {
	protected NoticeRepository $notice_repository;
	protected ViewRenderer $view_renderer;
	protected TimeFormatter $time_formatter;

	public function __construct(
		NoticeRepository $notice_repository,
		ViewRenderer $view_renderer,
		TimeFormatter $time_formatter
	) {
		$this->notice_repository = $notice_repository;
		$this->view_renderer     = $view_renderer;
		$this->time_formatter    = $time_formatter;
	}

	protected function can_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	protected function assert_manage_options( string $message ): void {
		if ( $this->can_manage_options() ) {
			return;
		}

		wp_die( esc_html( $message ) );
	}

	protected function render_template( string $template, array $variables = array() ): void {
		if ( ! array_key_exists( 'time_formatter', $variables ) ) {
			$variables['time_formatter'] = $this->time_formatter;
		}

		$this->view_renderer->render( $template, $variables );
	}

	protected function redirect_to( string $url ): void {
		wp_safe_redirect( $url );
		exit;
	}
}
