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
		$(function () {
			var page_slug = document.getElementById ('settings-page').dataset.page;
			var $input = $('#'+page_slug+'-png-reduce-colors-enable');
			var $hide1 = $('tr.'+page_slug+'-png-reduce-colors-dither');
			var $hide2 = $('tr.'+page_slug+'-png-reduce-max-colors-count');
			$input.on ('change', function() {
				if ($input.is(':checked')) {
					$hide1.show('slow');
					$hide2.show('slow');
				} else {
					$hide1.hide('slow');
					$hide2.hide('slow');
				}
			});
			$input.trigger ('change');
		});
	} else {
		console.log ('jQuery function not available (' + typeof $ + ')');
	}
}(jQuery));
