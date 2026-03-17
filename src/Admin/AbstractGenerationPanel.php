<?php

namespace WMAIGEN\Admin;

use WMAIGEN\Application\GenerateDescriptionService;
use WMAIGEN\Domain\GenerationResult;
use WMAIGEN\Infrastructure\NoticeRepository;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Infrastructure\TargetFactory;
use WMAIGEN\Support\GenerationNoticeFormatter;
use WMAIGEN\Support\ObjectTypeRegistry;
use WMAIGEN\Support\ViewRenderer;

/**
 * Shared plumbing for single-item generation panels.
 *
 * Post and term editors share the same dependencies and notice flow, so this
 * base class keeps the controllers focused on the WordPress hook differences.
 */
abstract class AbstractGenerationPanel {
	protected ObjectTypeRegistry $object_type_registry;
	protected SettingsRepository $settings_repository;
	protected TargetFactory $target_factory;
	protected GenerateDescriptionService $generate_service;
	protected NoticeRepository $notice_repository;
	protected GenerationNoticeFormatter $notice_formatter;
	protected ViewRenderer $view_renderer;
	private bool $is_generating = false;

	public function __construct(
		ObjectTypeRegistry $object_type_registry,
		SettingsRepository $settings_repository,
		TargetFactory $target_factory,
		GenerateDescriptionService $generate_service,
		NoticeRepository $notice_repository,
		GenerationNoticeFormatter $notice_formatter,
		ViewRenderer $view_renderer
	) {
		$this->object_type_registry = $object_type_registry;
		$this->settings_repository  = $settings_repository;
		$this->target_factory       = $target_factory;
		$this->generate_service     = $generate_service;
		$this->notice_repository    = $notice_repository;
		$this->notice_formatter     = $notice_formatter;
		$this->view_renderer        = $view_renderer;
	}

	protected function render_panel( string $template, array $variables = array() ): void {
		if ( ! array_key_exists( 'dry_run', $variables ) ) {
			$variables['dry_run'] = $this->settings_repository->is_dry_run_enabled();
		}

		$this->view_renderer->render( $template, $variables );
	}

	protected function is_generation_locked(): bool {
		return $this->is_generating;
	}

	protected function run_with_generation_lock( callable $callback ): void {
		if ( $this->is_generating ) {
			return;
		}

		$this->is_generating = true;

		try {
			$callback();
		} finally {
			$this->is_generating = false;
		}
	}

	/**
	 * @param \WMAIGEN\Domain\GenerationTarget|\WP_Error $target Target lookup result.
	 */
	protected function process_generation_target( $target, bool $overwrite ): void {
		if ( is_wp_error( $target ) ) {
			$this->notice_repository->add( 'error', esc_html( $target->get_error_message() ) );
			return;
		}

		$this->add_generation_notice( $this->generate_service->generate( $target, $overwrite ) );
	}

	protected function add_generation_notice( GenerationResult $result ): void {
		$this->notice_repository->add(
			$this->notice_formatter->get_type( $result ),
			$this->notice_formatter->get_message( $result )
		);
	}
}
