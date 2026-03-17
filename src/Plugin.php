<?php

namespace WMAIGEN;

use WMAIGEN\Admin\AdminNotices;
use WMAIGEN\Admin\PostEditorPanel;
use WMAIGEN\Admin\SettingsPage;
use WMAIGEN\Admin\TermEditorPanel;
use WMAIGEN\Admin\ToolsPage;
use WMAIGEN\Application\BatchRunService;
use WMAIGEN\Application\BatchScanService;
use WMAIGEN\Application\GenerateDescriptionService;
use WMAIGEN\Application\TestConnectionService;
use WMAIGEN\Infrastructure\BatchStateRepository;
use WMAIGEN\Infrastructure\DescriptionWriter;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\NoticeRepository;
use WMAIGEN\Infrastructure\OpenAIClient;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Infrastructure\TargetFactory;
use WMAIGEN\Support\GenerationNoticeFormatter;
use WMAIGEN\Support\ObjectTypeRegistry;
use WMAIGEN\Support\PostContentExtractor;
use WMAIGEN\Support\TimeFormatter;
use WMAIGEN\Support\ViewRenderer;

/**
 * Main plugin bootstrapper.
 */
final class Plugin {
	/**
	 * Singleton plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin notice controller.
	 *
	 * @var AdminNotices
	 */
	private $admin_notices;

	/**
	 * Settings page controller.
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Tools page controller.
	 *
	 * @var ToolsPage
	 */
	private $tools_page;

	/**
	 * Post editor integration.
	 *
	 * @var PostEditorPanel
	 */
	private $post_editor_panel;

	/**
	 * Term editor integration.
	 *
	 * @var TermEditorPanel
	 */
	private $term_editor_panel;

	/**
	 * Boot the plugin once.
	 */
	public static function bootstrap(): void {
		if ( self::$instance instanceof self ) {
			return;
		}

		self::$instance = new self();
		self::$instance->register_hooks();
	}

	/**
	 * Build the dependency graph.
	 */
	private function __construct() {
		$settings_repository  = new SettingsRepository();
		$log_repository       = new LogRepository();
		$notice_repository    = new NoticeRepository();
		$batch_state_repo     = new BatchStateRepository();
		$object_type_registry = new ObjectTypeRegistry();
		$post_content_extractor = new PostContentExtractor();
		$notice_formatter     = new GenerationNoticeFormatter();
		$view_renderer        = new ViewRenderer();
		$time_formatter       = new TimeFormatter();
		$target_factory       = new TargetFactory( $object_type_registry, $post_content_extractor );
		$description_writer   = new DescriptionWriter();
		$openai_client        = new OpenAIClient();

		$generate_service = new GenerateDescriptionService(
			$settings_repository,
			$log_repository,
			$openai_client,
			$description_writer
		);

		$test_connection_service = new TestConnectionService(
			$settings_repository,
			$log_repository,
			$openai_client
		);

		$batch_scan_service = new BatchScanService(
			$object_type_registry,
			$target_factory,
			$log_repository
		);

		$batch_run_service = new BatchRunService(
			$target_factory,
			$generate_service,
			$log_repository
		);

		$this->admin_notices = new AdminNotices( $notice_repository );
		$this->settings_page = new SettingsPage(
			$settings_repository,
			$log_repository,
			$notice_repository,
			$test_connection_service,
			$view_renderer,
			$time_formatter
		);
		$this->tools_page    = new ToolsPage(
			$batch_state_repo,
			$batch_scan_service,
			$batch_run_service,
			$log_repository,
			$notice_repository,
			$settings_repository,
			$object_type_registry,
			$view_renderer,
			$time_formatter
		);
		$this->post_editor_panel = new PostEditorPanel(
			$object_type_registry,
			$settings_repository,
			$target_factory,
			$generate_service,
			$notice_repository,
			$notice_formatter,
			$view_renderer
		);
		$this->term_editor_panel = new TermEditorPanel(
			$object_type_registry,
			$settings_repository,
			$target_factory,
			$generate_service,
			$notice_repository,
			$notice_formatter,
			$view_renderer
		);
	}

	/**
	 * Register top-level plugin hooks.
	 */
	private function register_hooks(): void {
		add_action( 'init', array( $this, 'load_text_domain' ) );

		$this->admin_notices->register();
		$this->settings_page->register();
		$this->tools_page->register();
		$this->post_editor_panel->register();
		$this->term_editor_panel->register();
	}

	/**
	 * Load the text domain for translations.
	 */
	public function load_text_domain(): void {
		load_plugin_textdomain( 'wordpress-metadata-aigen', false, dirname( plugin_basename( WMAIGEN_PLUGIN_FILE ) ) . '/languages' );
	}
}
