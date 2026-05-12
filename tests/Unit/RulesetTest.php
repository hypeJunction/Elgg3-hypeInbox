<?php

namespace hypeJunction\Inbox\Tests\Unit;

use hypeJunction\Inbox\Ruleset;
use PHPUnit\Framework\TestCase;

class RulesetTest extends TestCase {

    public function testDefaultsAreApplied() {
        $ruleset = new Ruleset('test_type', []);

        $this->assertFalse($ruleset->isPersistent());
        $this->assertFalse($ruleset->allowsMultipleRecipients());
        $this->assertFalse($ruleset->allowsAttachments());
        $this->assertTrue($ruleset->hasSubject());
        $this->assertEmpty($ruleset->getPolicies());
    }

    public function testRulesetWithAllOptions() {
        $ruleset = new Ruleset('custom_type', [
            'multiple' => true,
            'persistent' => true,
            'attachments' => true,
            'no_subject' => true,
            'labels' => [
                'singular' => 'Custom Message',
                'plural' => 'Custom Messages',
            ],
        ]);

        $this->assertTrue($ruleset->isPersistent());
        $this->assertTrue($ruleset->allowsMultipleRecipients());
        $this->assertTrue($ruleset->allowsAttachments());
        $this->assertFalse($ruleset->hasSubject());
    }

    public function testGetSingularLabelReturnsFalseKeyWhenNoLanguage() {
        $ruleset = new Ruleset('test_type', [
            'labels' => [
                'singular' => 'Test Message',
                'plural' => 'Test Messages',
            ],
        ]);

        $this->assertStringContainsString('test_type', $ruleset->getSingularLabel(false));
        $this->assertStringContainsString('test_type', $ruleset->getPluralLabel(false));
    }

    public function testGetSingularLabelReturnsEnglishLabel() {
        $ruleset = new Ruleset('test_type', [
            'labels' => [
                'singular' => 'Test Message',
                'plural' => 'Test Messages',
            ],
        ]);

        $this->assertEquals('Test Message', $ruleset->getSingularLabel('en'));
        $this->assertEquals('Test Messages', $ruleset->getPluralLabel('en'));
    }

    public function testNormalizeRulesetMergesDefaults() {
        $ruleset = new Ruleset('type', ['persistent' => true]);
        $this->assertTrue($ruleset->isPersistent());
        $this->assertFalse($ruleset->allowsMultipleRecipients());
    }
}
