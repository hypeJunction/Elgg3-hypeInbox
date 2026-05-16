<?php

namespace hypeJunction\Inbox;

use Elgg\Database\Seeds\Seed;

/**
 * Seeds fake private messages between random users for development and testing.
 */
class Seeder extends Seed {

	/**
	 * {@inheritdoc}
	 */
	public function seed() {
		$this->advance($this->getCount());

		while ($this->seedsCount() < $this->getCount()) {
			$sender = $this->getRandomUser();
			$recipient = $this->getRandomUser([$sender->guid]);

			if (!$sender || !$recipient) {
				break;
			}

			$message = new Message();
			$message->owner_guid = $sender->guid;
			$message->container_guid = $sender->guid;
			$message->access_id = ACCESS_PRIVATE;
			$message->title = $this->faker->sentence(4);
			$message->description = $this->faker->paragraph();
			$message->fromId = [$sender->guid];
			$message->toId = [$recipient->guid];
			$message->msgType = Config::TYPE_PRIVATE;
			$message->readYet = false;
			$message->msg = true;

			if (!$message->save()) {
				continue;
			}

			$this->advance();
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function unseed() {
		$entities = elgg_get_entities([
			'type' => 'object',
			'subtype' => 'messages',
			'metadata_name' => '__faker',
			'limit' => false,
			'batch' => true,
		]);

		foreach ($entities as $entity) {
			$entity->delete();
			$this->advance();
		}
	}

	/**
	 * Registers this seeder with the seeds:database event.
	 *
	 * @param \Elgg\Event $event seeds:database event
	 * @return array
	 */
	public static function addSeed(\Elgg\Event $event) {
		$seeds = $event->getValue();
		$seeds[] = self::class;
		return $seeds;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getType(): string {
		return 'messages';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCountOptions(): array {
		return [
			'type' => 'object',
			'subtype' => 'messages',
		];
	}

}
