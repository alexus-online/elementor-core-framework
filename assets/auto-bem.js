/**
 * Auto-BEM Class Generator — Elementor V4 editor integration.
 *
 * Registers an `elements/context-menu/groups` filter that adds
 * "BEM-Klassen aus Struktur" to container/section/flexbox/div-block
 * right-click. On click, opens a modal showing the element tree with
 * editable BEM names (pre-slugified from each element's _title). Apply
 * POSTs to /wp-json/ecf-framework/v1/bem/apply which registers classes
 * in Elementor's Global Classes Registry + tags each widget's
 * settings.classes.value.
 */
(function () {
	'use strict';

	/**
	 * elementType filter: show the BEM action on any container-like element.
	 * Explicit list covers V3 (container/section/column) + V4 atomic
	 * (e-flexbox/e-div-block) + Layrix's own e-layrix-section. The regex
	 * catches future custom container widgets that follow the *-section /
	 * *-container / *-block / *-flexbox / *-wrapper naming convention.
	 */
	var BEM_EXPLICIT_TARGETS = [
		'container', 'section', 'column',
		'e-flexbox', 'e-div-block', 'e-layrix-section',
	];
	var BEM_PATTERN = /(^|-)(section|container|block|flexbox|wrapper|grid)$/i;

	function isBemTarget(elementType) {
		if (!elementType) return false;
		if (BEM_EXPLICIT_TARGETS.indexOf(elementType) !== -1) return true;
		return BEM_PATTERN.test(elementType);
	}

	function init() {
		if (typeof window.elementor === 'undefined' || !window.elementor.hooks) return;

		window.elementor.hooks.addFilter('elements/context-menu/groups', function (groups, elementType) {
			if (!isBemTarget(elementType)) return groups;

			groups.push({
				name: 'layrix-bem',
				actions: [
					{
						name: 'layrix-generate-bem',
						title: 'BEM-Klassen aus Struktur',
						icon: 'eicon-code',
						// Inline HTML in `shortcut` is rendered raw — same trick
						// Elementor uses for its purple "New" badge (editor.js:35458).
						// Custom class .layrix-bem-shortcut-badge styles it (CSS file).
						shortcut: '<span class="layrix-bem-shortcut-badge">LAYRIX</span>',
						// V1/V4 callback signature: (openMenuEvent, meta). We read
						// the right-clicked element via elementor.selection — see
						// getElementId() — so neither arg is needed here.
						callback: function () {
							openBemModal();
						},
					},
				],
			});
			return groups;
		});
	}

	function getConfig() {
		return window.layrixBem || { restUrl: '', nonce: '', postId: 0 };
	}

	function getPostId() {
		try {
			if (window.elementor && elementor.config && elementor.config.document && elementor.config.document.id) {
				return parseInt(elementor.config.document.id, 10);
			}
		} catch (e) {}
		return getConfig().postId;
	}

	/**
	 * In Elementor V1 + V4, the context-menu action `callback` is called with
	 * (openMenuEvent, {location, secondaryLocation, trigger}) — NOT with a
	 * Marionette view. To find the right-clicked element we read the current
	 * selection from elementor.selection.getElements(), because right-clicking
	 * an element auto-selects it.
	 */
	function getElementId() {
		try {
			if (!window.elementor || !elementor.selection || !elementor.selection.getElements) return null;
			var selected = elementor.selection.getElements() || [];
			if (!selected.length) return null;
			var c = selected[0];
			// V4 container shape — id is on the container directly or nested model
			if (c && c.id) return c.id;
			if (c && c.model && c.model.get) return c.model.get('id');
			if (c && c.model && c.model.id) return c.model.id;
		} catch (e) {}
		return null;
	}

	/**
	 * Build a {element_id: visible_navigator_label} map by walking the live
	 * editor's container tree. Server-side fallback chain
	 * (editor_settings.title → _title → elType) misses the widget's
	 * get_title() display name that the Navigator actually shows for
	 * unrenamed elements. By passing this map alongside the preview
	 * request, PHP can use it as a fourth fallback before resorting to
	 * elType, so an unrenamed "Layrix Section" yields `layrix-section`
	 * (nicer) instead of a generic stripped elType.
	 */
	function buildNavigatorLabels() {
		var map = {};
		try {
			if (!window.elementor || !elementor.documents) return map;
			var doc = elementor.documents.getCurrent && elementor.documents.getCurrent();
			if (!doc || !doc.container) return map;
			walkContainerForLabels(doc.container, map);
		} catch (e) {}
		return map;
	}

	function walkContainerForLabels(container, map) {
		if (!container) return;
		var id = container.id || (container.model && (container.model.id || (container.model.get && container.model.get('id'))));
		if (id) {
			var label = readContainerLabel(container);
			if (label) map[id] = label;
		}
		var children = container.children;
		if (!children) return;
		// Marionette Collection: .each / Backbone-style
		if (typeof children.each === 'function') {
			children.each(function (c) { walkContainerForLabels(c, map); });
		} else if (children.length) {
			for (var i = 0; i < children.length; i++) walkContainerForLabels(children[i], map);
		}
	}

	function readContainerLabel(c) {
		try {
			// Property may be string or function depending on container variant
			if (typeof c.label === 'string' && c.label) return c.label;
			if (typeof c.label === 'function') {
				var l = c.label();
				if (l) return l;
			}
			// Widget-type's display title — what Navigator shows when not renamed
			if (c.getElementType) {
				var t = c.getElementType();
				if (t && t.title) return t.title;
			}
			// Settings-level _title (V1 fallback)
			if (c.model && c.model.get) {
				var settings = c.model.get('settings');
				if (settings) {
					var v1 = settings.get ? settings.get('_title') : settings._title;
					if (v1) return v1;
				}
				var editorSettings = c.model.get('editor_settings');
				if (editorSettings) {
					var t4 = editorSettings.title || (editorSettings.get && editorSettings.get('title'));
					if (t4) return t4;
				}
			}
		} catch (e) {}
		return '';
	}

	function escapeHtml(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function buildModalShell() {
		var existing = document.querySelector('.layrix-bem-overlay');
		if (existing) return existing;

		var overlay = document.createElement('div');
		overlay.className = 'layrix-bem-overlay';
		overlay.innerHTML =
			'<div class="layrix-bem-modal" role="dialog" aria-labelledby="layrix-bem-title">' +
			'  <div class="layrix-bem-head">' +
			'    <span class="layrix-bem-title" id="layrix-bem-title">BEM Class Generator</span>' +
			'    <button type="button" class="layrix-bem-close" aria-label="Schließen">&times;</button>' +
			'  </div>' +
			'  <div class="layrix-bem-body"></div>' +
			'  <div class="layrix-bem-foot">' +
			'    <button type="button" class="layrix-bem-btn layrix-bem-cancel">Abbrechen</button>' +
			'    <button type="button" class="layrix-bem-btn layrix-bem-apply">Klassen anwenden</button>' +
			'  </div>' +
			'</div>';
		document.body.appendChild(overlay);

		overlay.querySelector('.layrix-bem-close').addEventListener('click', closeModal);
		overlay.querySelector('.layrix-bem-cancel').addEventListener('click', closeModal);
		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) closeModal();
		});
		document.addEventListener('keydown', escClose);

		return overlay;
	}

	function escClose(e) {
		if (e.key === 'Escape') closeModal();
	}

	function closeModal() {
		var overlay = document.querySelector('.layrix-bem-overlay');
		if (overlay) overlay.remove();
		document.removeEventListener('keydown', escClose);
	}

	function showLoading() {
		var overlay = buildModalShell();
		overlay.querySelector('.layrix-bem-body').innerHTML =
			'<div class="layrix-bem-loading">Lade Struktur…</div>';
		overlay.querySelector('.layrix-bem-apply').disabled = true;
	}

	function showError(msg) {
		var overlay = buildModalShell();
		overlay.querySelector('.layrix-bem-body').innerHTML =
			'<div class="layrix-bem-error">' + escapeHtml(msg) + '</div>';
		overlay.querySelector('.layrix-bem-apply').disabled = true;
	}

	/**
	 * Ensure the current editor state is persisted to _elementor_data before
	 * we read it via REST. Without this, freshly added widgets / renames
	 * aren't visible to PHP. Tries V4's $e command first, falls back to V1's
	 * saver, then resolves anyway as last resort.
	 */
	function saveDocument() {
		return new Promise(function (resolve) {
			try {
				if (window.$e && typeof $e.run === 'function') {
					var p = $e.run('document/save/default');
					if (p && typeof p.then === 'function') {
						p.then(function () { resolve(); }, function () { resolve(); });
						return;
					}
				}
				if (window.elementor && elementor.saver) {
					if (typeof elementor.saver.savePromise === 'function') {
						elementor.saver.savePromise({ status: 'draft' }).then(resolve, resolve);
						return;
					}
					if (typeof elementor.saver.update === 'function') {
						elementor.saver.update({ status: 'draft' });
						setTimeout(resolve, 600);
						return;
					}
				}
			} catch (e) {}
			resolve();
		});
	}

	function openBemModal() {
		var elementId = getElementId();
		var postId = getPostId();
		var cfg = getConfig();

		var missing = [];
		if (!elementId)   missing.push('elementId (keine Selektion?)');
		if (!postId)      missing.push('postId');
		if (!cfg.restUrl) missing.push('REST-URL');
		if (missing.length) {
			try { console.log('[Layrix BEM] missing:', missing, 'selection =', window.elementor && elementor.selection && elementor.selection.getElements()); } catch (e) {}
			showError('BEM-Generator: ' + missing.join(' · '));
			return;
		}

		showLoading();
		var body = buildModalShell().querySelector('.layrix-bem-body');
		body.innerHTML = '<div class="layrix-bem-loading">Speichere Editor-Stand…</div>';

		saveDocument().then(function () { fetchPreview(postId, elementId, cfg); });
	}

	function fetchPreview(postId, elementId, cfg) {
		var body = buildModalShell().querySelector('.layrix-bem-body');
		body.innerHTML = '<div class="layrix-bem-loading">Lade Struktur…</div>';

		fetch(cfg.restUrl + '/bem/preview', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify({
				post_id: postId,
				element_id: elementId,
				navigator_labels: buildNavigatorLabels(),
			}),
		})
			.then(function (r) {
				return r.json().then(function (j) {
					return { ok: r.ok, body: j };
				});
			})
			.then(function (res) {
				if (!res.ok || !res.body || !res.body.success) {
					var msg = (res.body && res.body.message) || ('HTTP ' + (res.ok ? 200 : 'err'));
					showError('Preview fehlgeschlagen: ' + msg);
					return;
				}
				renderTree(res.body, postId);
			})
			.catch(function (err) {
				showError('Netzwerkfehler: ' + (err && err.message ? err.message : 'unbekannt'));
			});
	}

	/**
	 * Convert flat [{depth: N, ...}] list (from PHP, DFS-ordered) into a
	 * nested tree using a stack — supports arbitrary depth jumps.
	 */
	function buildNested(flatList) {
		var root = { _children: [], depth: 0 };
		var stack = [root];
		flatList.forEach(function (item) {
			item._children = [];
			while (stack.length && stack[stack.length - 1].depth >= item.depth) {
				stack.pop();
			}
			if (!stack.length) stack.push(root);
			stack[stack.length - 1]._children.push(item);
			stack.push(item);
		});
		return root._children;
	}

	/**
	 * Render modifier chips under an element/block. Each modifier has its own
	 * input whose data-el-id points at the TARGET (not the modifier layer);
	 * apply accumulates per target so the modifier class joins the target's
	 * widget alongside its base class.
	 */
	function renderModifiersHtml(modifiers, target_el_id) {
		if (!modifiers || !modifiers.length) return '';
		return '<div class="layrix-bem-modifiers">' +
			modifiers.map(function (m) {
				return '<div class="layrix-bem-mod-row">' +
					'  <div class="layrix-bem-chip layrix-bem-chip--mod">Mod</div>' +
					'  <div class="layrix-bem-meta layrix-bem-meta--mod">' + escapeHtml(m.original_label) + '</div>' +
					'  <input type="text" class="layrix-bem-input" data-el-id="' + escapeHtml(target_el_id) + '" value="' + escapeHtml(m.suggested_class) + '">' +
					'</div>';
			}).join('') +
			'</div>';
	}

	function renderElementCard(d) {
		var childrenHtml = '';
		if (d._children && d._children.length) {
			childrenHtml = '<div class="layrix-bem-children">' +
				d._children.map(renderElementCard).join('') +
				'</div>';
		}
		return '<div class="layrix-bem-card">' +
			'  <div class="layrix-bem-row">' +
			'    <div class="layrix-bem-chip">Label</div>' +
			'    <div class="layrix-bem-meta">' + escapeHtml(d.original_label) +
			(d.is_default_label ? ' <span class="layrix-bem-hint">(Default)</span>' : '') +
			'    </div>' +
			'    <input type="text" class="layrix-bem-input" data-el-id="' + escapeHtml(d.element_id) + '" value="' + escapeHtml(d.suggested_class) + '">' +
			'  </div>' +
			renderModifiersHtml(d.modifiers, d.element_id) +
			childrenHtml +
			'</div>';
	}

	function renderTree(data, postId) {
		var overlay = buildModalShell();
		var body = overlay.querySelector('.layrix-bem-body');

		var html = '<div class="layrix-bem-card layrix-bem-card--block">' +
			'  <div class="layrix-bem-row">' +
			'    <div class="layrix-bem-chip layrix-bem-chip--block">Label</div>' +
			'    <div class="layrix-bem-meta">' + escapeHtml(data.block_label || '') +
			(data.block_is_default ? ' <span class="layrix-bem-hint">(Default — Element im Navigator umbenennen für besseren Block-Namen)</span>' : '') +
			'    </div>' +
			'    <input type="text" class="layrix-bem-input" data-el-id="' + escapeHtml(data.block_element_id) + '" value="' + escapeHtml(data.block_suggested) + '">' +
			'  </div>' +
			renderModifiersHtml(data.block_modifiers, data.block_element_id);

		if (data.descendants && data.descendants.length) {
			var nested = buildNested(data.descendants);
			html += '<div class="layrix-bem-children">' +
				nested.map(renderElementCard).join('') +
				'</div>';
		}
		html += '</div>';

		if (!data.descendants || !data.descendants.length) {
			html += '<div class="layrix-bem-empty">Dieses Element hat keine Children. BEM-Generator macht hier nichts.</div>';
		}

		body.innerHTML = html;
		overlay.querySelector('.layrix-bem-apply').disabled = false;
		overlay.querySelector('.layrix-bem-apply').onclick = function () {
			doApply(overlay, postId);
		};
	}

	function doApply(overlay, postId) {
		var inputs = overlay.querySelectorAll('.layrix-bem-input');
		// classes: { element_id => [class1, class2, ...] } — multiple inputs
		// can target the same element_id (base + modifiers from --layers).
		var classes = {};
		inputs.forEach(function (inp) {
			var id = inp.getAttribute('data-el-id');
			var val = inp.value.trim();
			if (!id || !val) return;
			if (!classes[id]) classes[id] = [];
			if (classes[id].indexOf(val) === -1) classes[id].push(val);
		});

		if (!Object.keys(classes).length) {
			showError('Keine Klassen zum Anwenden.');
			return;
		}

		var btn = overlay.querySelector('.layrix-bem-apply');
		btn.disabled = true;
		btn.textContent = 'Wird angewendet…';

		var cfg = getConfig();
		fetch(cfg.restUrl + '/bem/apply', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify({ post_id: postId, classes: classes }),
		})
			.then(function (r) {
				return r.json().then(function (j) {
					return { ok: r.ok, body: j };
				});
			})
			.then(function (res) {
				if (!res.ok || !res.body || !res.body.success) {
					var msg = (res.body && res.body.message) || ('HTTP ' + (res.ok ? 200 : 'err'));
					showError('Apply fehlgeschlagen: ' + msg);
					btn.disabled = false;
					btn.textContent = 'Klassen anwenden';
					return;
				}
				closeModal();
				var note =
					'BEM angewendet: ' + res.body.classes_created + ' neu, ' +
					res.body.classes_reused + ' wiederverwendet, ' +
					res.body.elements_tagged + ' Widgets getaggt. Editor lädt neu…';
				try {
					if (window.elementor && elementor.notifications && elementor.notifications.showToast) {
						elementor.notifications.showToast({ message: note });
					}
				} catch (e) {}
				setTimeout(function () { window.location.reload(); }, 900);
			})
			.catch(function (err) {
				showError('Netzwerkfehler: ' + (err && err.message ? err.message : 'unbekannt'));
				btn.disabled = false;
				btn.textContent = 'Klassen anwenden';
			});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
