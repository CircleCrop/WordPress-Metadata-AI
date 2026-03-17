<?php

namespace WMAIGEN\Infrastructure;

use WMAIGEN\Domain\GenerationContext;

/**
 * Minimal OpenAI-compatible chat completions client.
 */
final class OpenAIClient {
	/**
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return string|\WP_Error
	 */
	public function request_description( GenerationContext $context, array $settings ) {
		$endpoint = $this->build_endpoint( (string) $settings['base_url'] );
		$payload  = array(
			'model'    => (string) $settings['model'],
			'messages' => array(
				array(
					'role'    => 'system',
					'content' => $context->get_system_prompt(),
				),
				array(
					'role'    => 'user',
					'content' => $context->get_user_prompt(),
				),
			),
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => isset( $settings['timeout'] ) ? (int) $settings['timeout'] : 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . (string) $settings['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'wmaigen_api_request_failed', $response->get_error_message() );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = $this->extract_error_message( $body );
			return new \WP_Error(
				'wmaigen_api_http_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: API error message */
					__( 'API request failed with status %1$d. %2$s', 'wordpress-metadata-aigen' ),
					$status_code,
					$error_message
				)
			);
		}

		$decoded = json_decode( $body, true );

		if ( ! is_array( $decoded ) ) {
			return new \WP_Error( 'wmaigen_api_invalid_json', __( 'API returned invalid JSON.', 'wordpress-metadata-aigen' ) );
		}

		$content = $this->extract_message_content( $decoded );

		if ( '' === trim( $content ) ) {
			return new \WP_Error( 'wmaigen_api_empty_content', __( 'API returned an empty description.', 'wordpress-metadata-aigen' ) );
		}

		return $content;
	}

	private function build_endpoint( string $base_url ): string {
		$base_url = rtrim( $base_url, '/' );

		if ( $this->ends_with( $base_url, '/chat/completions' ) ) {
			return $base_url;
		}

		return $base_url . '/chat/completions';
	}

	/**
	 * @param array<string, mixed> $decoded Parsed API response.
	 */
	private function extract_message_content( array $decoded ): string {
		if ( isset( $decoded['choices'][0]['message']['content'] ) && is_string( $decoded['choices'][0]['message']['content'] ) ) {
			return $decoded['choices'][0]['message']['content'];
		}

		if ( isset( $decoded['choices'][0]['message']['content'] ) && is_array( $decoded['choices'][0]['message']['content'] ) ) {
			return $this->flatten_content_blocks( $decoded['choices'][0]['message']['content'] );
		}

		if ( isset( $decoded['choices'][0]['text'] ) && is_string( $decoded['choices'][0]['text'] ) ) {
			return $decoded['choices'][0]['text'];
		}

		return '';
	}

	/**
	 * @param array<int, mixed> $content_blocks Message content blocks.
	 */
	private function flatten_content_blocks( array $content_blocks ): string {
		$parts = array();

		foreach ( $content_blocks as $block ) {
			if ( is_array( $block ) && isset( $block['text'] ) && is_string( $block['text'] ) ) {
				$parts[] = $block['text'];
			}
		}

		return implode( "\n", $parts );
	}

	private function extract_error_message( string $body ): string {
		$decoded = json_decode( $body, true );

		if ( is_array( $decoded ) && isset( $decoded['error']['message'] ) && is_string( $decoded['error']['message'] ) ) {
			return $decoded['error']['message'];
		}

		$body = trim( wp_strip_all_tags( $body ) );

		if ( '' === $body ) {
			return __( 'No API error message was returned.', 'wordpress-metadata-aigen' );
		}

		return $body;
	}

	private function ends_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		return substr( $haystack, - strlen( $needle ) ) === $needle;
	}
}
