# hypeinbox — Architecture (Elgg 5.x)

## Overview

Private messaging plugin for Elgg 5.x. Provides inbox/outbox threading, multi-recipient
messages, message type configuration, read/unread state, and menu integration. Depends on
`hypelists` for list rendering.

## Entry Points

| File | Purpose |
|------|---------|
| `elgg-plugin.php` | Plugin manifest: bootstrap, entities, actions, routes, upgrades |
| `classes/hypeJunction/Inbox/Bootstrap.php` | Plugin lifecycle — event wiring in `init()`, default settings in `activate()` |

## Class Map

| Class | Role |
|-------|------|
| `Bootstrap` | `PluginBootstrap` — registers all event handlers and view extensions |
| `Plugin` | DiContainer — service factory, singleton access via `hypeInbox()` |
| `Message` | `ElggObject` subtype `messages` — core entity, adds `save(): bool`, `delete(bool): bool`, `getDisplayName(): string` |
| `Thread` | Value object wrapping a set of Message entities in a thread |
| `Group` | Value object — typed collection of entity GUIDs |
| `Model` | Business logic — policy enforcement, message CRUD, type resolution |
| `Config` | Plugin settings accessor — reads/writes `default_message_types` and `message_types` as JSON |
| `Router` | Event handlers for `page_owner:system`, `entity:url:object`, `entity:icon:url:object` |
| `Menus` | Event handlers for all menu registrations (page, admin, topbar, entity, user-hover, title, inbox) |
| `Ajax` | Event handler for `output:ajax` — injects unread count into AJAX responses |
| `Notifications` | Event handler for `get_templates:notifications` — registers custom notification templates |
| `Policy` | Access policy checks (can user send/receive a given message type) |
| `Inbox`, `Thread`, `Userpicker`, `SearchRecipients` | Helper/query classes |
| `Upgrades/MigrateSettingsToJson` | Elgg upgrade batch — migrates `serialize()`-stored settings to `json_encode()` |

## Events Registered

| Event | Handler | Purpose |
|-------|---------|---------|
| `page_owner:system` | `Router::resolvePageOwner` | Set page owner for /messages/* routes |
| `entity:url:object` | `Router::messageUrlHandler` | Resolve URL for Message entities |
| `entity:icon:url:object` | `Router::messageIconUrlHandler` | Resolve icon URL for Message entities |
| `config:user_types:framework:inbox` | `Config::filterUserTypes` | Allow third-party plugins to add user type config |
| `register:menu:page` | `Menus::setupPageMenu`, `setupAdminPageMenu`, `setupInboxThreadMenu` | Page sidebar menus |
| `register:menu:inbox` | `Menus::setupInboxMenu` | Inbox tab menus |
| `register:menu:entity` | `Menus::setupMessageMenu` | Per-message entity menus |
| `register:menu:user_hover` | `Menus::setupUserHoverMenu` | User hover menu integration |
| `register:menu:title` | `Menus::setupTitleMenu` (static) | Title menu |
| `register:menu:topbar` | `Menus::setupTopbarMenu` | Topbar unread badge |
| `output:ajax` | `Ajax::setUnreadMessagesCount` | Inject unread count into AJAX output |
| `get_templates:notifications` | `Notifications::registerCustomTemplates` | Custom notification templates |

## Actions

| Action | Access | Purpose |
|--------|--------|---------|
| `messages/send` | logged_in | Send a new message |
| `messages/delete` | logged_in | Delete one or more messages (optionally threaded) |
| `messages/markread` | logged_in | Mark message(s) as read |
| `messages/markunread` | logged_in | Mark message(s) as unread |
| `messages/load` | logged_in | Ajax load of message thread |
| `hypeInbox/settings/save` | admin | Save plugin settings |
| `inbox/admin/import` | admin | Import messages |

## Routes

| Route name | Path | Resource view |
|-----------|------|--------------|
| `view:object:messages` | `/messages/{guid}` | `messages/read` |
| `collection:object:messages:owner` | `/messages/owner/{username}` | `messages/inbox` |
| `collection:object:messages:owner:sent` | `/messages/owner/{username}/sent` | `messages/sent` |
| `collection:object:messages:owner:search` | `/messages/owner/{username}/search` | `messages/search` |
| `add:object:messages` | `/messages/compose` | `messages/compose` |
| `forward:object:messages` | `/messages/forward/{guid}` | `messages/forward` |
| `autocomplete:inbox:guids` | `/autocomplete/inbox/guids` | `SearchRecipients` controller |

## Data Storage

- **Entity subtype**: `object/messages` → `Message` class
- **Plugin settings** (stored as JSON since 5.x migration):
  - `default_message_types` — default message type config array
  - `message_types` — admin-customized message type config array
- **Entity metadata**: `msgHash`, `msgType`, `readYet`, `hiddenFrom`, `hiddenTo`, `msg`, `toId`, `fromId`

## Upgrade Scripts

| Class | Version | Purpose |
|-------|---------|---------|
| `Upgrades/MigrateSettingsToJson` | 2026042301 | Rewrite `serialize()`-stored plugin settings to `json_encode()` |

## Migration Notes (4.x → 5.x, 2026-04-23)

- All `elgg_register_plugin_hook_handler()` → `elgg_register_event_handler()`
- All `\Elgg\Hook` type hints → `\Elgg\Event`; 4-arg `($hook, $type, $return, $params)` → `(Event $event)`
- `elgg_push_breadcrumb()` removed → `elgg_register_menu_item('breadcrumbs', ...)`
- `elgg_format_attributes()` removed → manual HTML with `htmlspecialchars()`
- `elgg_isinstance()` removed → `instanceof` checks
- `get_user_by_username()` removed → `elgg_get_user_by_username()`
- `elgg_set_plugin_setting()` / `elgg_get_plugin_setting()` removed → `$plugin->setSetting()` / `$plugin->getSetting()`
- `elgg_trigger_plugin_hook()` → `elgg_trigger_event_results()`
- `Message::delete($recursive, $threaded)` → `delete(bool $recursive = true): bool`; threaded delete handled at call sites via `$message->thread()->delete()`
- `Message::getDisplayName(): string` — added `: string` return type
- `Message::save(): bool` — already correct
- `Menus::setupTitleMenu` — changed to `static` (PHP 8.2 rejects non-static as static callable)
- `Group::toGuid()` — guarded `elgg_entity_exists()` with `is_numeric()` check (Elgg 5.x requires `int`)
- Plugin settings migrated from `serialize()` to `json_encode()` + upgrade batch shipped
- `elgg-plugin.php` composer version: `^4.0` → `^5.0`; PHP requirement: `>=7.4` → `>=8.2`
- Docker infrastructure: `docker/elgg5/` added (PHP 8.2, Elgg ~5.1, MySQL 8.0)
