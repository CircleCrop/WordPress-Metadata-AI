<?php

namespace WMAIGEN\Support;

/**
 * Tiny PHP template renderer for admin views.
 */
final class ViewRenderer {
	/**
	 * @param array<string, mixed> $variables Template variables.
	 */
	public function render( string $template, array $variables = array() ): void {
		$path = WMAIGEN_VIEWS_DIR . $template;

		if ( ! file_exists( $path ) ) {
			return;
		}

		extract( $variables, EXTR_SKIP );
		include $path;
	}
}
