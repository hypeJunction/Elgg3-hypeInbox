<?php

namespace hypeJunction\Inbox\Tests\Integration;

use Elgg\IntegrationTestCase;
use hypeJunction\Inbox\Message;

/**
 * @group integration
 */
class MessageTest extends IntegrationTestCase {

    public function testMessageSubtypeIsMessages() {
        $message = new Message();
        $this->assertEquals('messages', $message->getSubtype());
    }

    public function testMessageSubtypeConstant() {
        $this->assertEquals('messages', Message::SUBTYPE);
    }

    public function testMessageTypeConstants() {
        $this->assertEquals('__notification', Message::TYPE_NOTIFICATION);
        $this->assertEquals('__private', Message::TYPE_PRIVATE);
    }

    public function testFactoryCreatesMessageInstance() {
        $message = Message::factory([
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'message_type' => Message::TYPE_PRIVATE,
        ]);

        $this->assertInstanceOf(Message::class, $message);
    }
}
