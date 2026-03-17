<?php

namespace WMAIGEN\Domain;

/**
 * Result for one generation attempt.
 */
final class GenerationResult {
	/**
	 * Outcome flags and payload.
	 *
	 * @var bool
	 */
	private $successful;
	private $saved;
	private $dry_run;
	private $skipped;

	/**
	 * Generated description text.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * User-facing message.
	 *
	 * @var string
	 */
	private $message;

	private function __construct( bool $successful, bool $saved, bool $dry_run, bool $skipped, string $description, string $message ) {
		$this->successful  = $successful;
		$this->saved       = $saved;
		$this->dry_run     = $dry_run;
		$this->skipped     = $skipped;
		$this->description = $description;
		$this->message     = $message;
	}

	public static function saved( string $description, string $message ): self {
		return new self( true, true, false, false, $description, $message );
	}

	public static function dry_run( string $description, string $message ): self {
		return new self( true, false, true, false, $description, $message );
	}

	public static function skipped( string $message ): self {
		return new self( false, false, false, true, '', $message );
	}

	public static function failure( string $message, string $description = '' ): self {
		return new self( false, false, false, false, $description, $message );
	}

	public function is_successful(): bool {
		return $this->successful;
	}

	public function is_saved(): bool {
		return $this->saved;
	}

	public function is_dry_run(): bool {
		return $this->dry_run;
	}

	public function is_skipped(): bool {
		return $this->skipped;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function get_message(): string {
		return $this->message;
	}
}
