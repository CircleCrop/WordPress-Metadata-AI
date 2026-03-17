<?php

namespace WMAIGEN\Admin;

use WMAIGEN\Application\BatchRunService;
use WMAIGEN\Application\BatchScanService;
use WMAIGEN\Infrastructure\BatchStateRepository;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\NoticeRepository;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Support\ObjectTypeRegistry;
use WMAIGEN\Support\TimeFormatter;
use WMAIGEN\Support\ViewRenderer;

/**
 * Batch scan and generation admin page.
 */
final class ToolsPage {
	public const PAGE_SLUG = 'wmaigen-tools';

	/**
	 * @var BatchStateRepository
	 */
	private $batch_state_repository;

	/**
	 * @var BatchScanService
	 */
	private $batch_scan_service;

	/**
	 * @var BatchRunService
	 */
	private $batch_run_service;

	/**
	 * @var LogRepository
	 */
	private $log_repository;

	/**
	 * @var NoticeRepository
	 */
	private $notice_repository;

	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var ObjectTypeRegistry
	 */
	private $object_type_registry;

	/**
	 * @var ViewRenderer
	 */
	private $view_renderer;

	/**
	 * @var TimeFormatter
	 */
	private $time_formatter;

	public function __construct(
		BatchStateRepository $batch_state_repository,
		BatchScanService $batch_scan_service,
		BatchRunService $batch_run_service,
		LogRepository $log_repository,
		NoticeRepository $notice_repository,
		SettingsRepository $settings_repository,
		ObjectTypeRegistry $object_type_registry,
		ViewRenderer $view_renderer,
		TimeFormatter $time_formatter
	) {
		$this->batch_state_repository = $batch_state_repository;
		$this->batch_scan_service     = $batch_scan_service;
		$this->batch_run_service      = $batch_run_service;
		$this->log_repository         = $log_repository;
		$this->notice_repository      = $notice_repository;
		$this->settings_repository    = $settings_repository;
		$this->object_type_registry   = $object_type_registry;
		$this->view_renderer          = $view_renderer;
		$this->time_formatter         = $time_formatter;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_wmaigen_scan_batch', array( $this, 'handle_scan' ) );
		add_action( 'admin_post_wmaigen_run_batch', array( $this, 'handle_run' ) );
	}

	public function register_menu(): void {
		add_management_page(
			__( 'Metadata AI Batch', 'wordpress-metadata-aigen' ),
			__( 'Metadata AI Batch', 'wordpress-metadata-aigen' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$batch_state = $this->batch_state_repository->get();

		$this->view_renderer->render(
			'tools-page.php',
			array(
				'batch_state'          => $batch_state,
				'dry_run'              => ! empty( $this->settings_repository->get()['dry_run'] ),
				'filter_options'       => $this->object_type_registry->get_batch_filter_options(),
				'object_type_registry' => $this->object_type_registry,
				'logs'                 => $this->log_repository->get_recent( 30 ),
				'time_formatter'       => $this->time_formatter,
			)
		);
	}

	public function handle_scan(): void {
		$this->assert_manage_options();
		check_admin_referer( 'wmaigen_scan_batch' );

		$filter = isset( $_POST['object_filter'] ) ? sanitize_key( (string) wp_unslash( $_POST['object_filter'] ) ) : 'all';
		$limit  = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 10;
		$limit  = max( 1, min( 100, $limit ) );
		$options = $this->object_type_registry->get_batch_filter_options();

		if ( 'all' !== $filter && ! isset( $options[ $filter ] ) ) {
			$filter = 'all';
		}

		$candidates = $this->batch_scan_service->scan( $filter, $limit );
		$state      = array(
			'filter'     => $filter,
			'limit'      => $limit,
			'candidates' => array_map(
				static function ( $candidate ) {
					return $candidate->to_state_array();
				},
				$candidates
			),
		);

		$this->batch_state_repository->store( $state );

		if ( empty( $candidates ) ) {
			$this->notice_repository->add( 'warning', __( 'No empty descriptions were found for the current filter.', 'wordpress-metadata-aigen' ) );
		} else {
			$this->notice_repository->add(
				'success',
				sprintf(
					/* translators: %d: number of scan candidates */
					__( 'Scan complete. Found %d candidate(s) with empty descriptions.', 'wordpress-metadata-aigen' ),
					count( $candidates )
				)
			);
		}

		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	public function handle_run(): void {
		$this->assert_manage_options();
		check_admin_referer( 'wmaigen_run_batch' );

		$state = $this->batch_state_repository->get();

		if ( ! is_array( $state ) || empty( $state['candidates'] ) || ! is_array( $state['candidates'] ) ) {
			$this->notice_repository->add( 'error', __( 'No batch preview is available. Run a scan first.', 'wordpress-metadata-aigen' ) );
			wp_safe_redirect( $this->get_page_url() );
			exit;
		}

		$summary = $this->batch_run_service->run( $state['candidates'] );
		$this->batch_state_repository->clear();

		$notice_type = $summary['failed'] > 0 ? 'warning' : 'success';
		$this->notice_repository->add(
			$notice_type,
			sprintf(
				/* translators: 1: processed count, 2: saved count, 3: dry run count, 4: skipped count, 5: failed count */
				__( 'Batch finished. Processed: %1$d, saved: %2$d, dry run: %3$d, skipped: %4$d, failed: %5$d.', 'wordpress-metadata-aigen' ),
				$summary['processed'],
				$summary['saved'],
				$summary['dry_run'],
				$summary['skipped'],
				$summary['failed']
			)
		);

		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	private function assert_manage_options(): void {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_die( esc_html__( 'You are not allowed to use the batch tools.', 'wordpress-metadata-aigen' ) );
	}

	private function get_page_url(): string {
		return admin_url( 'tools.php?page=' . self::PAGE_SLUG );
	}
}
