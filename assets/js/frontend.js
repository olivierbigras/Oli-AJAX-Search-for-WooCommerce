(function () {
	'use strict';

	if (!window.oliAsSettings) {
		return;
	}

	var cfg = window.oliAsSettings;
	var recentKey = 'oli_as_recent';

	function qs(el, sel) {
		return el.querySelector(sel);
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var ctx = this;
			var args = arguments;
			window.clearTimeout(t);
			t = window.setTimeout(function () {
				fn.apply(ctx, args);
			}, wait);
		};
	}

	function getRecent() {
		try {
			var raw = window.localStorage.getItem(recentKey);
			return raw ? JSON.parse(raw) : [];
		} catch (e) {
			return [];
		}
	}

	function pushRecent(query) {
		if (!cfg.showRecent || !query) {
			return;
		}
		var list = getRecent().filter(function (item) {
			return item !== query;
		});
		list.unshift(query);
		try {
			window.localStorage.setItem(recentKey, JSON.stringify(list.slice(0, 6)));
		} catch (e) {
			// Ignore storage errors.
		}
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function fetchSuggestions(query) {
		var suffix = 'q=' + encodeURIComponent(query) + '&lang=' + encodeURIComponent(cfg.lang);
		var pretty = (cfg.prettyUrl || '') + ((cfg.prettyUrl || '').indexOf('?') === -1 ? '?' : '&') + suffix;

		return window.fetch(pretty, {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' }
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('pretty');
			}
			return response.json();
		}).catch(function () {
			var rest = cfg.restUrl + (cfg.restUrl.indexOf('?') === -1 ? '?' : '&') + suffix;
			return window.fetch(rest, {
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': cfg.restNonce,
					Accept: 'application/json'
				}
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('rest');
				}
				return response.json();
			});
		}).catch(function () {
			var ajax = cfg.ajaxUrl + '?action=oli_as_suggest&nonce=' + encodeURIComponent(cfg.nonce) + '&' + suffix;
			return window.fetch(ajax, { credentials: 'same-origin' }).then(function (response) {
				return response.json();
			}).then(function (json) {
				return json && json.success ? json.data : json;
			});
		});
	}

	function renderGroup(title, items, type) {
		if (!items || !items.length) {
			return '';
		}
		var html = '<p class="oli-as-group-title">' + escapeHtml(title) + '</p>';
		items.forEach(function (item, index) {
			html += '<a class="oli-as-item" role="option" data-type="' + escapeHtml(item.type || type) + '" data-index="' + index + '" href="' + escapeHtml(item.url) + '">';
			if (item.image) {
				html += '<img src="' + escapeHtml(item.image) + '" alt="" />';
			}
			html += '<span class="oli-as-item-body"><span class="oli-as-item-title">' + escapeHtml(item.title) + '</span>';
			if (item.price_html) {
				html += '<span class="oli-as-item-meta oli-as-item-price">' + item.price_html + '</span>';
			}
			if (item.sku) {
				html += '<span class="oli-as-item-meta">' + escapeHtml(cfg.i18n.sku) + ': ' + escapeHtml(item.sku) + '</span>';
			}
			html += '</span></a>';
		});
		return html;
	}

	function renderDetails(item) {
		if (!item || item.type !== 'product') {
			return '';
		}
		var html = '';
		if (item.image) {
			html += '<img src="' + escapeHtml(item.image) + '" alt="" />';
		}
		html += '<h3 class="oli-as-details-title">' + escapeHtml(item.title) + '</h3>';
		if (item.price_html) {
			html += '<div class="oli-as-item-price">' + item.price_html + '</div>';
		}
		if (item.sku) {
			html += '<p>' + escapeHtml(cfg.i18n.sku) + ': ' + escapeHtml(item.sku) + '</p>';
		}
		if (item.stock) {
			html += '<p>' + escapeHtml(item.in_stock ? cfg.i18n.inStock : cfg.i18n.outOfStock) + '</p>';
		}
		if (item.excerpt) {
			html += '<p>' + escapeHtml(item.excerpt) + '</p>';
		}
		if (item.add_to_cart_url) {
			html += '<a class="oli-as-add-to-cart" href="' + escapeHtml(item.add_to_cart_url) + '">' + escapeHtml(cfg.i18n.addToCart) + '</a>';
		}
		return html;
	}

	function flatten(groups) {
		var order = ['products', 'categories', 'tags', 'attributes', 'posts', 'pages'];
		var list = [];
		order.forEach(function (key) {
			(groups[key] || []).forEach(function (item) {
				list.push(item);
			});
		});
		return list;
	}

	function bindRoot(root) {
		var form = qs(root, '.oli-as-form');
		var input = qs(root, '.oli-as-input');
		var panel = qs(root, '.oli-as-panel');
		var list = qs(root, '.oli-as-suggestions');
		var details = qs(root, '.oli-as-details');
		var toggle = qs(root, '.oli-as-icon-toggle');
		var items = [];
		var active = -1;
		var overlay = null;

		if (!form || !input || !panel || !list) {
			return;
		}

		function setActive(index) {
			var nodes = list.querySelectorAll('.oli-as-item');
			nodes.forEach(function (node) {
				node.classList.remove('is-active');
			});
			active = index;
			if (nodes[index]) {
				nodes[index].classList.add('is-active');
				if (cfg.showDetails && details) {
					var href = nodes[index].getAttribute('href');
					var item = items.filter(function (row) {
						return row.url === href;
					})[0];
					var html = renderDetails(item);
					details.innerHTML = html;
					details.hidden = !html;
				}
			}
		}

		function openPanel() {
			panel.hidden = false;
		}

		function closePanel() {
			panel.hidden = true;
			if (details) {
				details.hidden = true;
			}
		}

		function showRecent() {
			if (!cfg.showRecent) {
				return;
			}
			var recent = getRecent();
			if (!recent.length) {
				return;
			}
			var html = '<p class="oli-as-group-title">' + escapeHtml(cfg.i18n.recent) + '</p>';
			recent.forEach(function (query) {
				html += '<a class="oli-as-item" href="#"><span class="oli-as-item-title">' + escapeHtml(query) + '</span></a>';
			});
			list.innerHTML = html;
			openPanel();
			list.querySelectorAll('.oli-as-item').forEach(function (node) {
				node.addEventListener('click', function (event) {
					event.preventDefault();
					input.value = node.textContent;
					input.dispatchEvent(new Event('input', { bubbles: true }));
				});
			});
		}

		function paint(data) {
			items = flatten(data.groups || {});
			var html = '';
			html += renderGroup(cfg.i18n.products, (data.groups || {}).products, 'product');
			html += renderGroup(cfg.i18n.categories, (data.groups || {}).categories, 'category');
			html += renderGroup(cfg.i18n.tags, (data.groups || {}).tags, 'tag');
			html += renderGroup(cfg.i18n.attributes, (data.groups || {}).attributes, 'attribute');
			html += renderGroup(cfg.i18n.posts, (data.groups || {}).posts, 'post');
			html += renderGroup(cfg.i18n.pages, (data.groups || {}).pages, 'page');
			if (!items.length) {
				html = '<p class="oli-as-empty">' + escapeHtml(cfg.i18n.noResults) + '</p>';
			} else if (data.results_url) {
				html += '<a class="oli-as-see-all" href="' + escapeHtml(data.results_url) + '">' + escapeHtml(cfg.i18n.seeAll) + '</a>';
			}
			list.innerHTML = html;
			openPanel();
			setActive(items.length ? 0 : -1);
			list.querySelectorAll('.oli-as-item').forEach(function (node, index) {
				node.addEventListener('mouseenter', function () {
					setActive(index);
				});
			});
		}

		var onInput = debounce(function () {
			var query = input.value.trim();
			if (query.length < cfg.minChars) {
				if (!query) {
					showRecent();
				} else {
					closePanel();
				}
				return;
			}
			fetchSuggestions(query).then(function (data) {
				if (!data) {
					return;
				}
				paint(data);
			});
		}, cfg.debounce || 180);

		input.addEventListener('input', onInput);
		input.addEventListener('focus', function () {
			if (!input.value.trim()) {
				showRecent();
			}
			if (cfg.mobileOverlay && window.matchMedia('(max-width: 782px)').matches) {
				openOverlay();
			}
		});

		form.addEventListener('submit', function () {
			pushRecent(input.value.trim());
		});

		input.addEventListener('keydown', function (event) {
			var nodes = list.querySelectorAll('.oli-as-item');
			if (event.key === 'ArrowDown') {
				event.preventDefault();
				setActive(Math.min(nodes.length - 1, active + 1));
			} else if (event.key === 'ArrowUp') {
				event.preventDefault();
				setActive(Math.max(0, active - 1));
			} else if (event.key === 'Enter' && nodes[active] && document.activeElement === input && !panel.hidden) {
				if (nodes[active].getAttribute('href') && nodes[active].getAttribute('href') !== '#') {
					event.preventDefault();
					pushRecent(input.value.trim());
					window.location.href = nodes[active].getAttribute('href');
				}
			} else if (event.key === 'Escape') {
				closePanel();
				closeOverlay();
			}
		});

		document.addEventListener('click', function (event) {
			if (!root.contains(event.target) && (!overlay || !overlay.contains(event.target))) {
				closePanel();
			}
		});

		if (toggle) {
			toggle.addEventListener('click', function () {
				var open = root.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
				if (open) {
					input.focus();
				}
			});
		}

		function openOverlay() {
			if (overlay) {
				return;
			}
			overlay = document.createElement('div');
			overlay.className = 'oli-as-overlay';
			overlay.innerHTML = '<div class="oli-as-overlay-bar"><button type="button" class="oli-as-close">' + escapeHtml(cfg.i18n.close) + '</button></div>';
			document.body.appendChild(overlay);
			document.body.classList.add('oli-as-overlay-open');
			overlay.insertBefore(root, overlay.firstChild.nextSibling);
			qs(overlay, '.oli-as-close').addEventListener('click', closeOverlay);
			input.focus();
		}

		function closeOverlay() {
			if (!overlay) {
				return;
			}
			var placeholder = overlay._placeholder;
			if (!placeholder) {
				placeholder = document.createElement('div');
			}
			if (overlay.parentNode) {
				document.body.classList.remove('oli-as-overlay-open');
			}
			if (root._home && root._home.parentNode) {
				root._home.parentNode.insertBefore(root, root._home);
				root._home.parentNode.removeChild(root._home);
			}
			overlay.parentNode.removeChild(overlay);
			overlay = null;
		}

		root._home = document.createComment('oli-as-home');
		root.parentNode.insertBefore(root._home, root);
	}

	function replaceThemeForms() {
		if (!cfg.replaceThemeForm || !cfg.selectors) {
			return;
		}
		cfg.selectors.forEach(function (selector) {
			document.querySelectorAll(selector).forEach(function (form) {
				if (form.closest('.oli-as-root') || form.getAttribute('data-oli-as-replaced')) {
					return;
				}
				form.setAttribute('data-oli-as-replaced', '1');
				var wrap = document.createElement('div');
				wrap.innerHTML = document.querySelector('.oli-as-root') ? document.querySelector('.oli-as-root').outerHTML : '';
				if (!wrap.firstElementChild) {
					return;
				}
				var clone = wrap.firstElementChild;
				clone.querySelectorAll('[id]').forEach(function (node) {
					node.id = node.id + '-r' + Math.random().toString(16).slice(2);
				});
				form.parentNode.insertBefore(clone, form);
				form.style.display = 'none';
				bindRoot(clone);
			});
		});
	}

	function boot() {
		document.querySelectorAll('[data-oli-as-root]').forEach(bindRoot);
		replaceThemeForms();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
