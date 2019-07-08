<?php

namespace hypeJunction\Inbox;

use Elgg\Database\Clauses\OrderByClause;
use Elgg\Database\QueryBuilder;
use ElggBatch;
use ElggEntity;

class Thread {

	protected $message;
	private $dbprefix;

	const LIMIT = 10;

	/**
	 * Construct a magic thread
	 *
	 * @param Message $message Message entity
	 *
	 * @throws \IOException
	 */
	public function __construct(Message $message) {
		$this->message = $message;
		$this->dbprefix = elgg_get_config('dbprefix');
	}

	/**
	 * Get options for {@link elgg_get_entities()}
	 *
	 * @param array $options Default options array
	 *
	 * @return array
	 */
	public function getFilterOptions(array $options = []) {
		$options['types'] = Message::TYPE;
		$options['subtypes'] = Message::SUBTYPE;
		$options['owner_guids'] = $this->message->owner_guid;

		if (!isset($options['order_by'])) {
			$options['order_by'] = [new OrderByClause('e.guid', 'ASC')];
		}

		$hash = $this->message->getHash();

		$options['metadata_name_value_pairs'][] = [
			'name' => 'msgHash',
			'value' => $hash,
		];

		return $options;
	}

	/**
	 * Calculate a page offset to the given message
	 *
	 * @param int $limit Items per page
	 *
	 * @return int
	 */
	public function getOffset($limit = self::LIMIT) {
		if ($limit === 0) {
			return 0;
		}
		$before = $this->getMessagesBefore(['count' => true, 'offset' => 0]);

		return floor($before / $limit) * $limit;
	}

	/**
	 * Get messages in a thread
	 *
	 * @param array $options Default options array
	 *
	 * @return Message[]|false|int
	 */
	public function getMessages(array $options = []) {
		$options = $this->getFilterOptions($options);

		return elgg_get_entities($options);
	}

	/**
	 * Get unread messages in a thread
	 *
	 * @param array $options Default options array
	 *
	 * @return Message[]|false|int
	 */
	public function getUnreadMessages(array $options = []) {
		$options['metadata_name_value_pairs'][] = [
			'name' => 'readYet',
			'value' => 0,
		];

		$options['metadata_name_value_pairs'][] = [
			'name' => 'fromId',
			'value' => $this->message->owner_guid,
		];

		return $this->getMessages($options);
	}

	/**
	 * Get count of messages in a thread
	 *
	 * @param array $options Default options array
	 *
	 * @return int
	 */
	public function getCount(array $options = []) {
		$options['count'] = true;

		return $this->getMessages($options);
	}

	/**
	 * Get count of unread messages in a thread
	 *
	 * @param array $options Default options array
	 *
	 * @return int
	 */
	public function getUnreadCount(array $options = []) {
		$options['count'] = true;

		return $this->getUnreadMessages($options);
	}

	/**
	 * Check if thread contains unread messages
	 * @return boolean
	 */
	public function isRead() {
		return (!$this->getUnreadCount());
	}

	/**
	 * Mark all messages in a thread as read
	 * @return void
	 */
	public function markRead() {
		$messages = $this->getAll();
		foreach ($messages as $message) {
			$message->readYet = true;
		}
	}

	/**
	 * Mark all messages in a thread as unread
	 * @return void
	 */
	public function markUnread() {
		$messages = $this->getAll();
		foreach ($messages as $message) {
			$message->readYet = false;
		}
	}

	/**
	 * Delete all messages in a thread
	 *
	 * @param bool $recursive Delete recursively
	 *
	 * @return bool
	 */
	public function delete($recursive = true) {
		$success = 0;
		$count = $this->getCount();
		$messages = $this->getAll();

		$messages->setIncrementOffset(false);

		foreach ($messages as $message) {
			if ($message->delete($recursive)) {
				$success++;
			}
		}

		return ($success == $count);
	}

	/**
	 * Get all messages as batch
	 *
	 * @param string $getter  Callable getter
	 * @param array  $options Getter options
	 *
	 * @return ElggBatch
	 */
	public function getAll($getter = 'elgg_get_entities', $options = []) {
		$options['limit'] = 0;
		$options = $this->getFilterOptions($options);

		return new ElggBatch($getter, $options);
	}

	/**
	 * Get preceding messages
	 *
	 * @param array $options Additional options
	 *
	 * @return mixed
	 */
	public function getMessagesBefore(array $options = []) {
		$options['wheres'][] = function(QueryBuilder $qb) {
			return $qb->compare('e.guid', 'lt', $this->message->guid);
		};

		$options['order_by'] = [new OrderByClause('e.guid', 'DESC')];

		$messages = elgg_get_entities($this->getFilterOptions($options));

		if (is_array($messages)) {
			return array_reverse($messages);
		}

		return $messages;
	}

	/**
	 * Get succeeding messages
	 *
	 * @param array $options Additional options
	 *
	 * @return mixed
	 */
	public function getMessagesAfter(array $options = []) {
		$options['wheres'][] = function(QueryBuilder $qb) {
			return $qb->compare('e.guid', 'gt', $this->message->guid);
		};

		return elgg_get_entities($this->getFilterOptions($options));
	}

	/**
	 * Returns an array of getter options for retrieving attachments in the thread
	 *
	 * @param array $options Additional options
	 *
	 * @return array
	 */
	public function getAttachmentsFilterOptions(array $options = []) {
		$hash = $this->message->getHash();

		$options['metadata_name_value_pairs'][] = [
			'name' => 'msgHash',
			'value' => $hash,
		];

		$options['wheres'][] = function(QueryBuilder $qb) {
			$qb->joinRelationshipTable('e', 'guid', 'attached', true, 'inner', 'er_attached');
		};

		return $options;
	}

	/**
	 * Returns an array of attachments in the thread
	 *
	 * @param array $options Additional options
	 *
	 * @return ElggEntity[]|false|int
	 */
	public function getAttachments(array $options = []) {
		$options = $this->getAttachmentsFilterOptions($options);

		return elgg_get_entities($options);
	}

	/**
	 * Returns a count of attachments in the thread
	 *
	 * @param array $options Additional options
	 *
	 * @return int
	 */
	public function hasAttachments(array $options = []) {
		$options['count'] = true;

		return $this->getAttachments($options);
	}

}
