<?php

namespace hypeJunction\Inbox;

use Elgg\IntegrationTestCase;

/**
 * Lock in Message entity CRUD on Elgg 4.x so the migration can't silently
 * break subtype mapping, metadata persistence, or delete semantics.
 *
 * Deliberately avoids setSender/setRecipients — those touch access collections
 * and plugin-specific ACL builders that cross into hypelists / hypeautocomplete
 * territory. Those pathways get their own targeted tests if/when needed.
 */
class EntityCrudTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	public function getPluginID(): string {
		return 'hypeinbox';
	}

	private function makeMessage(array $overrides = []): Message {
		return \elgg_call(ELGG_IGNORE_ACCESS, function () use ($overrides) {
			$user = $overrides['__user'] ?? $this->createUser();
			$msg = new Message();
			$msg->owner_guid = $overrides['owner_guid'] ?? $user->guid;
			$msg->container_guid = $overrides['container_guid'] ?? $user->guid;
			$msg->access_id = $overrides['access_id'] ?? ACCESS_PRIVATE;
			if (isset($overrides['subject'])) {
				$msg->setSubject($overrides['subject']);
			}
			if (isset($overrides['body'])) {
				$msg->setBody($overrides['body']);
			}
			$msg->save();
			return $msg;
		});
	}

	public function testInitializeAttributesSetsSubtype(): void {
		$msg = new Message();
		$this->assertSame(Message::SUBTYPE, $msg->getSubtype());
		$this->assertSame('messages', $msg->getSubtype());
	}

	public function testTypeConstantIsObject(): void {
		$this->assertSame('object', Message::TYPE);
	}

	public function testCreatedMessageHasGuid(): void {
		$msg = $this->makeMessage();
		$this->assertGreaterThan(0, $msg->guid);
		$this->assertSame('object', $msg->type);
		$this->assertSame('messages', $msg->getSubtype());
		$msg->delete();
	}

	public function testLoadedMessageIsMessageInstance(): void {
		$msg = $this->makeMessage();
		$guid = $msg->guid;
		\_elgg_services()->entityCache->delete($guid);
		$loaded = \elgg_call(ELGG_IGNORE_ACCESS, fn() => get_entity($guid));
		$this->assertInstanceOf(Message::class, $loaded);
		$msg->delete();
	}

	public function testSubjectMetadataPersists(): void {
		$msg = $this->makeMessage(['subject' => 'Characterization subject']);
		\_elgg_services()->entityCache->delete($msg->guid);
		$loaded = \elgg_call(ELGG_IGNORE_ACCESS, fn() => get_entity($msg->guid));
		$this->assertSame('Characterization subject', (string) $loaded->title);
		$msg->delete();
	}

	public function testBodyMetadataPersists(): void {
		$msg = $this->makeMessage(['body' => 'body goes here']);
		\_elgg_services()->entityCache->delete($msg->guid);
		$loaded = \elgg_call(ELGG_IGNORE_ACCESS, fn() => get_entity($msg->guid));
		$this->assertSame('body goes here', (string) $loaded->description);
		$msg->delete();
	}

	public function testSaveReturnsBoolTrueForNewMessage(): void {
		// Pins the post-migration signature: Message::save(): bool returns
		// true for a successful save. 3.x returned an int GUID — the
		// migration commit 22cf1a7 changed this, and this test pins the
		// new contract so any regression shows up immediately.
		$user = \elgg_call(ELGG_IGNORE_ACCESS, fn() => $this->createUser());
		$msg = new Message();
		$msg->owner_guid = $user->guid;
		$msg->container_guid = $user->guid;
		$msg->access_id = ACCESS_PRIVATE;
		$result = \elgg_call(ELGG_IGNORE_ACCESS, fn() => $msg->save());
		$this->assertTrue($result);
		$this->assertIsInt($msg->guid);
		$this->assertGreaterThan(0, $msg->guid);
		$msg->delete();
	}

	public function testDeleteReturnsTruthy(): void {
		$msg = $this->makeMessage();
		$result = \elgg_call(ELGG_IGNORE_ACCESS, fn() => $msg->delete());
		$this->assertNotFalse($result);
	}

	public function testMessageTypeConstants(): void {
		$this->assertSame(Config::TYPE_PRIVATE, Message::TYPE_PRIVATE);
		$this->assertSame(Config::TYPE_NOTIFICATION, Message::TYPE_NOTIFICATION);
	}

	public function testFactoryReturnsMessageInstance(): void {
		$msg = Message::factory([
			'subject' => 'via factory',
			'body' => 'factory body',
		]);
		$this->assertInstanceOf(Message::class, $msg);
		$this->assertSame('via factory', (string) $msg->title);
		$this->assertSame('factory body', (string) $msg->description);
	}
}
