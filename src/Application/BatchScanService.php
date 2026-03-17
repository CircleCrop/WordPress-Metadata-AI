<?php

namespace WMAIGEN\Application;

use WMAIGEN\Domain\GenerationTarget;
use WMAIGEN\Infrastructure\LogRepository;
use WMAIGEN\Infrastructure\TargetFactory;
use WMAIGEN\Support\ObjectTypeRegistry;

/**
 * Find batch candidates whose excerpt/description is currently empty.
 */
final class BatchScanService {
	/**
	 * @var ObjectTypeRegistry
	 */
	private $object_type_registry;

	/**
	 * @var TargetFactory
	 */
	private $target_factory;

	/**
	 * @var LogRepository
	 */
	private $log_repository;

	public function __construct( ObjectTypeRegistry $object_type_registry, TargetFactory $target_factory, LogRepository $log_repository ) {
		$this->object_type_registry = $object_type_registry;
		$this->target_factory       = $target_factory;
		$this->log_repository       = $log_repository;
	}

	/**
	 * @return array<int, GenerationTarget>
	 */
	public function scan( string $filter, int $limit ): array {
		$limit      = max( 1, min( 100, $limit ) );
		$candidates = array();

		if ( 'all' === $filter || $this->object_type_registry->is_supported_post_type( $filter ) ) {
			$post_types = 'all' === $filter ? array_keys( $this->object_type_registry->get_supported_post_types() ) : array( $filter );
			$candidates = array_merge( $candidates, $this->scan_posts( $post_types, $limit ) );
		}

		if ( count( $candidates ) < $limit && ( 'all' === $filter || $this->object_type_registry->is_supported_taxonomy( $filter ) ) ) {
			$taxonomies = 'all' === $filter ? array_keys( $this->object_type_registry->get_supported_taxonomies() ) : array( $filter );
			$remaining  = $limit - count( $candidates );
			$candidates = array_merge( $candidates, $this->scan_terms( $taxonomies, $remaining ) );
		}

		$this->log_repository->log(
			'info',
			'batch_scan',
			'success',
			sprintf(
				/* translators: 1: candidate count, 2: filter name */
				__( 'Batch scan found %1$d candidate(s) for filter %2$s.', 'wordpress-metadata-aigen' ),
				count( $candidates ),
				$filter
			),
			array( 'object_kind' => 'batch', 'object_subtype' => $filter )
		);

		return array_slice( $candidates, 0, $limit );
	}

	/**
	 * @param array<int, string> $post_types Supported post types.
	 * @return array<int, GenerationTarget>
	 */
	private function scan_posts( array $post_types, int $limit ): array {
		$candidates = array();
		$page       = 1;
		$page_size  = min( 50, max( 10, $limit ) );

		while ( count( $candidates ) < $limit ) {
			$query = new \WP_Query(
				array(
					'post_type'           => $post_types,
					'post_status'         => array( 'publish', 'draft', 'pending', 'future', 'private' ),
					'posts_per_page'      => $page_size,
					'paged'               => $page,
					'orderby'             => 'ID',
					'order'               => 'ASC',
					'ignore_sticky_posts' => true,
					'suppress_filters'    => true,
				)
			);

			if ( empty( $query->posts ) ) {
				wp_reset_postdata();
				break;
			}

			foreach ( $query->posts as $post ) {
				if ( ! $post instanceof \WP_Post ) {
					continue;
				}

				if ( '' !== trim( (string) $post->post_excerpt ) ) {
					continue;
				}

				$target = $this->target_factory->from_post( $post );

				if ( is_wp_error( $target ) ) {
					continue;
				}

				$candidates[] = $target;

				if ( count( $candidates ) >= $limit ) {
					break 2;
				}
			}

			wp_reset_postdata();

			if ( $page >= (int) $query->max_num_pages ) {
				break;
			}

			++$page;
		}

		return $candidates;
	}

	/**
	 * @param array<int, string> $taxonomies Supported taxonomies.
	 * @return array<int, GenerationTarget>
	 */
	private function scan_terms( array $taxonomies, int $limit ): array {
		$candidates = array();

		foreach ( $taxonomies as $taxonomy ) {
			$offset    = 0;
			$page_size = min( 50, max( 10, $limit ) );

			while ( count( $candidates ) < $limit ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'number'     => $page_size,
						'offset'     => $offset,
						'orderby'    => 'term_id',
						'order'      => 'ASC',
					)
				);

				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					break;
				}

				foreach ( $terms as $term ) {
					if ( ! $term instanceof \WP_Term ) {
						continue;
					}

					if ( '' !== trim( (string) $term->description ) ) {
						continue;
					}

					$target = $this->target_factory->from_term( $term );

					if ( is_wp_error( $target ) ) {
						continue;
					}

					$candidates[] = $target;

					if ( count( $candidates ) >= $limit ) {
						break 2;
					}
				}

				if ( count( $terms ) < $page_size ) {
					break;
				}

				$offset += $page_size;
			}
		}

		return $candidates;
	}
}
