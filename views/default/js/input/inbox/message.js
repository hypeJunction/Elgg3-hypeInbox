import * as elgg from 'elgg';
import $ from 'jquery';
import ckeditor from 'elgg/ckeditor';
import { echo } from 'elgg/i18n';

var input = {
	init: function () {
		input.bindEvents();
		$('.elgg-input-message-body:not([data-cke-init])').each(function () {
			$(this).attr('data-cke-init', true);
			$('.ckeditor-toggle-editor[href="#' + $(this).attr('id') + '"]')
					.html(echo('ckeditor:visual')).show();
			ckeditor.init(this);
		});
	},
	bindEvents: function () {

		ckeditor.bind();

		$(document).on('click focus', '.elgg-input-message-body', function (e) {
			if ($(this).data('ckeditorInstance')) {
				$(this).data('ckeditorInstance').focus();
			}
		});

		$(document).on('reset', 'form', function () {
			$(this).find('.elgg-input-message-body[data-cke-init]').each(function () {
				if ($(this).data('ckeditorInstance')) {
					$(this).data('ckeditorInstance').setData('');
				}
			});
		});

		input.bindEvents = elgg.nullFunction;
	}
};

export default input;
