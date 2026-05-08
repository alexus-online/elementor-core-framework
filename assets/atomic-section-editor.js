/**
 * Layrix Atomic Section editor registration + auto-class injection.
 */
(function() {
  'use strict';

  var TYPE = 'e-layrix-section';
  var auto = window.ecfAutoClasses || null;

  /* ────────────────────────────────────────────────────────────────────
   * Section element type registration (so default_children fires)
   * ────────────────────────────────────────────────────────────────── */
  var registered = false;
  function tryRegister() {
    if (registered) return true;
    if (typeof window.elementor === 'undefined') return false;
    if (!window.elementor.elementsManager) return false;
    var modules = window.elementor.modules || {};
    var elementsModules = modules.elements || {};
    var types = elementsModules.types || {};
    var views = elementsModules.views || {};
    if (!types.AtomicElementBase) return false;
    if (typeof views.createAtomicElementBase !== 'function') return false;
    try {
      var View = views.createAtomicElementBase(TYPE);
      window.elementor.elementsManager.registerElementType(new types.AtomicElementBase(TYPE, View));
      registered = true;
      return true;
    } catch (err) {
      var msg = (err && err.message) || '';
      if (/already registered/i.test(msg)) {
        registered = true;
        return true;
      }
      window.console && window.console.error('[Layrix] atomic registration failed', err);
      return true;
    }
  }
  if (!tryRegister()) {
    if (window.elementor && typeof window.elementor.on === 'function') {
      window.elementor.on('panel:init', tryRegister);
      window.elementor.on('preview:loaded', tryRegister);
    }
    var attempts = 0;
    var interval = setInterval(function() {
      if (tryRegister() || attempts++ > 50) clearInterval(interval);
    }, 200);
  }

  /* ────────────────────────────────────────────────────────────────────
   * Auto-class injection for e-heading + e-button
   * ────────────────────────────────────────────────────────────────── */
  function getElementKey(container) {
    if (!container || !container.model || !container.model.get) return null;
    var widgetType = container.model.get('widgetType');
    if (widgetType) return widgetType;
    return container.model.get('elType') || null;
  }
  function isHeading(c) { return getElementKey(c) === 'e-heading'; }
  function isButton(c)  {
    /* Treat e-button and e-form-submit-button as the same — both should get
       the ecf-button auto-class so they pick up Layrix's button defaults
       (transparent bg, token-driven padding/radius/font) and are not stuck
       with Elementor's hard-coded #375EFB / #000 base styles. */
    var key = getElementKey(c);
    return key === 'e-button' || key === 'e-form-submit-button';
  }
  function isLayrixSection(c) { return getElementKey(c) === 'e-layrix-section'; }

  function getCurrentClassValues(container) {
    if (!container || !container.settings || !container.settings.toJSON) return [];
    var raw = container.settings.toJSON();
    if (!raw || !raw.classes) return [];
    if (Array.isArray(raw.classes)) return raw.classes.slice();
    if (raw.classes.value && Array.isArray(raw.classes.value)) return raw.classes.value.slice();
    return [];
  }

  function setClassValues(container, values) {
    if (!window.$e || !window.$e.run) return;
    try {
      window.$e.run('document/elements/settings', {
        container: container,
        settings: {
          classes: { '$$type': 'classes', 'value': values }
        }
      });
    } catch (err) {
      window.console && window.console.warn('[Layrix] set classes failed', err);
    }
  }

  function ensureClassPresent(container, classId) {
    if (!classId) return;
    var values = getCurrentClassValues(container);
    if (values.indexOf(classId) >= 0) return;
    values.push(classId);
    setClassValues(container, values);
  }

  function unwrapTag(tag) {
    if (!tag) return null;
    if (typeof tag === 'string') return tag.toLowerCase();
    if (typeof tag === 'object' && tag.value) return String(tag.value).toLowerCase();
    return null;
  }

  function getHeadingClassId(container) {
    if (!auto || !auto.headingsEnabled) return null;
    if (!container || !container.settings) return null;
    var rawTag = container.settings.get ? container.settings.get('tag') : null;
    /* Elementor's atomic e-heading widget defaults to h2 when no tag is
       explicitly set on a fresh insert — match that so the initial auto-class
       (ecf-heading-2) reflects the actually rendered tag. */
    var tag = unwrapTag(rawTag) || 'h2';
    var ids = auto.headingClassIds || {};
    return ids[tag] || null;
  }

  function applyAutoClassIfApplicable(container) {
    /* Layrix Section always gets its own class chip — independent of the
       auto-classes toggle (it identifies the widget). */
    if (isLayrixSection(container) && auto && auto.layrixSectionClassId) {
      ensureClassPresent(container, auto.layrixSectionClassId);
    }
    if (!auto || !auto.masterEnabled) return;
    if (isHeading(container)) {
      var headingId = getHeadingClassId(container);
      if (headingId) ensureClassPresent(container, headingId);
      return;
    }
    if (isButton(container)) {
      if (auto.buttonsEnabled && auto.buttonClassId) {
        ensureClassPresent(container, auto.buttonClassId);
        // Elementor v4 atomic style-props live in their own React state
        // and aren't reachable via $e.commands / container.settings. Hack
        // workaround: when the inspector panel is rendered for this
        // selected button, find any input whose value is the schema
        // default #375EFB and replace it with "transparent" using a
        // native value setter + input/change/blur events so React picks
        // it up. Targets ONLY #375EFB, so user-set custom colors stay.
        scheduleInspectorPickerFix();
      }
    }
  }

  function scheduleInspectorPickerFix() {
    // Try a few times because the panel might still be rendering.
    var tries = 0;
    var iv = setInterval(function() {
      tries++;
      var hit = fixInspectorPickerOnce();
      if (hit || tries >= 8) clearInterval(iv);
    }, 150);
  }

  function fixInspectorPickerOnce() {
    var found = false;
    var inputs = document.querySelectorAll('input[type="text"], input[type="color"]');
    var nativeSetter;
    try {
      nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    } catch (e) { return false; }
    inputs.forEach(function(input) {
      var v = (input.value || '').trim();
      if (!/^#?375EFB$/i.test(v)) return;
      if (input.__layrixPickerFixed) return;
      input.__layrixPickerFixed = true;
      try {
        nativeSetter.call(input, 'transparent');
        input.dispatchEvent(new Event('input',  { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('blur',   { bubbles: true }));
        found = true;
      } catch (e) {}
    });
    return found;
  }

  /* When the heading tag changes, swap the matching ecf-heading-N class.
     The class itself carries the typography props (synced via Layrix). */
  function syncHeadingClassOnTagChange(container) {
    if (!auto || !auto.masterEnabled || !auto.headingsEnabled) return;
    if (!isHeading(container)) return;
    var allIds = Object.keys(auto.headingClassIds || {}).map(function(k) {
      return auto.headingClassIds[k];
    });
    var current = getCurrentClassValues(container);
    var desired = getHeadingClassId(container);
    var stripped = current.filter(function(id) { return allIds.indexOf(id) < 0; });
    var next = desired ? stripped.concat([desired]) : stripped;
    var same = current.length === next.length && current.every(function(v, i) { return v === next[i]; });
    if (!same) setClassValues(container, next);
  }

  /* Recursively walk the document tree, applying auto-class + tag watcher.
     Typography/width values come from the synced global classes themselves
     (ecf-heading-N, ecf-container-boxed) — no local style injection here. */
  function visit(container) {
    if (!container) return;
    applyAutoClassIfApplicable(container);
    if (isHeading(container) && container.settings && !container.__layrixTagWatcher) {
      container.settings.on('change:tag', function() {
        syncHeadingClassOnTagChange(container);
      });
      container.__layrixTagWatcher = true;
    }
    var kids = container.children;
    if (kids && kids.length) {
      kids.forEach(visit);
    }
  }

  function scanCurrentDocument() {
    if (!window.elementor || !window.elementor.documents) return;
    var doc = window.elementor.documents.getCurrent && window.elementor.documents.getCurrent();
    if (!doc || !doc.container) return;
    visit(doc.container);
  }

  /* ────────────────────────────────────────────────────────────────────
   * Wiring
   * ────────────────────────────────────────────────────────────────── */
  /* Wenn eine Layrix-Section gerade gedroppt wurde, direkt in den inneren
     Container springen (Elementor selektiert sonst den outer div). User
     fängt damit sofort an Inhalt einzufügen statt erst manuell zu klicken.

     Probiert mehrere Command-Varianten und macht Retries, weil
     default_children async erstellt werden. */
  function _findInnerContainer(c) {
    if (!c) return null;
    var kids = c.children;
    if (!kids) return null;
    /* Elementor v4: plain Array (kein Backbone). Erst prüfen. */
    if (Array.isArray(kids) && kids.length)   return kids[0];
    if (typeof kids.first === 'function')     return kids.first();
    if (typeof kids.at === 'function')        return kids.at(0);
    if (kids.models && kids.models.length)    return kids.models[0];
    if (kids.length && kids[0])               return kids[0];
    return null;
  }

  function _trySelectInner(outerContainer, attempt) {
    attempt = attempt || 0;
    var inner = _findInnerContainer(outerContainer);
    if (!inner) {
      if (attempt < 15) {
        setTimeout(function() { _trySelectInner(outerContainer, attempt + 1); }, 80);
      }
      return;
    }
    if (!window.$e || typeof window.$e.run !== 'function') return;
    var attempts = [
      ['document/elements/select',         { container: inner }],
      ['document/elements/select',         { container: inner, append: false }],
      ['document/elements/select-all',     { containers: [inner] }],
      ['panel/editor/open',                { model: inner.model, view: inner.view }],
    ];
    for (var i = 0; i < attempts.length; i++) {
      try {
        window.$e.run(attempts[i][0], attempts[i][1]);
        return; // erstes erfolgreiches Command genügt
      } catch (err) { /* nächstes probieren */ }
    }
    // Letzter Fallback: Elementor's altes Backbone-API zur Selection
    try {
      if (inner.model && inner.model.trigger) inner.model.trigger('request:edit');
    } catch (err) {}
  }

  function selectInnerOnLayrixSectionCreate(args) {
    if (!args) return;
    var newContainers = args.containers || (args.container ? [args.container] : []);
    if (!newContainers || !newContainers.length) return;
    newContainers.forEach(function(c) {
      if (!isLayrixSection(c)) return;
      setTimeout(function() { _trySelectInner(c); }, 80);
    });
  }

  /* Multi-channel listener: $e.commands ist in v4 nicht zuverlässig (Events-
     Map bleibt leer in manchen Builds). Plus Backbone-Add auf children-
     Collection plus MutationObserver auf Editor-DOM. */
  var _seenLayrixSections = new WeakSet();

  function _handleNewSectionContainer(container) {
    if (!container) return;
    if (_seenLayrixSections.has(container)) return;
    _seenLayrixSections.add(container);
    setTimeout(function() {
      _trySelectInner(container);
      _ensureInnerHasContainerBoxedClass(container);
    }, 80);
  }

  /* Inner-Div des frisch gedroppten Layrix-Section bekommt die
     ecf-container-boxed-Klasse als Chip — auch wenn PHP's
     define_default_children() das schon vorgegeben hat, wird's beim
     Atomic-Element-Type-Rebuild manchmal verloren. JS-Side nachziehen. */
  function _ensureInnerHasContainerBoxedClass(outerContainer, attempt) {
    attempt = attempt || 0;
    if (!auto || !auto.containerBoxedClassId) return;
    var inner = _findInnerContainer(outerContainer);
    if (!inner) {
      if (attempt < 15) setTimeout(function() {
        _ensureInnerHasContainerBoxedClass(outerContainer, attempt + 1);
      }, 80);
      return;
    }
    ensureClassPresent(inner, auto.containerBoxedClassId);
  }

  function _attachChildrenAddListener() {
    if (!window.elementor || !window.elementor.documents) return;
    var doc = window.elementor.documents.getCurrent && window.elementor.documents.getCurrent();
    if (!doc || !doc.container) return;

    function listenOn(container) {
      if (!container || !container.children || typeof container.children.on !== 'function') return;
      if (container.__layrixAddHooked) return;
      container.__layrixAddHooked = true;
      container.children.on('add', function(model) {
        // model ist ein Backbone-Model — dazugehöriger Container ist meistens
        // über elementor.helpers oder Children-Lookup erreichbar.
        try {
          var addedContainer = null;
          // Modern Elementor: Container hat .children mit Backbone-Collection
          // Wir suchen den Container per ID.
          var elType = model.get && (model.get('widgetType') || model.get('elType'));
          if (elType !== 'e-layrix-section') {
            // Auch Sub-Children rekursiv hooken (User dropt evt. Sections in Sections)
            return;
          }
          // Container hat oft .children — finde frisch hinzugefügten in container.children
          if (container.children && container.children.length) {
            // Letztes Kind ist meist das frisch hinzugefügte
            var childContainers = (typeof container.children.toJSON === 'function')
              ? null
              : null;
            // Alternative: über elementor.helpers.containerFromModel
            if (window.elementor && window.elementor.helpers && typeof window.elementor.helpers.findContainer === 'function') {
              addedContainer = window.elementor.helpers.findContainer(model);
            }
          }
          if (!addedContainer) {
            // Fallback: scan ganzer Tree und suche das Section mit gleicher Model-ID
            var sectionId = model.get && model.get('id');
            if (sectionId && doc.container.children) {
              doc.container.children.each(function(c) {
                if (c.id === sectionId) addedContainer = c;
              });
            }
          }
          if (addedContainer) _handleNewSectionContainer(addedContainer);
        } catch (err) {}
      });
    }
    listenOn(doc.container);
  }

  function _attachMutationObserver() {
    if (!window.MutationObserver) return;
    if (window._layrixSectionObserver) return;
    var previewIframe = document.querySelector('#elementor-preview-iframe');
    var iframeDoc = previewIframe && (previewIframe.contentDocument || (previewIframe.contentWindow && previewIframe.contentWindow.document));
    if (!iframeDoc) {
      setTimeout(_attachMutationObserver, 500);
      return;
    }
    var obs = new MutationObserver(function(mutations) {
      mutations.forEach(function(m) {
        if (!m.addedNodes) return;
        m.addedNodes.forEach(function(node) {
          if (!node || node.nodeType !== 1) return;
          var matches = [];
          if (node.matches && node.matches('[data-element_type="e-layrix-section"]')) matches.push(node);
          if (node.querySelectorAll) {
            node.querySelectorAll('[data-element_type="e-layrix-section"]').forEach(function(n) { matches.push(n); });
          }
          matches.forEach(function(domNode) {
            // Aus DOM-Knoten zurück zum Container via Elementor-Helpers
            var modelId = domNode.dataset && (domNode.dataset.id || domNode.getAttribute('data-id'));
            if (!modelId) return;
            // Tree scannen, Container mit dieser ID finden
            if (!window.elementor || !window.elementor.documents) return;
            var doc = window.elementor.documents.getCurrent && window.elementor.documents.getCurrent();
            if (!doc || !doc.container) return;
            function findById(c) {
              if (!c) return null;
              if (c.id === modelId) return c;
              var kids = c.children;
              if (!kids) return null;
              var found = null;
              /* v4: plain Array */
              if (Array.isArray(kids)) {
                for (var i = 0; i < kids.length && !found; i++) found = findById(kids[i]);
              } else if (typeof kids.each === 'function') {
                /* Backbone */
                kids.each(function(k) { if (!found) found = findById(k); });
              }
              return found;
            }
            var container = findById(doc.container);
            if (container) _handleNewSectionContainer(container);
          });
        });
      });
    });
    obs.observe(iframeDoc.body, { childList: true, subtree: true });
    window._layrixSectionObserver = obs;
  }

  function setupCommandListener() {
    if (window._layrixCommandListenerRegistered) return true; // einmal-Guard
    var ok = false;
    /* Channel 1: $e.commands run:after — args.container ist der PARENT
       (wo das neue Element rein erstellt wird), nicht das frisch erstellte
       Element selbst. Wir nehmen darum den letzten Child des Parents. */
    if (window.$e && window.$e.commands && typeof window.$e.commands.on === 'function') {
      try {
        window.$e.commands.on('run:after', function(component, command, args) {
          if (/^document\/elements\/(create|duplicate|paste|import)$/.test(String(command))) {
            setTimeout(scanCurrentDocument, 50);
          }
          if (command !== 'document/elements/create' || !args) return;
          /* Layrix-Section-Detection via args.model — das ist das frisch
             erstellte Element-Definition. */
          var newType = '';
          if (args.model) {
            if (args.model.get && typeof args.model.get === 'function') {
              newType = args.model.get('widgetType') || args.model.get('elType') || '';
            } else {
              newType = args.model.widgetType || args.model.elType || '';
            }
          }
          if (newType !== 'e-layrix-section') return;
          var parent = args.container;
          if (!parent) return;
          /* Frisch erstelltes Element ist der letzte Child des Parents.
             Default_children werden async erzeugt → kurz warten. */
          setTimeout(function() {
            var kids = parent.children;
            if (!kids) return;
            var lastChild = null;
            if (Array.isArray(kids) && kids.length)         lastChild = kids[kids.length - 1];
            else if (typeof kids.last === 'function')        lastChild = kids.last();
            else if (typeof kids.at === 'function' && kids.length) lastChild = kids.at(kids.length - 1);
            else if (kids.length && kids[kids.length - 1])   lastChild = kids[kids.length - 1];
            if (lastChild) _handleNewSectionContainer(lastChild);
          }, 50);
        });
        ok = true;
      } catch (err) {}
    }
    /* Channel 2: document children Backbone add (in v4 plain Array,
       wird hier null-safe abgefangen) */
    try { _attachChildrenAddListener(); } catch (err) {}
    /* Channel 3: MutationObserver auf Editor-DOM */
    try { _attachMutationObserver(); ok = true; } catch (err) {}
    if (ok) window._layrixCommandListenerRegistered = true;
    return ok;
  }

  /* ────────────────────────────────────────────────────────────────────
   * Inject runtime CSS into the preview iframe.
   * v4 atomic preview is sandboxed — wp_head doesn't fire there, so the
   * --ecf-* design tokens never reach the iframe naturally. We push them
   * in via DOM injection on every preview load.
   * ────────────────────────────────────────────────────────────────── */
  var STYLE_ID = 'ecf-framework-v010';
  function getPreviewDoc() {
    if (window.elementor && window.elementor.$preview && window.elementor.$preview[0]) {
      try { return window.elementor.$preview[0].contentDocument; } catch (e) { return null; }
    }
    return null;
  }
  function injectRuntimeCss() {
    if (!auto || !auto.runtimeCss) return;
    var doc = getPreviewDoc();
    if (!doc || !doc.head) return;
    var existing = doc.getElementById(STYLE_ID);
    if (existing) {
      if (existing.textContent !== auto.runtimeCss) existing.textContent = auto.runtimeCss;
      return;
    }
    var style = doc.createElement('style');
    style.id = STYLE_ID;
    style.textContent = auto.runtimeCss;
    doc.head.appendChild(style);
  }

  function init() {
    /* setupCommandListener + scanCurrentDocument laufen unabhängig von auto:
       Layrix-Section-Inner-Auto-Select braucht ecfAutoClasses nicht.
       applyAutoClassIfApplicable() prüft 'auto' selbst. */
    if (!auto) auto = window.ecfAutoClasses || null; // Lazy-Refresh falls erst spät verfügbar
    setupCommandListener();
    setTimeout(scanCurrentDocument, 200);
    setTimeout(injectRuntimeCss, 200);
  }

  if (window.elementor && typeof window.elementor.on === 'function') {
    window.elementor.on('panel:init', function() { setTimeout(init, 50); });
    window.elementor.on('preview:loaded', function() {
      setTimeout(init, 100);
      setTimeout(injectRuntimeCss, 150);
      setTimeout(injectRuntimeCss, 600);
    });
    window.elementor.on('document:loaded', function() {
      setTimeout(scanCurrentDocument, 100);
      setTimeout(injectRuntimeCss, 100);
    });
  }
  setTimeout(init, 600);
  setTimeout(injectRuntimeCss, 1200);
}());
