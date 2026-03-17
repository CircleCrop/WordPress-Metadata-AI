<?php

namespace WMAIGEN\Infrastructure;

/**
 * Option-backed plugin settings storage.
 */
final class SettingsRepository {
	public const OPTION_NAME = 'wmaigen_settings';

	/**
	 * @return array<string, mixed>
	 */
	public function get(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, $this->get_defaults() );
	}

	/**
	 * @param array<string, mixed> $raw_settings Settings from the request.
	 * @return array<string, mixed>
	 */
	public function update( array $raw_settings ): array {
		$sanitized = $this->sanitize( $raw_settings );
		update_option( self::OPTION_NAME, $sanitized, false );

		return $sanitized;
	}

	/**
	 * @param array<string, mixed>|null $settings Settings override.
	 */
	public function is_configured( ?array $settings = null ): bool {
		$current = is_array( $settings ) ? $settings : $this->get();

		return '' !== $current['base_url'] && '' !== $current['api_key'] && '' !== $current['model'];
	}

	/**
	 * @param array<string, mixed>|null $settings Settings override.
	 */
	public function is_dry_run_enabled( ?array $settings = null ): bool {
		$current = is_array( $settings ) ? $settings : $this->get();

		return ! empty( $current['dry_run'] );
	}

	/**
	 * @param array<string, mixed> $raw_settings Untrusted settings input.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $raw_settings ): array {
		$defaults = $this->get_defaults();
		$base_url = array_key_exists( 'base_url', $raw_settings )
			? esc_url_raw( trim( (string) wp_unslash( $raw_settings['base_url'] ) ) )
			: (string) $defaults['base_url'];
		$base_url = rtrim( $base_url, '/' );

		if ( $this->ends_with( $base_url, '/chat/completions' ) ) {
			$base_url = substr( $base_url, 0, - strlen( '/chat/completions' ) );
		}

		return array(
			'base_url'         => $base_url,
			'api_key'          => array_key_exists( 'api_key', $raw_settings ) ? sanitize_text_field( (string) wp_unslash( $raw_settings['api_key'] ) ) : (string) $defaults['api_key'],
			'model'            => array_key_exists( 'model', $raw_settings ) ? sanitize_text_field( (string) wp_unslash( $raw_settings['model'] ) ) : (string) $defaults['model'],
			'timeout'          => $this->normalize_timeout( array_key_exists( 'timeout', $raw_settings ) ? $raw_settings['timeout'] : $defaults['timeout'] ),
			'dry_run'          => array_key_exists( 'dry_run', $raw_settings ) && ! empty( $raw_settings['dry_run'] ) ? 1 : 0,
			'prompt_post_like' => array_key_exists( 'prompt_post_like', $raw_settings ) ? sanitize_textarea_field( (string) wp_unslash( $raw_settings['prompt_post_like'] ) ) : (string) $defaults['prompt_post_like'],
			'prompt_term_like' => array_key_exists( 'prompt_term_like', $raw_settings ) ? sanitize_textarea_field( (string) wp_unslash( $raw_settings['prompt_term_like'] ) ) : (string) $defaults['prompt_term_like'],
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return array(
			'base_url'         => 'https://api.openai.com/v1',
			'api_key'          => '',
			'model'            => '',
			'timeout'          => 30,
			'dry_run'          => 1,
			'prompt_post_like' => 'You write clear WordPress excerpts. Return one complete sentence in plain text. Keep it moderate in length, do not copy the opening paragraph verbatim, and avoid truncated fragments or title-like stubs.',
			'prompt_term_like' => 'You write clear taxonomy descriptions for WordPress. Return one complete sentence in plain text. Keep it moderate in length, do not copy existing text verbatim, and avoid truncated fragments.',
		);
	}

	/**
	 * @param mixed $timeout Raw timeout input.
	 */
	private function normalize_timeout( $timeout ): int {
		$value = absint( $timeout );

		if ( $value < 5 ) {
			$value = 5;
		}

		if ( $value > 120 ) {
			$value = 120;
		}

		return $value;
	}

	private function ends_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		return substr( $haystack, - strlen( $needle ) ) === $needle;
	}
}
