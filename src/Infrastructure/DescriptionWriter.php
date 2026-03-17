<?php

namespace WMAIGEN\Infrastructure;

use WMAIGEN\Domain\GenerationTarget;

/**
 * Persist generated descriptions back into WordPress core fields.
 */
final class DescriptionWriter {
	/**
	 * @return true|\WP_Error
	 */
	public function save( GenerationTarget $target, string $description ) {
		if ( $target->is_post() ) {
			$result = wp_update_post(
				array(
					'ID'           => $target->get_id(),
					'post_excerpt' => $description,
				),
				true
			);

			return is_wp_error( $result ) ? $result : true;
		}

		if ( $target->is_term() ) {
			$result = wp_update_term(
				$target->get_id(),
				$target->get_subtype(),
				array(
					'description' => $description,
				)
			);

			return is_wp_error( $result ) ? $result : true;
		}

		return new \WP_Error( 'wmaigen_unknown_target', __( 'Unknown target type.', 'wordpress-metadata-aigen' ) );
	}
}
