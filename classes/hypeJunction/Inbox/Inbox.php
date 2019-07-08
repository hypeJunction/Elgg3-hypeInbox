<?php

namespace hypeJunction\Inbox;

use Elgg\Database\Clauses\GroupByClause;
use Elgg\Database\Clauses\OrderByClause;
use Elgg\Database\Clauses\SelectClause;
use Elgg\Database\QueryBuilder;
use ElggUser;
use InvalidArgumentException;

class Inbox {

	/**
	 * User that "owns" the inbox
	 * @var ElggUser
	 */
	protected $owner;

	/**
	 * Message type
	 * @var string
	 */
	protected $msgType;

	/**
	 * Read status
	 * @var boolean
	 */
	protected $readYet;

	/**
	 * Flag to display messages as threads
	 * @var bool
	 */
	protected $threaded;

	/**
	 * Flag to only display sent or received messages
	 * @var bool
	 */
	protected $direction;

	/**
	 * Database prefix
	 * @var string
	 */
	private $dbprefix;

	/**
	 * Cached metastring ids
	 * @var array
	 */
	private static $metamap;

	const DIRECTION_SENT = 'sent';
	const DIRECTION_RECEIVED = 'received';
	const DIRECTION_ALL = 'all';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->dbprefix = elgg_get_config('dbprefix');
	}

	/**
	 * Get a count of unread messages
	 *
	 * @param ElggUser $user    Recipient
	 * @param string   $msgType Message type
	 * @param array    $options Additional options to pass to the getter
	 *
	 * @return int
	 */
	public static function countUnread(ElggUser $user, $msgType = '', array $options = []) {
		$instance = new Inbox();
		$instance->setOwner($user)->setMessageType($msgType)->setReadStatus(false);

		return $instance->getCount($options);
	}

	/**
	 * Set inbox owner
	 *
	 * @param ElggUser $user Owning user
	 *
	 * @return Inbox
	 * @throws InvalidArgumentException
	 */
	public function setOwner(ElggUser $user) {
		if (!$user instanceof ElggUser) {
			throw new InvalidArgumentException(get_class() . '::setOwner() expects an instanceof ElggUser');
		}

		$this->owner = $user;

		return $this;
	}

	/**
	 * Get inbox owner
	 * @return \ElggUser
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * Set message type
	 *
	 * @param string $msgType Message type
	 *
	 * @return Inbox
	 */
	public function setMessageType($msgType = '') {
		$this->msgType = $msgType;

		return $this;
	}

	/**
	 * Get message type
	 * @return string
	 */
	public function getMessageType() {
		return $this->msgType;
	}

	/**
	 * Set read / not read status
	 *
	 * @param boolean $readYet True for read, false for not read
	 *
	 * @return Inbox
	 */
	public function setReadStatus($readYet = null) {
		if (!is_null($readYet)) {
			$this->readYet = $readYet ? '1' : '0';
		}

		return $this;
	}

	/**
	 * Get read status
	 * @return bool
	 */
	public function getReadStatus() {
		return $this->readYet;
	}

	/**
	 * Sets message display to threaded
	 *
	 * @param bool $threaded Flag
	 *
	 * @return Inbox;
	 */
	public function displayThreaded($threaded = true) {
		$this->threaded = (bool) $threaded;

		return $this;
	}

	/**
	 * Check if display is threaded
	 * @return bool
	 */
	public function isDisplayThreaded() {
		return (bool) $this->threaded;
	}

	/**
	 * Set types of messages to display
	 *
	 * @param string $direction 'sent', 'received' or 'all'
	 *
	 * @return Inbox
	 */
	public function setDirection($direction = '') {
		if (in_array($direction, [
			self::DIRECTION_ALL,
			self::DIRECTION_SENT,
			self::DIRECTION_RECEIVED
		])) {
			$this->direction = $direction;
		}

		return $this;
	}

	/**
	 * Returns set message direction or 'all'
	 * @return string
	 */
	public function getDirection() {
		return ($this->direction) ? : self::DIRECTION_ALL;
	}

	/**
	 * Get messages
	 *
	 * @param array $options Additional options to pass to the getter
	 *
	 * @return Message[]|false|int
	 */
	public function getMessages(array $options = []) {
		$options = $this->getFilterOptions($options);

		return elgg_get_entities($options);
	}

	/**
	 * Get count of messages
	 *
	 * @param array $options Additional options to pass to the getter
	 *
	 * @return int
	 */
	public function getCount(array $options = []) {
		if ($this->threaded) {
			$options = $this->getFilterOptions($options);

			unset($options['group_by']);
			$options['selects'][] = new SelectClause('COUNT(DISTINCT msgHash.value) AS total');

			$options['limit'] = 1;
			$options['callback'] = [$this, 'getCountCallback'];

			$messages = elgg_get_entities($options);

			return $messages[0]->total;
		} else {
			$options['count'] = true;

			return $this->getMessages($options);
		}
	}

	public static function getCountCallback($row) {
		return $row;
	}

	/**
	 * Filter getter options
	 *
	 * @param array $options Default options
	 *
	 * @return array
	 */
	public function getFilterOptions(array $options = []) {
		$options['types'] = 'object';
		$options['subtypes'] = Message::SUBTYPE;
		$options['owner_guids'] = $this->owner->guid;

		$options['wheres'][] = function (QueryBuilder $qb) {
			$qb->joinMetadataTable('e', 'guid', 'msgHash', 'inner', 'msgHash');
		};

		$options['metadata_name_value_pairs'][] = [
			'name' => 'msgHash',
			'operand' => 'IS NOT NULL',
		];

		$direction = $this->getDirection();

		if ($direction == self::DIRECTION_SENT) {
			$options['metadata_name_value_pairs'][] = [
				'name' => 'fromId',
				'value' => $this->owner->guid,
			];
		} else if ($direction == self::DIRECTION_RECEIVED) {
			$options['metadata_name_value_pairs'][] = [
				'name' => 'toId',
				'value' => $this->owner->guid,
			];
		} else if ($this->threaded) {
			$options['selects'][] = new SelectClause('MAX(e.guid) as lastMsg');
			$options['group_by'] = [new GroupByClause('msgHash.value')];
			$options['order_by'] = [new OrderByClause('MAX(e.guid)', 'DESC')];
		}

		if ($this->msgType) {
			$options['metadata_name_value_pairs'][] = [
				'name' => 'msgType',
				'value' => $this->msgType,
			];
		}

		if (!is_null($this->readYet)) {
			$options['metadata_name_value_pairs'][] = [
				'name' => 'readYet',
				'value' => $this->readYet,
			];
		}

		return $options;
	}

}
