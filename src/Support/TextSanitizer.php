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

		foreach ( $lines as $line ) {
			$line = (string) $line;
			$line = self::is_block_comment_line( $line )
				? self::collapse_inline_whitespace( $line )
				: self::collapse_inline_whitespace( wp_strip_all_tags( $line ) );

			if ( '' === $line ) {
				continue;
			}

			$normalized_lines[] = $line;
		}

		return self::truncate( implode( "\n", $normalized_lines ), $max_length );
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
}
