/**
 * UI Widgets — Preset Undo Bar, Sync Status Dot, Empty State Hints, Setup Wizard
 *
 * Dependencies (via ctx):
 *   ctx.restUrl                    — REST endpoint for settings
 *   ctx.restNonce                  — WP REST nonce
 *   ctx.buildVariableSyncHash      — fn(payload) → hash string
 *   ctx.buildSettingsPayloadFromForm — fn() → settings object
 *   ctx.switchPanel                — fn(panelName) → void
 */

export function initUiWidgets($, ctx) {
  var restUrl                    = ctx.restUrl;
  var restNonce                  = ctx.restNonce;
  var buildVariableSyncHash      = ctx.buildVariableSyncHash;
  var buildSettingsPayloadFromForm = ctx.buildSettingsPayloadFromForm;
  var switchPanel                = ctx.switchPanel;

  // ── Preset Undo Bar ────────────────────────────────────────────
  (function() {
    var undoKey = 'ecfPresetUndo';
    try {
      var stored = window.sessionStorage.getItem(undoKey);
      if (!stored) return;
      var $bar = $('<div class="ecf-undo-bar" role="status">'
        + '<span class="ecf-undo-bar__msg">Preset angewendet.</span>'
        + '<button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-undo-bar__action">Rückgängig</button>'
        + '<button type="button" class="ecf-undo-bar__dismiss" aria-label="Schließen">×</button>'
        + '</div>');
      $('.ecf-main').prepend($bar);

      $bar.find('.ecf-undo-bar__action').on('click', function() {
        try {
          var payload = JSON.parse(stored);
          $bar.find('.ecf-undo-bar__action').prop('disabled', true).text('Wird wiederhergestellt…');
          window.fetch(restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': restNonce },
            body: JSON.stringify({ settings: payload })
          }).then(function() {
            window.sessionStorage.removeItem(undoKey);
            window.location.reload();
          }).catch(function() {
            $bar.find('.ecf-undo-bar__action').prop('disabled', false).text('Rückgängig');
          });
        } catch(e) {}
      });

      $bar.find('.ecf-undo-bar__dismiss').on('click', function() {
        window.sessionStorage.removeItem(undoKey);
        $bar.remove();
      });
    } catch(e) {}
  })();

  // ── Sync-Status Indikator in Sidebar ──────────────────────────
  function updateSyncStatusDot(state) {
    var $dot = $('[data-ecf-sync-dot]');
    if (!$dot.length) return;
    $dot.attr('data-ecf-sync-dot', state || 'unknown');
  }

  // Beim Start: wenn letzter Sync-Hash bekannt ist, Status setzen
  try {
    var savedSyncHash = window.sessionStorage.getItem('ecfLastSyncHash') || '';
    var currentHash = buildVariableSyncHash(buildSettingsPayloadFromForm());
    updateSyncStatusDot(savedSyncHash && savedSyncHash === currentHash ? 'synced' : 'pending');
  } catch(e) { updateSyncStatusDot('unknown'); }

  // Nach jedem erfolgreichen Elementor-Sync updaten
  $(document).on('ecf:sync-complete', function() {
    try {
      var hash = buildVariableSyncHash(buildSettingsPayloadFromForm());
      window.sessionStorage.setItem('ecfLastSyncHash', hash);
      updateSyncStatusDot('synced');
    } catch(e) {}
  });
  $(document).on('change input', '[data-group="colors"] input, [data-group="radius"] input, [name^="ecf_framework_v50[spacing]"], [name^="ecf_framework_v50[shadows]"]', function() {
    updateSyncStatusDot('pending');
  });

  // ── Leere-Zustand-Hinweise ────────────────────────────────────
  function checkEmptyTokenGroups() {
    var groups = [
      { selector: '[data-group="colors"] .ecf-row--color',   container: '[data-group="colors"]',  msg: 'Noch keine Farben. Füge deine erste Farbe hinzu oder wende ein Stil-Preset an.' },
      { selector: '[data-group="radius"] .ecf-row--minmax',  container: '[data-group="radius"]',  msg: 'Noch keine Radien. Füge deinen ersten Radius-Token hinzu.' },
      { selector: '[data-group="shadows"] .ecf-row',         container: '[data-group="shadows"]', msg: 'Noch keine Schatten. Füge deinen ersten Shadow-Token hinzu.' },
    ];
    groups.forEach(function(g) {
      var $container = $(g.container);
      if (!$container.length) return;
      var hasRows = $(g.selector).length > 0;
      var $existing = $container.find('.ecf-empty-state');
      if (!hasRows && !$existing.length) {
        $container.append('<div class="ecf-empty-state"><p>' + g.msg + '</p></div>');
      } else if (hasRows && $existing.length) {
        $existing.remove();
      }
    });
  }
  checkEmptyTokenGroups();
  $(document).on('click', '.ecf-add-row, .ecf-remove-row', function() {
    setTimeout(checkEmptyTokenGroups, 100);
  });

  // ── Setup-Wizard (Guided Tour) ────────────────────────────────
  var wizardKey = 'ecfSetupWizardDone';

  // Schritte: step 0 = Modal, steps 1-N = Sidebar-Callout, letzter = Toast
  var wizardSteps = [
    {
      mode:  'modal',
      title: 'Willkommen bei Layrix',
      body:  'Layrix verwaltet deine Design-Tokens zentral und überträgt sie mit einem Klick in Elementor. Dieser kurze Guide zeigt dir alle Bereiche.',
      next:  'Los geht\'s'
    },
    {
      mode:  'callout',
      panel: 'tokens',
      title: 'Farben & Radius',
      body:  'Definiere hier deine Markenfarben und Border-Radien. Du kannst auch ein <strong>Stil-Preset</strong> anwenden um sofort loszulegen.',
      next:  'Weiter'
    },
    {
      mode:  'callout',
      panel: 'typography',
      title: 'Typografie',
      body:  'Schriftarten, Schriftgrößen und Typografie-Skala. Lege fest welche Fonts auf der Webseite verwendet werden und wie groß Überschriften im Verhältnis zum Fließtext sind.',
      next:  'Weiter'
    },
    {
      mode:  'callout',
      panel: 'spacing',
      title: 'Abstände',
      body:  'Definiere dein Spacing-System — die Basis-Abstände und den Rhythmus zwischen Elementen. Layrix generiert daraus eine vollständige Skala.',
      next:  'Weiter'
    },
    {
      mode:  'callout',
      panel: 'shadows',
      title: 'Schatten',
      body:  'Erstelle wiederverwendbare Schatten-Tokens. Von subtilen Elevation-Effekten bis zu ausgeprägten Drop Shadows.',
      next:  'Weiter'
    },
    {
      mode:  'callout',
      panel: 'variables',
      title: 'Variablen',
      body:  'Eigene CSS-Variablen die über Elementor hinaus gelten — z.B. für Animationszeiten, Z-Index-Stufen oder andere Design-Werte.',
      next:  'Weiter'
    },
    {
      mode:  'callout',
      panel: 'utilities',
      title: 'Klassen',
      body:  'Globale Elementor-Klassen für wiederkehrende Stile. Wähle aus der Bibliothek oder erstelle eigene Klassen die in jedem Widget verfügbar sind.',
      next:  'Weiter zu Sync'
    },
    {
      mode:  'callout',
      panel: 'sync',
      title: 'Elementor synchronisieren',
      body:  'Wenn alle Tokens eingerichtet sind, klicke auf <strong>Native Elementor Sync</strong>. Alle Tokens stehen danach als CSS-Variablen direkt im Elementor-Editor zur Verfügung.',
      next:  'Fertig'
    },
    {
      mode:  'toast',
      title: 'Du bist startklar!',
      body:  'Alle Bereiche kennengelernt, Elementor synchronisiert — los geht\'s.'
    }
  ];

  var currentWizardStep = 0;
  var $wizardCallout    = null;
  var $wizardNavPulse   = null; // aktives Nav-Item mit Puls-Ring

  function wizardDots(activeIdx) {
    return wizardSteps
      .filter(function(s) { return s.mode !== 'toast'; })
      .map(function(_, i) {
        return '<span class="ecf-wizard__step-dot' + (i === activeIdx ? ' is-active' : '') + '"></span>';
      }).join('');
  }

  function closeWizardCallout() {
    if ($wizardCallout)  { $wizardCallout.remove();  $wizardCallout  = null; }
    if ($wizardNavPulse) { $wizardNavPulse.removeClass('ecf-wizard-nav-pulse'); $wizardNavPulse = null; }
    $(window).off('resize.ecfWizard scroll.ecfWizard');
  }

  function positionCallout($callout, $anchor) {
    var rect   = $anchor[0].getBoundingClientRect();
    var top    = rect.top + (rect.height / 2);
    var left   = rect.right + 14; // 14px Abstand von der Sidebar
    $callout.css({ top: top, left: left });
  }

  function showWizardStep(idx) {
    var step = wizardSteps[idx];
    if (!step) return;
    currentWizardStep = idx;

    if (step.mode === 'modal') {
      closeWizardCallout();
      if ($('.ecf-wizard-overlay').length) return;

      var $overlay = $('<div class="ecf-wizard-overlay" aria-modal="true" role="dialog" aria-label="Einrichtungsassistent"></div>');
      var $modal = $('<div class="ecf-wizard">'
        + '<button type="button" class="ecf-wizard__close" aria-label="Schließen">×</button>'
        + '<div class="ecf-wizard__steps">' + wizardDots(idx) + '</div>'
        + '<div class="ecf-wizard__title">' + $('<span>').text(step.title).html() + '</div>'
        + '<div class="ecf-wizard__body">' + $('<span>').text(step.body).html() + '</div>'
        + '<div class="ecf-wizard__footer">'
        + '<button type="button" class="ecf-btn ecf-btn--secondary ecf-wizard__next">' + $('<span>').text(step.next).html() + '</button>'
        + '</div>'
        + '</div>');
      $overlay.append($modal);
      $('body').append($overlay);

      $modal.find('.ecf-wizard__next').on('click', function() {
        $overlay.remove();
        showWizardStep(idx + 1);
      });
      $modal.find('.ecf-wizard__close').on('click', function() {
        try { window.sessionStorage.setItem(wizardKey, '1'); } catch(e) {}
        $overlay.remove();
      });

    } else if (step.mode === 'callout') {
      switchPanel(step.panel);
      closeWizardCallout();

      // Nav-Item für diesen Panel hervorheben
      var $anchor = $('[data-panel="' + step.panel + '"]').first();
      $anchor.addClass('ecf-wizard-nav-pulse');
      $wizardNavPulse = $anchor;

      // Callout aufbauen
      $wizardCallout = $('<div class="ecf-wizard-callout" role="dialog" aria-label="Wizard">'
        + '<div class="ecf-wizard-callout__arrow"></div>'
        + '<div class="ecf-wizard-callout__steps">' + wizardDots(idx) + '</div>'
        + '<strong class="ecf-wizard-callout__title">' + $('<span>').text(step.title).html() + '</strong>'
        + '<p class="ecf-wizard-callout__body">' + step.body + '</p>'
        + '<div class="ecf-wizard-callout__footer">'
        + '<button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-wizard-callout__skip">Überspringen</button>'
        + '<button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--sm ecf-wizard-callout__next">' + $('<span>').text(step.next).html() + '</button>'
        + '</div>'
        + '</div>');

      $('body').append($wizardCallout);
      positionCallout($wizardCallout, $anchor);

      // Neu positionieren bei Resize
      $(window).on('resize.ecfWizard', function() {
        if ($wizardCallout) positionCallout($wizardCallout, $anchor);
      });

      // Esc schließt den Callout (als "Überspringen")
      $(document).off('keydown.ecfWizardCallout').on('keydown.ecfWizardCallout', function(ev) {
        if (ev.key === 'Escape') {
          try { window.sessionStorage.setItem(wizardKey, '1'); } catch(e) {}
          closeWizardCallout();
          $(document).off('keydown.ecfWizardCallout');
        }
      });

      $wizardCallout.find('.ecf-wizard-callout__next').on('click', function() {
        $(document).off('keydown.ecfWizardCallout');
        showWizardStep(idx + 1);
      });
      $wizardCallout.find('.ecf-wizard-callout__skip').on('click', function() {
        $(document).off('keydown.ecfWizardCallout');
        try { window.sessionStorage.setItem(wizardKey, '1'); } catch(e) {}
        closeWizardCallout();
      });

    } else if (step.mode === 'toast') {
      closeWizardCallout();
      try { window.sessionStorage.setItem(wizardKey, '1'); } catch(e) {}

      var $toast = $('<div class="ecf-wizard-toast">'
        + '<span class="ecf-wizard-toast__icon">🎉</span>'
        + '<div><strong>' + $('<span>').text(step.title).html() + '</strong><span>' + $('<span>').text(step.body).html() + '</span></div>'
        + '<button type="button" class="ecf-wizard-toast__close" aria-label="Schließen">×</button>'
        + '</div>');
      $('body').append($toast);
      setTimeout(function() { $toast.addClass('is-visible'); }, 50);
      setTimeout(function() { $toast.removeClass('is-visible'); setTimeout(function() { $toast.remove(); }, 400); }, 4000);
      $toast.find('.ecf-wizard-toast__close').on('click', function() {
        $toast.removeClass('is-visible');
        setTimeout(function() { $toast.remove(); }, 400);
      });
    }
  }

  function showWizard() {
    if ($('.ecf-wizard-overlay').length || $wizardCallout) return;
    showWizardStep(currentWizardStep);
  }

  // Automatisch beim ersten Besuch zeigen
  try { if (!window.sessionStorage.getItem(wizardKey)) showWizard(); } catch(e) {}

  // Manuell per Knopf starten
  $(document).on('click', '[data-ecf-wizard-start]', function() {
    try { window.sessionStorage.removeItem(wizardKey); } catch(e) {}
    currentWizardStep = 0;
    closeWizardCallout();
    $('.ecf-wizard-overlay').remove();
    showWizard();
  });

  // ── Custom Confirm Modal (Health Fix) ─────────────────────────
  $(document).on('submit', '.ecf-health-fix-form', function(e) {
    e.preventDefault();
    var $form    = $(this);
    var title    = $form.data('ecf-confirm-title') || 'Bereinigen';
    var classes  = [];
    try { classes = JSON.parse($form.attr('data-ecf-confirm-classes') || '[]'); } catch(ex) {}

    var classHtml = '';
    if (classes.length > 0) {
      classHtml = '<ul class="ecf-confirm-modal__list">'
        + classes.map(function(c) {
            return '<li><code class="ecf-confirm-modal__class">.' + $('<span>').text(c).html() + '</code></li>';
          }).join('')
        + '</ul>';
    }

    var $overlay = $('<div class="ecf-confirm-modal-overlay"></div>');
    var $modal   = $('<div class="ecf-confirm-modal" role="dialog" aria-modal="true">'
      + '<div class="ecf-confirm-modal__icon">🗑️</div>'
      + '<h3 class="ecf-confirm-modal__title">' + $('<span>').text(classes.length === 1 ? 'Klasse entfernen?' : 'Klassen entfernen?').html() + '</h3>'
      + '<p class="ecf-confirm-modal__body">'
      + (classes.length === 1
          ? 'Diese Klasse wird aus Elementor entfernt. Sie kann danach neu synchronisiert werden.'
          : 'Diese ' + classes.length + ' Klassen werden aus Elementor entfernt. Sie können danach neu synchronisiert werden.')
      + '</p>'
      + classHtml
      + '<div class="ecf-confirm-modal__footer">'
      + '<button type="button" class="ecf-btn ecf-btn--ghost ecf-confirm-modal__cancel">Abbrechen</button>'
      + '<button type="button" class="ecf-btn ecf-btn--danger ecf-confirm-modal__confirm">'
      + $('<span>').text(title).html()
      + '</button>'
      + '</div>'
      + '</div>');

    $overlay.append($modal);
    $('body').append($overlay);
    setTimeout(function() { $overlay.addClass('is-visible'); }, 10);

    function closeModal() {
      $overlay.removeClass('is-visible');
      setTimeout(function() { $overlay.remove(); }, 250);
    }

    $modal.find('.ecf-confirm-modal__cancel').on('click', closeModal);
    $overlay.on('click', function(ev) { if ($(ev.target).is($overlay)) closeModal(); });
    $(document).one('keydown.ecfConfirm', function(ev) { if (ev.key === 'Escape') closeModal(); });

    $modal.find('.ecf-confirm-modal__confirm').on('click', function() {
      $overlay.remove();
      $form[0].submit();
    });
  });
}
