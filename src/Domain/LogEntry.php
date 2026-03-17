<?php

namespace WMAIGEN\Domain;

/**
 * Persisted log record for admin debugging.
 */
final class LogEntry {
	/**
	 * Timestamp in Unix seconds.
	 *
	 * @var int
	 */
	private $timestamp;

	/**
	 * Severity level.
	 *
	 * @var string
	 */
	private $level;

	/**
	 * Action code.
	 *
	 * @var string
	 */
	private $action;

	/**
	 * Result code.
	 *
	 * @var string
	 */
	private $result;

	/**
	 * Human-readable message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Target metadata.
	 *
	 * @var string
	 */
	private $object_kind;
	private $object_subtype;
	private $object_id;
	private $object_name;

	public function __construct(
		int $timestamp,
		string $level,
		string $action,
		string $result,
		string $message,
		string $object_kind = '',
		string $object_subtype = '',
		int $object_id = 0,
		string $object_name = ''
	) {
		$this->timestamp      = $timestamp;
		$this->level          = $level;
		$this->action         = $action;
		$this->result         = $result;
		$this->message        = $message;
		$this->object_kind    = $object_kind;
		$this->object_subtype = $object_subtype;
		$this->object_id      = $object_id;
		$this->object_name    = $object_name;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'timestamp'      => $this->timestamp,
			'level'          => $this->level,
			'action'         => $this->action,
			'result'         => $this->result,
			'message'        => $this->message,
			'object_kind'    => $this->object_kind,
			'object_subtype' => $this->object_subtype,
			'object_id'      => $this->object_id,
			'object_name'    => $this->object_name,
		);
	}
}
