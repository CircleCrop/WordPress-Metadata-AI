<?php

namespace WMAIGEN\Application;

use WMAIGEN\Domain\GenerationContext;
use WMAIGEN\Domain\GenerationResult;
use WMAIGEN\Domain\GenerationTarget;
use WMAIGEN\Infrastructure\DescriptionWriter;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\OpenAIClient;
use WMAIGEN\Infrastructure\SettingsRepository;
use WMAIGEN\Support\ErrorMessageFormatter;
use WMAIGEN\Support\TextSanitizer;

/**
 * Core generation orchestration shared by single and batch flows.
 */
final class GenerateDescriptionService {
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

	/**
	 * @var DescriptionWriter
	 */
	private $description_writer;

	public function __construct(
		SettingsRepository $settings_repository,
		LogRepository $log_repository,
		OpenAIClient $openai_client,
		DescriptionWriter $description_writer
	) {
		$this->settings_repository = $settings_repository;
		$this->log_repository      = $log_repository;
		$this->openai_client       = $openai_client;
		$this->description_writer  = $description_writer;
	}

	public function generate( GenerationTarget $target, bool $overwrite = false ): GenerationResult {
		$settings = $this->settings_repository->get();
		$dry_run  = ! empty( $settings['dry_run'] );

		$this->log_repository->log(
			'info',
			'configuration_read',
			'ok',
			sprintf(
				/* translators: %s: AI model name */
				__( 'Loaded API configuration for model %s.', 'wordpress-metadata-aigen' ),
				(string) $settings['model']
			),
			$target->to_log_context()
		);

		if ( ! $this->settings_repository->is_configured( $settings ) ) {
			$message = __( 'API settings are incomplete. Please configure Base URL, API Key, and Model first.', 'wordpress-metadata-aigen' );
			$this->log_repository->log( 'error', 'configuration_invalid', 'failed', $message, $target->to_log_context() );
			return GenerationResult::failure( $message );
		}

		if ( $target->has_existing_description() && ! $overwrite ) {
			$message = __( 'Skipped because a description already exists. Enable overwrite to replace it manually.', 'wordpress-metadata-aigen' );
			$this->log_repository->log( 'warning', 'write_skipped', 'skipped', $message, $target->to_log_context() );
			return GenerationResult::skipped( $message );
		}

		$context = $this->build_context( $target, $settings, $dry_run );

		$this->log_repository->log(
			'info',
			'request_started',
			'ok',
			__( 'Sending generation request to the AI API.', 'wordpress-metadata-aigen' ),
			$target->to_log_context( array( 'dry_run' => $dry_run ? 1 : 0 ) )
		);

		$generated_text = $this->openai_client->request_description( $context, $settings );

		if ( is_wp_error( $generated_text ) ) {
			$message = ErrorMessageFormatter::from_wp_error( $generated_text, __( 'The AI request failed.', 'wordpress-metadata-aigen' ) );
			$this->log_repository->log( 'error', 'request_failed', 'failed', $message, $target->to_log_context() );
			return GenerationResult::failure( $message );
		}

		$generated_text = TextSanitizer::normalize_generated_text( $generated_text );

		if ( '' === $generated_text ) {
			$message = __( 'The AI returned an empty description after cleanup.', 'wordpress-metadata-aigen' );
			$this->log_repository->log( 'error', 'result_invalid', 'failed', $message, $target->to_log_context() );
			return GenerationResult::failure( $message );
		}

		$this->log_repository->log(
			'info',
			'result_generated',
			'success',
			sprintf(
				/* translators: %s: generated description preview */
				__( 'Generated description: %s', 'wordpress-metadata-aigen' ),
				TextSanitizer::truncate( $generated_text, 160 )
			),
			$target->to_log_context()
		);

		if ( $dry_run ) {
			$message = __( 'Dry Run is enabled. The description was generated but not written to WordPress.', 'wordpress-metadata-aigen' );
			$this->log_repository->log( 'info', 'write_skipped', 'dry-run', $message, $target->to_log_context() );
			return GenerationResult::dry_run( $generated_text, $message );
		}

		$saved = $this->description_writer->save( $target, $generated_text );

		if ( is_wp_error( $saved ) ) {
			$message = ErrorMessageFormatter::from_wp_error( $saved, __( 'The generated description could not be saved.', 'wordpress-metadata-aigen' ) );
			$this->log_repository->log( 'error', 'write_failed', 'failed', $message, $target->to_log_context() );
			return GenerationResult::failure( $message, $generated_text );
		}

		$message = __( 'The generated description was saved successfully.', 'wordpress-metadata-aigen' );
		$this->log_repository->log( 'info', 'write_succeeded', 'success', $message, $target->to_log_context() );
		return GenerationResult::saved( $generated_text, $message );
	}

	/**
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	private function build_context( GenerationTarget $target, array $settings, bool $dry_run ): GenerationContext {
		$system_prompt = isset( $settings[ $target->get_prompt_key() ] ) ? (string) $settings[ $target->get_prompt_key() ] : '';
		$title         = TextSanitizer::normalize_for_prompt( $target->get_title(), 200 );
		$content       = TextSanitizer::normalize_for_prompt( $target->get_content(), 4000 );
		$current       = TextSanitizer::normalize_for_prompt( $target->get_current_description(), 500 );

		$user_prompt = implode(
			"\n\n",
			array(
				sprintf( 'Target kind: %s', $target->get_kind() ),
				sprintf( 'Subtype: %s', $target->get_subtype() ),
				sprintf( 'Title: %s', '' !== $title ? $title : '[empty]' ),
				sprintf( 'Current description: %s', '' !== $current ? $current : '[empty]' ),
				sprintf( 'Source content: %s', '' !== $content ? $content : '[empty]' ),
				'Requirements: Return plain text only. Use one complete sentence. Keep it moderate in length. Do not copy the first paragraph verbatim. Do not output a title fragment, bullets, or HTML.',
				$dry_run ? 'Mode: Dry Run preview only.' : 'Mode: Save-ready output.',
			)
		);

		return new GenerationContext( $target, $system_prompt, $user_prompt, $dry_run );
	}
}
