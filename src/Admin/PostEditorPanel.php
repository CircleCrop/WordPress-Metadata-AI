<?php

namespace WMAIGEN\Admin;

use WMAIGEN\Application\GenerateDescriptionService;
use WMAIGEN\Domain\GenerationResult;
use WMAIGEN\Infrastructure\NoticeRepository;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Infrastructure\TargetFactory;
use WMAIGEN\Support\ObjectTypeRegistry;
use WMAIGEN\Support\ViewRenderer;

/**
 * Add single-item generation controls to supported post edit screens.
 */
final class PostEditorPanel {
	/**
	 * @var ObjectTypeRegistry
	 */
	private $object_type_registry;

	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var TargetFactory
	 */
	private $target_factory;

	/**
	 * @var GenerateDescriptionService
	 */
	private $generate_service;

	/**
	 * @var NoticeRepository
	 */
	private $notice_repository;

	/**
	 * @var ViewRenderer
	 */
	private $view_renderer;

	/**
	 * Prevent save recursion while excerpt updates are written.
	 *
	 * @var bool
	 */
	private $is_generating = false;

	public function __construct(
		ObjectTypeRegistry $object_type_registry,
		SettingsRepository $settings_repository,
		TargetFactory $target_factory,
		GenerateDescriptionService $generate_service,
		NoticeRepository $notice_repository,
		ViewRenderer $view_renderer
	) {
		$this->object_type_registry = $object_type_registry;
		$this->settings_repository  = $settings_repository;
		$this->target_factory       = $target_factory;
		$this->generate_service     = $generate_service;
		$this->notice_repository    = $notice_repository;
		$this->view_renderer        = $view_renderer;
	}

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 3 );
		add_action( 'admin_post_wmaigen_generate_post', array( $this, 'handle_generate_request' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_block_editor_script' ) );
	}

	public function register_meta_boxes(): void {
		foreach ( array_keys( $this->object_type_registry->get_supported_post_types() ) as $post_type ) {
			add_meta_box(
				'wmaigen-description-panel',
				__( 'AI Description Generator', 'wordpress-metadata-aigen' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	public function render_meta_box( \WP_Post $post ): void {
		$this->view_renderer->render(
			'post-meta-box.php',
			array(
				'post'          => $post,
				'dry_run'       => ! empty( $this->settings_repository->get()['dry_run'] ),
				'generate_url'  => admin_url( 'admin-post.php' ),
				'redirect_url'  => (string) get_edit_post_link( $post->ID, 'raw' ),
			)
		);
	}

	/**
	 * Load the block editor helper only where it is needed.
	 */
	public function enqueue_block_editor_script( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof \WP_Screen || empty( $screen->post_type ) ) {
			return;
		}

		if ( ! $this->object_type_registry->is_supported_post_type( (string) $screen->post_type ) ) {
			return;
		}

		if ( ! function_exists( 'use_block_editor_for_post_type' ) || ! use_block_editor_for_post_type( (string) $screen->post_type ) ) {
			return;
		}

		wp_enqueue_script(
			'wmaigen-post-editor-generate',
			plugin_dir_url( WMAIGEN_PLUGIN_FILE ) . 'assets/js/post-editor-generate.js',
			array( 'wp-data', 'wp-dom-ready' ),
			WMAIGEN_VERSION,
			true
		);
	}

	/**
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Saved post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public function handle_save_post( $post_id, $post, $update ): void {
		unset( $update );

		if ( $this->is_generating || ! $post instanceof \WP_Post ) {
			return;
		}

		if ( empty( $_POST['wmaigen_generate_post'] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $this->object_type_registry->is_supported_post_type( $post->post_type ) ) {
			return;
		}

		if ( empty( $_POST['wmaigen_generate_post_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wmaigen_generate_post_nonce'] ) ), 'wmaigen_generate_post' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$overwrite = ! empty( $_POST['wmaigen_overwrite_existing'] );
		$target    = $this->target_factory->from_post( $post );

		if ( is_wp_error( $target ) ) {
			$this->notice_repository->add( 'error', esc_html( $target->get_error_message() ) );
			return;
		}

		$this->is_generating = true;
		$result              = $this->generate_service->generate( $target, $overwrite );
		$this->is_generating = false;

		$this->notice_repository->add( $this->get_notice_type( $result ), $this->build_notice_message( $result ) );
	}

	/**
	 * Dedicated request handler used by the block editor helper.
	 */
	public function handle_generate_request(): void {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( $post_id <= 0 ) {
			wp_die( esc_html__( 'Invalid post ID for description generation.', 'wordpress-metadata-aigen' ) );
		}

		check_admin_referer( 'wmaigen_generate_post', 'wmaigen_generate_post_nonce' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to generate a description for this post.', 'wordpress-metadata-aigen' ) );
		}

		$overwrite = ! empty( $_POST['wmaigen_overwrite_existing'] );
		$target    = $this->target_factory->from_post_id( $post_id );

		if ( is_wp_error( $target ) ) {
			$this->notice_repository->add( 'error', esc_html( $target->get_error_message() ) );
			wp_safe_redirect( $this->get_post_redirect_url( $post_id ) );
			exit;
		}

		$result = $this->generate_service->generate( $target, $overwrite );
		$this->notice_repository->add( $this->get_notice_type( $result ), $this->build_notice_message( $result ) );

		wp_safe_redirect( $this->get_post_redirect_url( $post_id ) );
		exit;
	}

	private function get_notice_type( GenerationResult $result ): string {
		if ( $result->is_saved() ) {
			return 'success';
		}

		if ( $result->is_dry_run() ) {
			return 'info';
		}

		if ( $result->is_skipped() ) {
			return 'warning';
		}

		return 'error';
	}

	private function build_notice_message( GenerationResult $result ): string {
		if ( $result->is_dry_run() || $result->is_saved() ) {
			return sprintf(
				'%1$s<br><strong>%2$s</strong> <code>%3$s</code>',
				esc_html( $result->get_message() ),
				esc_html__( 'Generated description:', 'wordpress-metadata-aigen' ),
				esc_html( $result->get_description() )
			);
		}

		return esc_html( $result->get_message() );
	}

	private function get_post_redirect_url( int $post_id ): string {
		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( (string) wp_unslash( $_POST['redirect_to'] ) ) : '';

		if ( '' !== $redirect ) {
			return $redirect;
		}

		$edit_link = get_edit_post_link( $post_id, 'raw' );

		if ( ! empty( $edit_link ) ) {
			return (string) $edit_link;
		}

		return admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	}
}
