<?php

namespace WMAIGEN\Admin;

use WMAIGEN\Application\TestConnectionService;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\NoticeRepository;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Support\TimeFormatter;
use WMAIGEN\Support\ViewRenderer;

/**
 * Render and handle the plugin settings page.
 */
final class SettingsPage extends AbstractManageOptionsPage {
	public const PAGE_SLUG = 'wmaigen-settings';

	private SettingsRepository $settings_repository;
	private LogRepository $log_repository;
	private TestConnectionService $test_connection_service;

	public function __construct(
		SettingsRepository $settings_repository,
		LogRepository $log_repository,
		NoticeRepository $notice_repository,
		TestConnectionService $test_connection_service,
		ViewRenderer $view_renderer,
		TimeFormatter $time_formatter
	) {
		parent::__construct( $notice_repository, $view_renderer, $time_formatter );

		$this->settings_repository    = $settings_repository;
		$this->log_repository         = $log_repository;
		$this->test_connection_service = $test_connection_service;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_wmaigen_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_wmaigen_test_connection', array( $this, 'handle_test_connection' ) );
	}

	public function register_menu(): void {
		add_options_page(
			__( 'Metadata AI', 'wordpress-metadata-aigen' ),
			__( 'Metadata AI', 'wordpress-metadata-aigen' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		if ( ! $this->can_manage_options() ) {
			return;
		}

		$this->render_template(
			'settings-page.php',
			array(
				'settings'       => $this->settings_repository->get(),
				'logs'           => $this->log_repository->get_recent( 20 ),
			)
		);
	}

	public function handle_save(): void {
		$this->assert_manage_options( __( 'You are not allowed to manage this plugin.', 'wordpress-metadata-aigen' ) );
		check_admin_referer( 'wmaigen_save_settings' );

		$raw_settings = isset( $_POST['wmaigen_settings'] ) && is_array( $_POST['wmaigen_settings'] )
			? $_POST['wmaigen_settings']
			: array();

		$saved_settings = $this->settings_repository->update( $raw_settings );
		$this->log_repository->log(
			'info',
			'settings_saved',
			'success',
			sprintf(
				/* translators: 1: dry run status, 2: think mode */
				__( 'Plugin settings were updated. Dry Run is now %1$s. Think Mode: %2$s.', 'wordpress-metadata-aigen' ),
				! empty( $saved_settings['dry_run'] ) ? __( 'enabled', 'wordpress-metadata-aigen' ) : __( 'disabled', 'wordpress-metadata-aigen' ),
				$this->settings_repository->get_think_mode( $saved_settings )
			),
			array( 'object_kind' => 'system', 'object_subtype' => 'settings', 'object_name' => 'Plugin settings' )
		);
		$this->notice_repository->add(
			'success',
			sprintf(
				/* translators: 1: dry run status, 2: think mode */
				__( 'Settings saved successfully. Dry Run is now %1$s. Think Mode: %2$s.', 'wordpress-metadata-aigen' ),
				! empty( $saved_settings['dry_run'] ) ? __( 'enabled', 'wordpress-metadata-aigen' ) : __( 'disabled', 'wordpress-metadata-aigen' ),
				$this->settings_repository->get_think_mode( $saved_settings )
			)
		);

		$this->redirect_to( $this->get_page_url() );
	}

	public function handle_test_connection(): void {
		$this->assert_manage_options( __( 'You are not allowed to manage this plugin.', 'wordpress-metadata-aigen' ) );
		check_admin_referer( 'wmaigen_test_connection' );

		$result = $this->test_connection_service->run();
		$type   = ! empty( $result['success'] ) ? 'success' : 'error';
		$this->notice_repository->add( $type, (string) $result['message'] );

		$this->redirect_to( $this->get_page_url() );
	}

	private function get_page_url(): string {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
	}
}
