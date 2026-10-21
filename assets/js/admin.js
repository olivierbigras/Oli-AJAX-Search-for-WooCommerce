(function () {
	'use strict';

	if (!window.oliAsAdmin) {
		return;
	}

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	ready(function () {
		if (window.jQuery && window.jQuery.fn.wpColorPicker) {
			window.jQuery('.oli-as-color').wpColorPicker();
		}

		var rebuild = document.getElementById('oli-as-rebuild');
		var clearBtn = document.getElementById('oli-as-clear-analytics');

		function post(action) {
			var body = new window.URLSearchParams();
			body.set('action', action);
			body.set('nonce', window.oliAsAdmin.nonce);
			return window.fetch(window.oliAsAdmin.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			});
		}

		function paintStatus(data) {
			if (!data) {
				return;
			}
			var state = document.getElementById('oli-as-index-state');
			var message = document.getElementById('oli-as-index-message');
			var progress = document.getElementById('oli-as-index-progress');
			var counts = document.getElementById('oli-as-index-counts');
			if (state) {
				state.textContent = data.state || '';
			}
			if (message) {
				message.textContent = data.message || '';
			}
			if (progress) {
				progress.max = Math.max(1, parseInt(data.total, 10) || 1);
				progress.value = parseInt(data.processed, 10) || 0;
			}
			if (counts) {
				counts.textContent = (data.processed || 0) + ' / ' + (data.total || 0);
			}
		}

		var poll = null;
		function pollStatus() {
			post('oli_as_index_status').then(function (json) {
				paintStatus(json && json.data);
				if (json && json.data && json.data.state === 'building') {
					poll = window.setTimeout(pollStatus, 1500);
				}
			});
		}

		if (rebuild) {
			rebuild.addEventListener('click', function () {
				rebuild.disabled = true;
				post('oli_as_rebuild_index').then(function (json) {
					paintStatus(json && json.data);
					pollStatus();
					rebuild.disabled = false;
				});
			});
			pollStatus();
		}

		var advanced = document.getElementById('oli-as-show-advanced');
		if (advanced) {
			advanced.addEventListener('change', function () {
				document.querySelector('.oli-as-admin').classList.toggle('is-advanced', advanced.checked);
				var body = new window.URLSearchParams();
				body.set('action', 'oli_as_toggle_advanced');
				body.set('nonce', window.oliAsAdmin.nonce);
				body.set('on', advanced.checked ? '1' : '0');
				window.fetch(window.oliAsAdmin.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				});
			});
		}

		if (clearBtn) {
			clearBtn.addEventListener('click', function () {
				if (!window.confirm(clearBtn.textContent)) {
					return;
				}
				post('oli_as_clear_analytics').then(function () {
					window.location.reload();
				});
			});
		}
	});
})();
