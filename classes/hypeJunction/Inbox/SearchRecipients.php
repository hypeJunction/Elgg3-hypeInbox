<?php

namespace hypeJunction\Inbox;

use Elgg\BadRequestException;
use Elgg\Database\QueryBuilder;
use Elgg\Http\ResponseBuilder;
use Elgg\HttpException;
use Elgg\Request;
use ElggEntity;
use hypeJunction\Ajax\Context;
use Psr\Log\LogLevel;

class SearchRecipients {

	/**
	 * Autocomplete tags
	 *
	 * @param Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws BadRequestException
	 * @throws HttpException
	 */
	public function __invoke(Request $request) {
		if (elgg_is_xhr()) {
			Context::restore($request);
		} else {
			elgg_signed_request_gatekeeper();
		}

		$message_type = get_input('message_type', Message::TYPE_PRIVATE);
		$options = hypeInbox()->model->getUserQueryOptions($message_type);

		$options['limit'] = 100;

		$query = $request->getParam('q');
		if ($query) {
			$options['metadata_name_value_pairs'][] = [
				'name' => ['name', 'username'],
				'value' => "%{$query}%",
				'operand' => 'like',
				'case_sensitive' => false,
			];
		}

		$value = $request->getParam('value', []);
		$exclude = $request->getParam('exclude', []);

		if (is_array($value) && !empty($value)) {
			$exclude = array_merge($value, $exclude);
		}

		if (!empty($exclude)) {
			$options['wheres'][] = function(QueryBuilder $qb) use ($exclude) {
				return $qb->compare('e.guid', 'NOT IN', $exclude, ELGG_VALUE_INTEGER);
			};
		}

		$exclude_subtypes = $request->getParam('exclude_subtypes', []);
		if (!empty($exclude_subtypes)) {
			$options['wheres'][] = function(QueryBuilder $qb) use ($exclude_subtypes) {
				return $qb->compare('e.subtype', 'NOT IN', $exclude_subtypes, ELGG_VALUE_STRING);
			};
		}

		$entities = elgg_get_entities($options);

		if (empty($entities)) {
			return elgg_ok_response(json_encode([]));
		}

		$data = array_map(function(ElggEntity $e) {
			return [
				'id' => $e->guid,
				'text' => $e->getDisplayName(),
				'iconUrl' => $e->getIconURL(['size' => 'small']),
			];
		}, $entities);

		return elgg_ok_response(json_encode($data));
	}
}