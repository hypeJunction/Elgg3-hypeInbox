<?php

namespace hypeJunction\Inbox;

use Elgg\IntegrationTestCase;

/**
 * framework/inbox/message was imported unconditionally in Bootstrap::init(), so it
 * landed in the ESM import map of every page — including the anonymous homepage,
 * where no .inbox-message element it hooks ever renders (bd elgg-migrate-xhigk).
 * Bootstrap::importUserAssets() now guards it behind an authenticated session.
 */
class EagerEsmTest extends IntegrationTestCase {

	public function up(): void {
		_elgg_services()->session_manager->removeLoggedInUser();
	}

	public function down(): void {
		_elgg_services()->session_manager->removeLoggedInUser();
	}

	public function testInboxModuleNotImportedForAnonymous(): void {
		_elgg_services()->session_manager->removeLoggedInUser();
		$this->assertFalse(elgg_is_logged_in());

		Bootstrap::importUserAssets();

		$this->assertNotContains(
			'framework/inbox/message',
			_elgg_services()->esm->getImports(),
			'the inbox module must not load on anonymous pages'
		);
	}

	public function testInboxModuleImportedForLoggedInUser(): void {
		$user = $this->createUser();
		_elgg_services()->session_manager->setLoggedInUser($user);

		Bootstrap::importUserAssets();

		$this->assertContains(
			'framework/inbox/message',
			_elgg_services()->esm->getImports(),
			'the inbox module must load once a user is authenticated'
		);

		_elgg_services()->session_manager->removeLoggedInUser();
	}
}
