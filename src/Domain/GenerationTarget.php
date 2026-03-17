<?php

namespace WMAIGEN\Domain;

/**
 * Unified description target for posts and taxonomy terms.
 */
final class GenerationTarget {
	/**
	 * Target kind: post, term, system.
	 *
	 * @var string
	 */
	private $kind;

	/**
	 * Target subtype: post type or taxonomy slug.
	 *
	 * @var string
	 */
	private $subtype;

	/**
	 * Target numeric ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Human-readable label.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Content used to generate the description.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * Existing description/excerpt value.
	 *
	 * @var string
	 */
	private $current_description;

	/**
	 * Admin edit link, when available.
	 *
	 * @var string
	 */
	private $edit_link;

	/**
	 * @param string $kind                Target kind.
	 * @param string $subtype             Target subtype.
	 * @param int    $id                  Target ID.
	 * @param string $title               Target title.
	 * @param string $content             Target content.
	 * @param string $current_description Existing excerpt/description.
	 * @param string $edit_link           Admin edit URL.
	 */
	public function __construct(
		string $kind,
		string $subtype,
		int $id,
		string $title,
		string $content,
		string $current_description,
		string $edit_link = ''
	) {
		$this->kind                = $kind;
		$this->subtype             = $subtype;
		$this->id                  = $id;
		$this->title               = $title;
		$this->content             = $content;
		$this->current_description = $current_description;
		$this->edit_link           = $edit_link;
	}

	public function get_kind(): string {
		return $this->kind;
	}

	public function get_subtype(): string {
		return $this->subtype;
	}

	public function get_id(): int {
		return $this->id;
	}

	public function get_title(): string {
		return $this->title;
	}

	public function get_content(): string {
		return $this->content;
	}

	public function get_current_description(): string {
		return $this->current_description;
	}

	public function get_edit_link(): string {
		return $this->edit_link;
	}

	public function has_existing_description(): bool {
		return '' !== trim( $this->current_description );
	}

	public function is_post(): bool {
		return 'post' === $this->kind;
	}

	public function is_term(): bool {
		return 'term' === $this->kind;
	}

	public function get_prompt_key(): string {
		return $this->is_term() ? 'prompt_term_like' : 'prompt_post_like';
	}

	/**
	 * Minimal candidate payload that can survive a redirect.
	 *
	 * @return array<string, mixed>
	 */
	public function to_state_array(): array {
		return array(
			'kind'                => $this->kind,
			'subtype'             => $this->subtype,
			'id'                  => $this->id,
			'title'               => $this->title,
			'current_description' => $this->current_description,
			'edit_link'           => $this->edit_link,
		);
	}

	/**
	 * Build a log context array.
	 *
	 * @param array<string, mixed> $extra Extra fields to merge.
	 * @return array<string, mixed>
	 */
	public function to_log_context( array $extra = array() ): array {
		return array_merge(
			array(
				'object_kind'    => $this->kind,
				'object_subtype' => $this->subtype,
				'object_id'      => $this->id,
				'object_name'    => $this->title,
			),
			$extra
		);
	}
}
