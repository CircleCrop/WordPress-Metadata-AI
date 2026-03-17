<?php

declare(strict_types=1);

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $show ): string { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.showFound
		return 'UTF-8';
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $text ): string {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $value Value to encode.
	 */
	function wp_json_encode( $value, int $flags = 0, int $depth = 512 ): string {
		$encoded = json_encode( $value, $flags, $depth );
		return false === $encoded ? '' : $encoded;
	}
}

if ( ! function_exists( 'parse_blocks' ) ) {
	/**
	 * Minimal local parser for Gutenberg comment blocks.
	 *
	 * It is intentionally conservative and only powers offline example
	 * conversion. WordPress runtime still uses core parse_blocks().
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function parse_blocks( string $content ): array {
		$parser = new WMAIGEN_Example_Block_Parser();
		return $parser->parse( $content );
	}
}

require_once dirname( __DIR__ ) . '/src/Support/TextSanitizer.php';
require_once dirname( __DIR__ ) . '/src/Support/PostContentExtractor.php';

$input_files = array_slice( $argv, 1 );

if ( empty( $input_files ) ) {
	$input_files = glob( dirname( __DIR__ ) . '/example/*.txt' );
}

if ( empty( $input_files ) ) {
	fwrite( STDERR, "No example files found.\n" );
	exit( 1 );
}

$extractor = new \WMAIGEN\Support\PostContentExtractor();

foreach ( $input_files as $input_file ) {
	if ( ! is_string( $input_file ) || ! is_file( $input_file ) ) {
		fwrite( STDERR, sprintf( "Skipping missing file: %s\n", (string) $input_file ) );
		continue;
	}

	$source = file_get_contents( $input_file );

	if ( false === $source ) {
		fwrite( STDERR, sprintf( "Could not read file: %s\n", $input_file ) );
		continue;
	}

	$output = $extractor->extract( $source );
	$output = '' === trim( $output ) ? '' : rtrim( $output ) . "\n";
	$target = $input_file . '.md';

	if ( false === file_put_contents( $target, $output ) ) {
		fwrite( STDERR, sprintf( "Could not write file: %s\n", $target ) );
		continue;
	}

	fwrite( STDOUT, sprintf( "Wrote %s\n", $target ) );
}

/**
 * Tiny parser node used only by the local example converter.
 */
final class WMAIGEN_Example_Block_Node {
	public string $block_name;
	/** @var array<string, mixed> */
	public array $attrs;
	public string $inner_html;
	/** @var array<int, self> */
	public array $inner_blocks;

	/**
	 * @param array<string, mixed> $attrs Parsed block attrs.
	 */
	public function __construct( string $block_name, array $attrs = array() ) {
		$this->block_name   = $block_name;
		$this->attrs        = $attrs;
		$this->inner_html   = '';
		$this->inner_blocks = array();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'blockName'    => $this->block_name,
			'attrs'        => $this->attrs,
			'innerHTML'    => $this->inner_html,
			'innerContent' => '' === $this->inner_html ? array() : array( $this->inner_html ),
			'innerBlocks'  => array_map(
				static function ( WMAIGEN_Example_Block_Node $child ): array {
					return $child->to_array();
				},
				$this->inner_blocks
			),
		);
	}
}

/**
 * Minimal block parser for offline example conversion.
 */
final class WMAIGEN_Example_Block_Parser {
	private const TOKEN_PATTERN = '/<!--\s*(\/)?wp:([a-z0-9_-]+(?:\/[a-z0-9_-]+)?)(?:\s+(\{.*?\}))?\s*(\/)?\s*-->/is';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function parse( string $content ): array {
		$root   = new WMAIGEN_Example_Block_Node( '' );
		$stack  = array( $root );
		$offset = 0;

		while ( preg_match( self::TOKEN_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE, $offset ) ) {
			$token_text  = $matches[0][0];
			$token_start = (int) $matches[0][1];
			$token_end   = $token_start + strlen( $token_text );
			$this->append_html( $stack, substr( $content, $offset, $token_start - $offset ) );

			$is_closing   = isset( $matches[1][0] ) && '' !== $matches[1][0];
			$raw_name     = isset( $matches[2][0] ) ? strtolower( (string) $matches[2][0] ) : '';
			$block_name   = $this->normalize_block_name( $raw_name );
			$is_self_closing = ! $is_closing && isset( $matches[4][0] ) && '' !== $matches[4][0];

			if ( $is_closing ) {
				$this->close_block( $stack, $block_name );
			} else {
				$node = new WMAIGEN_Example_Block_Node( $block_name, $this->decode_attrs( isset( $matches[3][0] ) ? (string) $matches[3][0] : '' ) );

				if ( $is_self_closing ) {
					$this->current_node( $stack )->inner_blocks[] = $node;
				} else {
					$stack[] = $node;
				}
			}

			$offset = $token_end;
		}

		$this->append_html( $stack, substr( $content, $offset ) );

		while ( count( $stack ) > 1 ) {
			$node                        = array_pop( $stack );
			$this->current_node( $stack )->inner_blocks[] = $node;
		}

		return array_map(
			static function ( WMAIGEN_Example_Block_Node $node ): array {
				return $node->to_array();
			},
			$root->inner_blocks
		);
	}

	/**
	 * @param array<int, WMAIGEN_Example_Block_Node> $stack Active node stack.
	 */
	private function append_html( array &$stack, string $html ): void {
		$this->current_node( $stack )->inner_html .= $html;
	}

	/**
	 * @param array<int, WMAIGEN_Example_Block_Node> $stack Active node stack.
	 */
	private function close_block( array &$stack, string $block_name ): void {
		while ( count( $stack ) > 1 ) {
			$node                        = array_pop( $stack );
			$this->current_node( $stack )->inner_blocks[] = $node;

			if ( $node->block_name === $block_name ) {
				return;
			}
		}
	}

	/**
	 * @param array<int, WMAIGEN_Example_Block_Node> $stack Active node stack.
	 */
	private function current_node( array &$stack ): WMAIGEN_Example_Block_Node {
		return $stack[ count( $stack ) - 1 ];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decode_attrs( string $json ): array {
		$json = trim( $json );

		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	private function normalize_block_name( string $raw_name ): string {
		return false === strpos( $raw_name, '/' ) ? 'core/' . $raw_name : $raw_name;
	}
}
