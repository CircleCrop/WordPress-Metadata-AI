<?php

namespace WMAIGEN\Support;

/**
 * Convert block editor content into prompt-friendly plain text.
 *
 * The goal is not pixel-perfect rendering. It keeps semantic text that helps
 * the model understand the article, while dropping layout wrappers and other
 * low-signal block noise.
 */
final class PostContentExtractor {
	/**
	 * @param string $post_content Raw post_content from WordPress.
	 */
	public function extract( string $post_content ): string {
		if ( '' === trim( $post_content ) ) {
			return '';
		}

		if ( ! function_exists( 'parse_blocks' ) || false === strpos( $post_content, '<!-- wp:' ) ) {
			return $this->extract_visible_text( $post_content );
		}

		$blocks = parse_blocks( $post_content );

		if ( ! is_array( $blocks ) || empty( $blocks ) ) {
			return $this->extract_visible_text( $post_content );
		}

		$lines = $this->extract_lines_from_blocks( $blocks );

		if ( empty( $lines ) ) {
			return $this->extract_visible_text( $post_content );
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param array<int, mixed> $blocks Parsed blocks from WordPress.
	 * @return array<int, string>
	 */
	private function extract_lines_from_blocks( array $blocks ): array {
		$lines = array();

		foreach ( $blocks as $block ) {
			foreach ( $this->extract_lines_from_block( $block ) as $line ) {
				if ( '' === $line ) {
					continue;
				}

				$lines[] = $line;
			}
		}

		return $lines;
	}

	/**
	 * @param mixed $block Parsed block payload.
	 * @return array<int, string>
	 */
	private function extract_lines_from_block( $block ): array {
		if ( ! is_array( $block ) ) {
			return array();
		}

		$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

		if ( '' === $block_name ) {
			return $this->wrap_line( $this->extract_text_fallback( $block ) );
		}

		if ( $this->should_skip_block( $block_name ) ) {
			return array();
		}

		if ( $this->is_layout_block( $block_name ) ) {
			return $this->extract_inner_block_lines( $block );
		}

		if ( 'core/heading' === $block_name ) {
			return $this->wrap_line( $this->format_heading( $block ) );
		}

		if ( 'core/list-item' === $block_name ) {
			return $this->wrap_line( $this->format_list_item( $block ) );
		}

		if ( 'core/code' === $block_name ) {
			return $this->wrap_line( $this->format_code_block( $block ) );
		}

		if ( 'core/html' === $block_name ) {
			return $this->wrap_line( $this->format_html_block( $block ) );
		}

		$lines = array();

		if ( $this->is_custom_context_block( $block_name ) ) {
			$summary = $this->format_custom_block_summary( $block_name, isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array() );

			if ( '' !== $summary ) {
				$lines[] = $summary;
			}
		}

		$inner_lines = $this->extract_inner_block_lines( $block );

		if ( ! empty( $inner_lines ) ) {
			return array_merge( $lines, $inner_lines );
		}

		$fallback_text = $this->extract_text_fallback( $block );

		if ( '' !== $fallback_text ) {
			$lines[] = $fallback_text;
		}

		return $lines;
	}

	/**
	 * @param array<string, mixed> $block Parsed block payload.
	 * @return array<int, string>
	 */
	private function extract_inner_block_lines( array $block ): array {
		if ( ! isset( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) ) {
			return array();
		}

		return $this->extract_lines_from_blocks( $block['innerBlocks'] );
	}

	private function should_skip_block( string $block_name ): bool {
		return in_array(
			$block_name,
			array(
				'core/footnotes',
				'core/spacer',
				'core/separator',
				'core/nextpage',
				'core/more',
			),
			true
		);
	}

	private function is_layout_block( string $block_name ): bool {
		return in_array(
			$block_name,
			array(
				'core/columns',
				'core/column',
				'core/group',
				'core/row',
				'core/stack',
				'core/buttons',
			),
			true
		);
	}

	private function is_custom_context_block( string $block_name ): bool {
		return 0 !== strpos( $block_name, 'core/' );
	}

	/**
	 * @param array<string, mixed> $block Parsed block payload.
	 */
	private function format_heading( array $block ): string {
		$level = 2;

		if ( isset( $block['attrs']['level'] ) ) {
			$level = (int) $block['attrs']['level'];
		}

		$level = max( 1, min( 6, $level ) );
		$text  = $this->extract_text_fallback( $block );

		if ( '' === $text ) {
			return '';
		}

		return sprintf( '%1$s %2$s', str_repeat( '#', $level ), $text );
	}

	/**
	 * @param array<string, mixed> $block Parsed block payload.
	 */
	private function format_list_item( array $block ): string {
		$text = $this->extract_text_fallback( $block );

		if ( '' === $text ) {
			return '';
		}

		return '- ' . $text;
	}

	/**
	 * @param array<string, mixed> $block Parsed block payload.
	 */
	private function format_html_block( array $block ): string {
		$html = '';

		if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
			$html = $block['innerHTML'];
		}

		if ( $this->looks_like_code_block( $html ) ) {
			return $this->build_fenced_code_block(
				$this->extract_code_block_text( $html ),
				$this->detect_code_language( $html )
			);
		}

		$text = $this->extract_visible_text( $html );

		if ( '' === $text ) {
			return '';
		}

		return $text;
	}

	/**
	 * @param array<string, mixed> $block Parsed block payload.
	 */
	private function format_code_block( array $block ): string {
		$html = '';

		if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
			$html = $block['innerHTML'];
		}

		$language = '';

		if ( isset( $block['attrs']['className'] ) && is_string( $block['attrs']['className'] ) ) {
			$language = $this->detect_language_from_class_names( $block['attrs']['className'] );
		}

		if ( '' === $language ) {
			$language = $this->detect_code_language( $html );
		}

		return $this->build_fenced_code_block(
			$this->extract_code_block_text( $html ),
			$language
		);
	}

