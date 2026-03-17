<?php

namespace WMAIGEN\Admin;

/**
 * Add single-item generation controls to category and tag edit screens.
 */
final class TermEditorPanel extends AbstractGenerationPanel {
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

		$this->render_panel(
			'term-edit-row.php',
			array(
				'term'    => $term,
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

		if ( $this->is_generation_locked() || empty( $_POST['wmaigen_generate_term'] ) ) {
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

		$this->run_with_generation_lock(
			function () use ( $target, $overwrite ): void {
				$this->process_generation_target( $target, $overwrite );
			}
		);
	}
}
