<?php

namespace WMAIGEN\Support;

/**
 * Text cleanup helpers shared by prompt building, results, and notices.
 */
final class TextSanitizer {
	public static function normalize_for_prompt( string $text, int $max_length = 4000 ): string {
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$text = self::collapse_whitespace( $text );

		return self::truncate( $text, $max_length );
	}

	public static function normalize_multiline_for_prompt( string $text, int $max_length = 4000 ): string {
		$text  = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$lines = preg_split( "/\r\n|\r|\n/", $text );

		if ( ! is_array( $lines ) ) {
			return self::normalize_for_prompt( $text, $max_length );
		}

		$normalized_lines = array();
		$inside_code_fence = false;

		foreach ( $lines as $line ) {
			$line = (string) $line;

			if ( self::is_code_fence_line( $line ) ) {
				$normalized_lines[] = trim( $line );
				$inside_code_fence  = ! $inside_code_fence;
				continue;
			}

			if ( $inside_code_fence ) {
				$normalized_lines[] = rtrim( $line );
				continue;
			}

			$line = self::is_block_comment_line( $line )
				? self::collapse_inline_whitespace( $line )
				: self::collapse_inline_whitespace( wp_strip_all_tags( $line ) );

			if ( '' === $line ) {
				continue;
			}

			$normalized_lines[] = $line;
		}

		return self::truncate_multiline_lines( $normalized_lines, $max_length );
	}

	public static function normalize_generated_text( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$text = self::collapse_whitespace( $text );

		return trim( $text );
	}

	public static function truncate( string $text, int $max_length ): string {
		if ( $max_length <= 0 ) {
			return '';
		}

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $text ) <= $max_length ) {
				return $text;
			}

			return trim( mb_substr( $text, 0, $max_length ) ) . '…';
		}

		if ( strlen( $text ) <= $max_length ) {
			return $text;
		}

		return trim( substr( $text, 0, $max_length ) ) . '…';
	}

	private static function collapse_whitespace( string $text ): string {
		$collapsed = preg_replace( '/\s+/u', ' ', trim( $text ) );

		return is_string( $collapsed ) ? $collapsed : trim( $text );
	}

	private static function collapse_inline_whitespace( string $text ): string {
		$collapsed = preg_replace( '/[^\S\r\n]+/u', ' ', trim( $text ) );

		return is_string( $collapsed ) ? $collapsed : trim( $text );
	}

	private static function is_block_comment_line( string $text ): bool {
		return 1 === preg_match( '/^\s*<!--\s*wp:[\s\S]*-->\s*$/u', $text );
	}

	private static function is_code_fence_line( string $text ): bool {
		return 1 === preg_match( '/^\s*`{3,}[^`]*$/u', $text );
	}

	/**
	 * @param array<int, string> $lines Normalized prompt lines.
	 */
	private static function truncate_multiline_lines( array $lines, int $max_length ): string {
		if ( $max_length <= 0 ) {
			return '';
		}

		$result_lines = array();

		foreach ( $lines as $line ) {
			$candidate_lines   = array_merge( $result_lines, array( $line ) );
			$candidate_content = self::join_lines( $candidate_lines );

			if ( self::string_length( $candidate_content ) <= $max_length ) {
				$result_lines = $candidate_lines;
				continue;
			}

			return self::build_truncated_multiline_result( $result_lines, $max_length );
		}

		return self::join_lines( $result_lines );
	}

	/**
	 * @param array<int, string> $lines Fittable normalized lines.
	 */
	private static function build_truncated_multiline_result( array $lines, int $max_length ): string {
		$suffix_lines = self::get_truncation_suffix_lines( $lines );

		if ( self::string_length( self::join_lines( $suffix_lines ) ) > $max_length ) {
			return self::truncate( '…', $max_length );
		}

		while ( ! empty( $lines ) ) {
			$candidate = self::join_lines( array_merge( $lines, $suffix_lines ) );

			if ( self::string_length( $candidate ) <= $max_length ) {
				return $candidate;
			}

			array_pop( $lines );
			$suffix_lines = self::get_truncation_suffix_lines( $lines );
		}

		return self::join_lines( $suffix_lines );
	}

	/**
	 * @param array<int, string> $lines Fittable normalized lines.
	 * @return array<int, string>
	 */
	private static function get_truncation_suffix_lines( array $lines ): array {
		return self::has_unclosed_code_fence( $lines ) ? array( '```', '…' ) : array( '…' );
	}

	/**
	 * @param array<int, string> $lines Fittable normalized lines.
	 */
	private static function has_unclosed_code_fence( array $lines ): bool {
		$fence_count = 0;

		foreach ( $lines as $line ) {
			if ( self::is_code_fence_line( $line ) ) {
				++$fence_count;
			}
		}

		return 1 === $fence_count % 2;
	}

	/**
	 * @param array<int, string> $lines Prompt lines to join.
	 */
	private static function join_lines( array $lines ): string {
		return implode( "\n", $lines );
	}

	private static function string_length( string $text ): int {
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $text );
		}

		return strlen( $text );
	}
}
