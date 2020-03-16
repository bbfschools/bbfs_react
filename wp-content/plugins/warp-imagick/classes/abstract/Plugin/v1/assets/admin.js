/*!
 * Copyright © 2017-2019 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2019 Dragan Đurić. All rights reserved.
 * @author Dragan Đurić <dragan dot djuritj at gmail dot com>
 * @link https://wordpress.org/plugins/warp-imagick/
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

(function ($) {
	if (typeof $ === 'function') {
		var page_slug = false;
		function abstractSettingsSaveState () {
			var sections = document.getElementsByClassName ('accordion-section');
			var sections_string = '';
			var section_state;
			for (var i = 0; i < sections.length; i++) {
				section_state = sections[i].classList.contains('open');
				sections_string += section_state ? '1' : '0';
			}

			var tab_state = 0;
			var tabs = document.getElementsByClassName ('nav-tab-state');
			for (var i = 0; i < tabs.length; i++) {
				if (tabs[i].checked) {
					tab_state = i;
					break;
				}
			}

			setUserSetting (page_slug+'-sections', sections_string);
			setUserSetting (page_slug+'-tabindex', tab_state);
		}

		function abstractSettingsLoadState () {
			var sections_string = getUserSetting (page_slug+'-sections');
			var sections_states = [];
			var section_state;
			for (var i = 0, len = sections_string.length; i < len; i++) {
				section_state = sections_string [i] === '1' ? true : false;
				sections_states.push (section_state);
			}

			if (typeof sections_states === 'array') {
				var sections = document.getElementsByClassName ('accordion-section');
				for (var i = 0; i < sections.length; i++) {
					if (sections_states [i] == 'true') {
						sections [i].classList.add ('open');
					} else {
						sections [i].classList.remove ('open');
					}
				}
			}

			var tab_state = getUserSetting (page_slug+'-tabindex');
			if (typeof tab_state === 'number') {
				var tabs = document.getElementsByClassName ('nav-tab-state');
				for (var i = 0; i < tabs.length; i++) {
					if (i === Number (tab_state)) {
						tabs[i].checked = true;
						break;
					}
				}
			}
		}
		function fixMozillaSlider () { // Mozilla/FF: fix slider thumb position on html-reload.
			$('input[type=range]').each (function (ix, el) {
				el.value = el.defaultValue;
			});
		}
		$(function () {
			page_slug = document.getElementById ('settings-page').dataset.page;
			if (typeof page_slug === 'string' && typeof adminpage === 'string') {
				abstractSettingsLoadState ();
				if (adminpage !== '') {
					postboxes.add_postbox_toggles (adminpage); // Initialize Meta Boxes
				}
				fixMozillaSlider ();
				window.onbeforeunload = function () {
					abstractSettingsSaveState ();
				}
			} else {
				console.log ('No element #settings-page[data-page] or var adminpage found.');
			}
			$('.multiple-input.ui-sortable').sortable ({
				update: function (event, ui) {
				/* 	todo: AYS cannot recognize reordered fields nor can mark form dirty.
					note: Create/use hidden field and change its value?
					ui.helper.closest ('form').trigger ('checkform.areYouSure');
				*/
				}
			});
			$('.multiple-input.ui-sortable').disableSelection ();
			$('.multiple-input.ui-sortable').on ('dblclick','.multiple-input.multiple-remove', function() {
				var $this = $(this);
				var $form = $this.closest ('form');
				$this.parent().remove ();
				$form.trigger ('checkform.areYouSure');
			});
			$('.multiple-input.multiple-append').click (function() {
				var $this = $(this);
				var $form = $this.closest ('form');
				$this.siblings ('.multiple-input.ui-sortable').first ().append (this.dataset.append);
				$form.trigger ('checkform.areYouSure');
			});
		});
	} else {
		console.log ('jQuery function not available (' + typeof $ + ')');
	}
}(jQuery));
