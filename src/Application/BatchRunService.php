<?php

namespace WMAIGEN\Application;

use WMAIGEN\Domain\GenerationResult;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\TargetFactory;
use WMAIGEN\Support\ErrorMessageFormatter;

/**
 * Execute a previously scanned batch of targets.
 */
final class BatchRunService {
	/**
	 * @var TargetFactory
	 */
	private $target_factory;

	/**
	 * @var GenerateDescriptionService
	 */
	private $generate_service;

	/**
	 * @var LogRepository
	 */
	private $log_repository;

	public function __construct( TargetFactory $target_factory, GenerateDescriptionService $generate_service, LogRepository $log_repository ) {
		$this->target_factory   = $target_factory;
		$this->generate_service = $generate_service;
		$this->log_repository   = $log_repository;
	}

	/**
	 * @param array<int, array<string, mixed>> $candidate_refs Stored candidate references.
	 * @return array<string, int>
	 */
	public function run( array $candidate_refs ): array {
		$summary = array(
			'processed' => 0,
			'saved'     => 0,
			'dry_run'   => 0,
			'skipped'   => 0,
			'failed'    => 0,
		);

		foreach ( $candidate_refs as $candidate_ref ) {
			$target = $this->restore_target( $candidate_ref );

			if ( is_wp_error( $target ) ) {
				++$summary['failed'];
				$this->log_repository->log(
					'error',
					'batch_restore_failed',
					'failed',
					ErrorMessageFormatter::from_wp_error( $target, __( 'Could not reload a batch target.', 'wordpress-metadata-aigen' ) ),
					array(
						'object_kind'    => isset( $candidate_ref['kind'] ) ? (string) $candidate_ref['kind'] : 'unknown',
						'object_subtype' => isset( $candidate_ref['subtype'] ) ? (string) $candidate_ref['subtype'] : 'unknown',
						'object_id'      => isset( $candidate_ref['id'] ) ? (int) $candidate_ref['id'] : 0,
						'object_name'    => isset( $candidate_ref['title'] ) ? (string) $candidate_ref['title'] : '',
					)
				);
				continue;
			}

			$result = $this->generate_service->generate( $target, false );
			++$summary['processed'];
			$this->update_summary( $summary, $result );
		}

		$this->log_repository->log(
			'info',
			'batch_run',
			'success',
			sprintf(
				/* translators: 1: processed count, 2: saved count, 3: dry run count, 4: skipped count, 5: failed count */
				__( 'Batch run finished. Processed: %1$d, saved: %2$d, dry run: %3$d, skipped: %4$d, failed: %5$d.', 'wordpress-metadata-aigen' ),
				$summary['processed'],
				$summary['saved'],
				$summary['dry_run'],
				$summary['skipped'],
				$summary['failed']
			),
			array( 'object_kind' => 'batch', 'object_subtype' => 'run' )
		);

		return $summary;
	}

	/**
	 * @param array<string, int> $summary Running counters.
	 */
	private function update_summary( array &$summary, GenerationResult $result ): void {
		if ( $result->is_saved() ) {
			++$summary['saved'];
			return;
		}

		if ( $result->is_dry_run() ) {
			++$summary['dry_run'];
			return;
		}

		if ( $result->is_skipped() ) {
			++$summary['skipped'];
			return;
		}

		++$summary['failed'];
	}

	/**
	 * @param array<string, mixed> $candidate_ref Stored candidate reference.
	 * @return \WMAIGEN\Domain\GenerationTarget|\WP_Error
	 */
	private function restore_target( array $candidate_ref ) {
		$kind    = isset( $candidate_ref['kind'] ) ? (string) $candidate_ref['kind'] : '';
		$subtype = isset( $candidate_ref['subtype'] ) ? (string) $candidate_ref['subtype'] : '';
		$id      = isset( $candidate_ref['id'] ) ? (int) $candidate_ref['id'] : 0;

		if ( 'post' === $kind ) {
			return $this->target_factory->from_post_id( $id );
		}

		if ( 'term' === $kind ) {
			return $this->target_factory->from_term_id( $id, $subtype );
		}

		return new \WP_Error( 'wmaigen_invalid_batch_target', __( 'Invalid batch target payload.', 'wordpress-metadata-aigen' ) );
	}
}
