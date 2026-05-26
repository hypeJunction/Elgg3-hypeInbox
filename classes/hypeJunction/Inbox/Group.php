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

	public static function create($data): self {
		$group = new static();
		return $group->add($data);
	}

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

	public function guids(): array {
		return array_unique($this->guids);
	}

	public function entities(): array {
		return array_filter(array_map('get_entity', $this->guids()));
	}

	protected function toGuid($entity): int {
		if ($entity instanceof ElggEntity) {
			return (int) $entity->getGUID();
		}
		if (\elgg_entity_exists($entity)) {
			return (int) $entity;
		}
		return 0;
	}
}
