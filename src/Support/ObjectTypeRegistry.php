<?php

namespace WMAIGEN\Support;

/**
 * Resolve supported post types and taxonomies for phase one.
 */
final class ObjectTypeRegistry {
	/**
	 * @return array<string, \WP_Post_Type>
	 */
	public function get_supported_post_types(): array {
		$post_types = array();

		foreach ( array( 'post', 'page' ) as $core_type ) {
			$object = get_post_type_object( $core_type );

			if ( $object instanceof \WP_Post_Type ) {
				$post_types[ $core_type ] = $object;
			}
		}

		$custom_types = get_post_types( array( 'show_ui' => true ), 'objects' );

		foreach ( $custom_types as $name => $object ) {
			if ( ! $object instanceof \WP_Post_Type ) {
				continue;
			}

			if ( ! empty( $object->_builtin ) ) {
				continue;
			}

			$post_types[ $name ] = $object;
		}

		return $post_types;
	}

	/**
	 * @return array<string, \WP_Taxonomy>
	 */
	public function get_supported_taxonomies(): array {
		$taxonomies = array();

		foreach ( array( 'category', 'post_tag' ) as $taxonomy_name ) {
			$taxonomy = get_taxonomy( $taxonomy_name );

			if ( $taxonomy instanceof \WP_Taxonomy ) {
				$taxonomies[ $taxonomy_name ] = $taxonomy;
			}
		}

		return $taxonomies;
	}

	public function is_supported_post_type( string $post_type ): bool {
		$post_types = $this->get_supported_post_types();

		return isset( $post_types[ $post_type ] );
	}

	public function is_supported_taxonomy( string $taxonomy ): bool {
		$taxonomies = $this->get_supported_taxonomies();

		return isset( $taxonomies[ $taxonomy ] );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_batch_filter_options(): array {
		$options = array(
			'all' => __( 'All supported objects', 'wordpress-metadata-aigen' ),
		);

		foreach ( $this->get_supported_post_types() as $name => $object ) {
			$options[ $name ] = $object->labels->singular_name;
		}

		foreach ( $this->get_supported_taxonomies() as $name => $taxonomy ) {
			$options[ $name ] = $taxonomy->labels->singular_name;
		}

		return $options;
	}

	public function get_label( string $kind, string $subtype ): string {
		if ( 'post' === $kind ) {
			$post_type = get_post_type_object( $subtype );

			return $post_type instanceof \WP_Post_Type ? $post_type->labels->singular_name : $subtype;
		}

		if ( 'term' === $kind ) {
			$taxonomy = get_taxonomy( $subtype );

			return $taxonomy instanceof \WP_Taxonomy ? $taxonomy->labels->singular_name : $subtype;
		}

		return $subtype;
	}
}
