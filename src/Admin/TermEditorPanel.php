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
 * Add single-item generation controls to category and tag edit screens.
 */
final class TermEditorPanel {
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
	 * Prevent wp_update_term recursion.
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
		foreach ( array_keys( $this->object_type_registry->get_supported_taxonomies() ) as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render_edit_fields' ) );
		}

		add_action( 'edited_term', array( $this, 'handle_edited_term' ), 10, 4 );
	}

	public function render_edit_fields( \WP_Term $term ): void {
		if ( ! $this->object_type_registry->is_supported_taxonomy( $term->taxonomy ) ) {
			return;
		}

		$this->view_renderer->render(
			'term-edit-row.php',
			array(
				'term'    => $term,
				'dry_run' => ! empty( $this->settings_repository->get()['dry_run'] ),
			)
		);
	}

	/**
	 * @param int                 $term_id  Term ID.
	 * @param int                 $tt_id    Term taxonomy ID.
	 * @param string              $taxonomy Taxonomy slug.
	 * @param array<string,mixed> $args     Saved term args.
	 */
	public function handle_edited_term( $term_id, $tt_id, $taxonomy, $args ): void {
		unset( $tt_id, $args );

		if ( $this->is_generating || empty( $_POST['wmaigen_generate_term'] ) ) {
			return;
		}

		if ( ! $this->object_type_registry->is_supported_taxonomy( (string) $taxonomy ) ) {
			return;
		}

		if ( empty( $_POST['wmaigen_generate_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wmaigen_generate_term_nonce'] ) ), 'wmaigen_generate_term' ) ) {
			return;
		}

		$taxonomy_object = get_taxonomy( (string) $taxonomy );

		if ( ! $taxonomy_object instanceof \WP_Taxonomy || ! current_user_can( $taxonomy_object->cap->manage_terms ) ) {
			return;
		}

		$overwrite = ! empty( $_POST['wmaigen_overwrite_existing'] );
		$target    = $this->target_factory->from_term_id( (int) $term_id, (string) $taxonomy );

		if ( is_wp_error( $target ) ) {
			$this->notice_repository->add( 'error', esc_html( $target->get_error_message() ) );
			return;
		}

		$this->is_generating = true;
		$result              = $this->generate_service->generate( $target, $overwrite );
		$this->is_generating = false;

		$this->notice_repository->add( $this->get_notice_type( $result ), $this->build_notice_message( $result ) );
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
}
