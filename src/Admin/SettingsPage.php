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
final class SettingsPage {
	public const PAGE_SLUG = 'wmaigen-settings';

	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var LogRepository
	 */
	private $log_repository;

	/**
	 * @var NoticeRepository
	 */
	private $notice_repository;

	/**
	 * @var TestConnectionService
	 */
	private $test_connection_service;

	/**
	 * @var ViewRenderer
	 */
	private $view_renderer;

	/**
	 * @var TimeFormatter
	 */
	private $time_formatter;

	public function __construct(
		SettingsRepository $settings_repository,
		LogRepository $log_repository,
		NoticeRepository $notice_repository,
		TestConnectionService $test_connection_service,
		ViewRenderer $view_renderer,
		TimeFormatter $time_formatter
	) {
		$this->settings_repository    = $settings_repository;
		$this->log_repository         = $log_repository;
		$this->notice_repository      = $notice_repository;
		$this->test_connection_service = $test_connection_service;
		$this->view_renderer          = $view_renderer;
		$this->time_formatter         = $time_formatter;
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->view_renderer->render(
			'settings-page.php',
			array(
				'settings'       => $this->settings_repository->get(),
				'logs'           => $this->log_repository->get_recent( 20 ),
				'time_formatter' => $this->time_formatter,
			)
		);
	}

	public function handle_save(): void {
		$this->assert_manage_options();
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
				/* translators: %s: dry run status */
				__( 'Plugin settings were updated. Dry Run is now %s.', 'wordpress-metadata-aigen' ),
				! empty( $saved_settings['dry_run'] ) ? __( 'enabled', 'wordpress-metadata-aigen' ) : __( 'disabled', 'wordpress-metadata-aigen' )
			),
			array( 'object_kind' => 'system', 'object_subtype' => 'settings', 'object_name' => 'Plugin settings' )
		);
		$this->notice_repository->add(
			'success',
			sprintf(
				/* translators: %s: dry run status */
				__( 'Settings saved successfully. Dry Run is now %s.', 'wordpress-metadata-aigen' ),
				! empty( $saved_settings['dry_run'] ) ? __( 'enabled', 'wordpress-metadata-aigen' ) : __( 'disabled', 'wordpress-metadata-aigen' )
			)
		);

		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	public function handle_test_connection(): void {
		$this->assert_manage_options();
		check_admin_referer( 'wmaigen_test_connection' );

		$result = $this->test_connection_service->run();
		$type   = ! empty( $result['success'] ) ? 'success' : 'error';
		$this->notice_repository->add( $type, (string) $result['message'] );

		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	private function assert_manage_options(): void {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_die( esc_html__( 'You are not allowed to manage this plugin.', 'wordpress-metadata-aigen' ) );
	}

	private function get_page_url(): string {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
	}
}
