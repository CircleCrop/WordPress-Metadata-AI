<?php

namespace WMAIGEN\Application;

use WMAIGEN\Domain\GenerationContext;
use WMAIGEN\Domain\GenerationTarget;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\OpenAIClient;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Support\ErrorMessageFormatter;
use WMAIGEN\Support\TextSanitizer;

/**
 * Lightweight API connectivity test for the settings page.
 */
final class TestConnectionService {
	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var LogRepository
	 */
	private $log_repository;

	/**
	 * @var OpenAIClient
	 */
	private $openai_client;

	public function __construct( SettingsRepository $settings_repository, LogRepository $log_repository, OpenAIClient $openai_client ) {
		$this->settings_repository = $settings_repository;
		$this->log_repository      = $log_repository;
		$this->openai_client       = $openai_client;
	}

	/**
	 * @return array<string, string|bool>
	 */
	public function run(): array {
		$settings   = $this->settings_repository->get();
		$think_mode = $this->settings_repository->get_think_mode( $settings );

		$this->log_repository->log(
			'info',
			'configuration_read',
			'ok',
			sprintf(
				/* translators: %s: think mode */
				__( 'Loaded API configuration for connection testing with think mode %s.', 'wordpress-metadata-aigen' ),
				$think_mode
			),
			array( 'object_kind' => 'system', 'object_subtype' => 'settings', 'object_name' => 'API connection test' )
		);

		if ( ! $this->settings_repository->is_configured( $settings ) ) {
			$message = __( 'Cannot test the API because Base URL, API Key, or Model is missing.', 'wordpress-metadata-aigen' );
			$this->log_repository->log( 'error', 'configuration_invalid', 'failed', $message, array( 'object_kind' => 'system', 'object_subtype' => 'settings' ) );
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$target  = new GenerationTarget( 'system', 'settings', 0, 'Connection Test', 'Return the single word Connected.', '', '' );
		$context = new GenerationContext(
			$target,
			'You are testing an API connection. Return a very short plain text response with no reasoning or analysis.',
			'Reply with the single word Connected. Do not include reasoning, analysis, or extra text.',
			false
		);

		$this->log_repository->log( 'info', 'request_started', 'ok', __( 'Sending API connection test request.', 'wordpress-metadata-aigen' ), $target->to_log_context() );

		$response = $this->openai_client->request_description( $context, $settings );

		if ( is_wp_error( $response ) ) {
			$message = ErrorMessageFormatter::from_wp_error( $response, __( 'API test failed.', 'wordpress-metadata-aigen' ) );
			$this->log_repository->log( 'error', 'request_failed', 'failed', $message, $target->to_log_context() );
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$response = TextSanitizer::normalize_generated_text( $response );
		$this->log_repository->log(
			'info',
			'result_generated',
			'success',
			sprintf(
				/* translators: %s: response preview */
				__( 'API test response: %s', 'wordpress-metadata-aigen' ),
				TextSanitizer::truncate( $response, 120 )
			),
			$target->to_log_context()
		);

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: response preview */
				__( 'API test succeeded. Sample response: %s', 'wordpress-metadata-aigen' ),
				TextSanitizer::truncate( $response, 120 )
			),
		);
	}
}
