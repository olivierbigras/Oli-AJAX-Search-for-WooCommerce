(function ($) {
	'use strict';

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	ready(function () {
		var button = document.getElementById('submit-oli-as-menu-item');
		if (!button || !window.oliAsNavMenu) {
			return;
		}

		button.addEventListener('click', function (event) {
			event.preventDefault();
			var box = document.getElementById('oli-as-menu-item');
			if (!box) {
				return;
			}

			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'menu-item[-1][menu-item-title]';
			input.value = window.oliAsNavMenu.title || '';
			box.appendChild(input);

			var type = document.createElement('input');
			type.type = 'hidden';
			type.name = 'menu-item[-1][menu-item-type]';
			type.value = 'custom';
			box.appendChild(type);

			var url = document.createElement('input');
			url.type = 'hidden';
			url.name = 'menu-item[-1][menu-item-url]';
			url.value = '#oli-ajax-search';
			box.appendChild(url);

			var classes = document.createElement('input');
			classes.type = 'hidden';
			classes.name = 'menu-item[-1][menu-item-classes]';
			classes.value = 'oli-as-menu-item';
			box.appendChild(classes);

			if (window.wpNavMenu && typeof window.wpNavMenu.addItemToMenu === 'function' && $) {
				window.wpNavMenu.addItemToMenu($(box).find('input').serialize(), 'oli-as-menu-item', function () {});
			}
		});
	});
})(window.jQuery);
