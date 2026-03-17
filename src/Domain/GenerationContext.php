<?php

namespace WMAIGEN\Domain;

/**
 * Immutable prompt bundle sent to the API client.
 */
final class GenerationContext {
	/**
	 * Target being processed.
	 *
	 * @var GenerationTarget
	 */
	private $target;

	/**
	 * System prompt.
	 *
	 * @var string
	 */
	private $system_prompt;

	/**
	 * User prompt.
	 *
	 * @var string
	 */
	private $user_prompt;

	/**
	 * Whether the request is a dry run.
	 *
	 * @var bool
	 */
	private $dry_run;

	public function __construct( GenerationTarget $target, string $system_prompt, string $user_prompt, bool $dry_run ) {
		$this->target        = $target;
		$this->system_prompt = $system_prompt;
		$this->user_prompt   = $user_prompt;
		$this->dry_run       = $dry_run;
	}

	public function get_target(): GenerationTarget {
		return $this->target;
	}

	public function get_system_prompt(): string {
		return $this->system_prompt;
	}

	public function get_user_prompt(): string {
		return $this->user_prompt;
	}

	public function is_dry_run(): bool {
		return $this->dry_run;
	}
}