	/**
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	private function format_custom_block_summary( string $block_name, array $attributes ): string {
		$filtered_attributes = $this->filter_context_attributes( $attributes );

		if ( empty( $filtered_attributes ) ) {
			return '';
		}

		$json = wp_json_encode( $filtered_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( ! is_string( $json ) || '' === $json ) {
			return '';
		}

		return sprintf( '<!-- wp:%1$s %2$s /-->', $block_name, $json );
	}

	/**
	 * @param mixed $value Attribute value to inspect.
	 * @return mixed|null
	 */
	private function filter_context_attributes( $value, string $attribute_name = '' ) {
		if ( is_array( $value ) ) {
			$filtered = array();

			foreach ( $value as $key => $child_value ) {
				if ( ! is_string( $key ) && ! is_int( $key ) ) {
					continue;
				}

				$filtered_child = $this->filter_context_attributes( $child_value, (string) $key );

				if ( null === $filtered_child ) {
					continue;
				}

				$filtered[ $key ] = $filtered_child;
			}

			return empty( $filtered ) ? null : $filtered;
		}

		if ( ! is_scalar( $value ) ) {
			return null;
		}

		if ( ! $this->is_context_attribute_name( $attribute_name ) ) {
			return null;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		$text = TextSanitizer::normalize_for_prompt( (string) $value, 300 );

		return '' === $text ? null : $text;
	}

	private function is_context_attribute_name( string $attribute_name ): bool {
		$attribute_name = strtolower( $attribute_name );

		return false !== strpos( $attribute_name, 'title' ) || false !== strpos( $attribute_name, 'description' );
	}

	/**
	 * @param array<string, mixed> $block Parsed block payload.
	 */
	private function extract_text_fallback( array $block ): string {
		$html = '';

		if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
			$html = $block['innerHTML'];
		} elseif ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
			$html = $this->join_inner_content_strings( $block['innerContent'] );
		}

		return $this->extract_visible_text( $html );
	}

	/**
	 * @param array<int, mixed> $inner_content Raw block innerContent values.
	 */
	private function join_inner_content_strings( array $inner_content ): string {
		$parts = array();

		foreach ( $inner_content as $chunk ) {
			if ( is_string( $chunk ) ) {
				$parts[] = $chunk;
			}
		}

		return implode( '', $parts );
	}

	private function extract_visible_text( string $html ): string {
		$html = preg_replace( '/<(br|\/p|\/div|\/li|\/blockquote|\/pre|\/h[1-6])\b[^>]*>/i', "$0\n", $html );
		$text = wp_strip_all_tags( is_string( $html ) ? $html : '' );

		return TextSanitizer::normalize_multiline_for_prompt( $text, 4000 );
	}

	private function looks_like_code_block( string $html ): bool {
		return false !== stripos( $html, '<pre' ) || false !== stripos( $html, '<code' );
	}

	private function extract_code_block_text( string $html ): string {
		$code = $html;

		if ( preg_match( '/<code\b[^>]*>(.*?)<\/code>/is', $html, $matches ) && isset( $matches[1] ) ) {
			$code = (string) $matches[1];
		} elseif ( preg_match( '/<pre\b[^>]*>(.*?)<\/pre>/is', $html, $matches ) && isset( $matches[1] ) ) {
			$code = (string) $matches[1];
		}

		$code  = html_entity_decode( $code, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$code  = str_replace( array( "\r\n", "\r" ), "\n", $code );
		$code  = wp_strip_all_tags( $code );
		$lines = preg_split( "/\n/", $code );

		if ( ! is_array( $lines ) ) {
			return trim( $code );
		}

		$normalized_lines = array_map(
			static function ( string $line ): string {
				return rtrim( $line );
			},
			$lines
		);

		return trim( implode( "\n", $normalized_lines ), "\n" );
	}

	private function detect_code_language( string $html ): string {
		if ( preg_match( '/(?:lang-|language-)([a-z0-9_+-]+)/i', $html, $matches ) && isset( $matches[1] ) ) {
			return strtolower( (string) $matches[1] );
		}

		if ( preg_match_all( '/\bclass=(["\'])(.*?)\1/is', $html, $matches ) && isset( $matches[2] ) && is_array( $matches[2] ) ) {
			foreach ( $matches[2] as $class_names ) {
				$language = $this->detect_language_from_class_names( (string) $class_names );

				if ( '' !== $language ) {
					return $language;
				}
			}
		}

		return '';
	}

	private function detect_language_from_class_names( string $class_names ): string {
		if ( preg_match( '/(?:^|\s)lang-([a-z0-9_+-]+)(?:\s|$)/i', $class_names, $matches ) && isset( $matches[1] ) ) {
			return strtolower( (string) $matches[1] );
		}

		if ( preg_match( '/(?:^|\s)language-([a-z0-9_+-]+)(?:\s|$)/i', $class_names, $matches ) && isset( $matches[1] ) ) {
			return strtolower( (string) $matches[1] );
		}

		return '';
	}

	private function build_fenced_code_block( string $code, string $language = '' ): string {
		$code = trim( $code, "\n" );

		if ( '' === $code ) {
			return '';
		}

		$fence = false !== strpos( $code, '```' ) ? '````' : '```';

		return sprintf( "%1\$s%2\$s\n%3\$s\n%1\$s", $fence, $language, $code );
	}

	/**
	 * @return array<int, string>
	 */
	private function wrap_line( string $line ): array {
		return '' === $line ? array() : array( $line );
	}
}
