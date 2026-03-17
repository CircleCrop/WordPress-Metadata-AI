<?php

namespace WMAIGEN\Infrastructure;

use WMAIGEN\Domain\GenerationTarget;
use WMAIGEN\Support\ObjectTypeRegistry;

/**
 * Build unified generation targets from WordPress objects.
 */
final class TargetFactory {
	/**
	 * Supported object registry.
	 *
	 * @var ObjectTypeRegistry
	 */
	private $object_type_registry;

	public function __construct( ObjectTypeRegistry $object_type_registry ) {
		$this->object_type_registry = $object_type_registry;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return GenerationTarget|\WP_Error
	 */
	public function from_post_id( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error( 'wmaigen_missing_post', __( 'The selected post no longer exists.', 'wordpress-metadata-aigen' ) );
		}

		return $this->from_post( $post );
	}

	/**
	 * @return GenerationTarget|\WP_Error
	 */
	public function from_post( \WP_Post $post ) {
		if ( ! $this->object_type_registry->is_supported_post_type( $post->post_type ) ) {
			return new \WP_Error( 'wmaigen_unsupported_post_type', __( 'This post type is not supported in phase one.', 'wordpress-metadata-aigen' ) );
		}

		return new GenerationTarget(
			'post',
			(string) $post->post_type,
			(int) $post->ID,
			(string) get_the_title( $post ),
			(string) $post->post_content,
			(string) $post->post_excerpt,
			(string) get_edit_post_link( $post->ID, 'raw' )
		);
	}

	/**
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return GenerationTarget|\WP_Error
	 */
	public function from_term_id( int $term_id, string $taxonomy ) {
		$term = get_term( $term_id, $taxonomy );

		if ( ! $term instanceof \WP_Term ) {
			return new \WP_Error( 'wmaigen_missing_term', __( 'The selected taxonomy term no longer exists.', 'wordpress-metadata-aigen' ) );
		}

		return $this->from_term( $term );
	}

	/**
	 * @return GenerationTarget|\WP_Error
	 */
	public function from_term( \WP_Term $term ) {
		if ( ! $this->object_type_registry->is_supported_taxonomy( $term->taxonomy ) ) {
			return new \WP_Error( 'wmaigen_unsupported_taxonomy', __( 'This taxonomy is not supported in phase one.', 'wordpress-metadata-aigen' ) );
		}

		$edit_link = add_query_arg(
			array(
				'taxonomy' => $term->taxonomy,
				'tag_ID'   => $term->term_id,
			),
			admin_url( 'term.php' )
		);

		return new GenerationTarget(
			'term',
			(string) $term->taxonomy,
			(int) $term->term_id,
			(string) $term->name,
			(string) $term->description,
			(string) $term->description,
			$edit_link
		);
	}
}
