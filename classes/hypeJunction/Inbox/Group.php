<?php

namespace hypeJunction\Inbox;

use ElggEntity;

/**
 * Minimal GUID-set utility replacing the removed hypejunction/acl_builder_api
 * dependency (hypeJunction\Access\EntitySet). Collects entity GUIDs, supports
 * chained add() calls, and returns guids()/entities().
 */
class Group {

	protected $guids = [];

	/**
	 * create.
	 *
	 * @param mixed $data data
	 *
	 * @return self
	 */
	public static function create($data): self {
		$group = new static();
		return $group->add($data);
	}

	/**
	 * add.
	 *
	 * @param mixed $data data
	 *
	 * @return self
	 */
	public function add($data = null): self {
		if (is_array($data)) {
			foreach ($data as $elem) {
				$this->add($elem);
			}
		} else {
			$guid = $this->toGuid($data);
			if ($guid) {
				$this->guids[] = $guid;
				sort($this->guids);
			}
		}

		return $this;
	}

	/**
	 * guids.
	 *
	 * @return array
	 */
	public function guids(): array {
		return array_unique($this->guids);
	}

	/**
	 * entities.
	 *
	 * @return array
	 */
	public function entities(): array {
		return array_filter(array_map('get_entity', $this->guids()));
	}

	/**
	 * toGuid.
	 *
	 * @param mixed $entity entity
	 *
	 * @return int
	 */
	protected function toGuid($entity): int {
		if ($entity instanceof ElggEntity) {
			return (int) $entity->getGUID();
		}

		if (is_numeric($entity) && (int) $entity > 0 && elgg_entity_exists((int) $entity)) {
			return (int) $entity;
		}

		return 0;
	}
}
