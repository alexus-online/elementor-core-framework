# AI Page Builder — Design Spec
**Datum:** 2026-04-20  
**Status:** Approved

---

## Übersicht

Ein eigenständiges WordPress-Plugin das ein Bild oder eine URL entgegennimmt, das Design per AI-Vision analysiert, eine Vorschau der erkannten Elementor-Elemente zeigt, und nach Bestätigung automatisch eine Elementor-Seite als Entwurf erstellt.

---

## Ziele

- Design/Webseite → Elementor-Seite mit minimalem manuellem Aufwand
- Kontrolle durch Vorschau vor der Erstellung
- Kostenlos nutzbar (via Gemini) oder mit Premium-Providern (Claude, OpenAI)
- Persönlicher Einsatz (kein Vertrieb in Phase 1)

---

## Flow

```
1. User gibt URL ein ODER lädt Bild hoch
2. PHP holt Screenshot (bei URL) oder nimmt Bild direkt
3. PHP schickt Bild an gewählten AI-Provider (Gemini / Claude / OpenAI)
4. AI gibt strukturiertes JSON zurück (Sektionen → Spalten → Widgets + Inhalt)
5. Admin-Seite zeigt Vorschau der erkannten Elemente
6. User wählt: "Nur Struktur" oder "Struktur + Inhalt"
7. User gibt Seitentitel ein und klickt "Seite erstellen"
8. PHP baut Elementor-JSON und erstellt WordPress-Seite (Entwurf)
9. Link zum Elementor-Editor der neuen Seite
```

---

## Admin-Interface

**Menüpunkt:** "AI Page Builder" im WP-Hauptmenü (eigenes Icon)

### Tab 1 — Analyse
- Eingabefeld: URL einer Webseite
- ODER: Datei-Upload (PNG, JPG, WebP)
- Button: "Analysieren"
- Vorschau-Bereich: erkannte Sektionen/Spalten/Widgets (nach Analyse)
- Toggle: "Nur Struktur" / "Struktur + Inhalt"
- Eingabefeld: Titel der neuen WordPress-Seite
- Button: "Elementor-Seite erstellen"
- Nach Erstellung: "Seite im Elementor-Editor öffnen →"

### Tab 2 — Einstellungen
- Dropdown: AI-Provider wählen (Gemini / Claude API / OpenAI GPT-4o)
- Eingabefeld: API-Key (mit Inline-Test: "Key gültig ✓" / "Key ungültig ✗")
- Dropdown: Modell (z.B. gemini-2.0-flash / claude-sonnet-4-6 / gpt-4o)
- Kostenlimit: optionales Eingabefeld in USD (Warnung + Blockierung bei Überschreitung)
- Speichern-Button

### Tab 3 — Kosten & Verlauf
- Tabelle: Datum, Provider, Tokens (Input/Output), Kosten pro Analyse
- Gesamt-Kosten-Anzeige (kumulativ)
- Preise sind fest im Plugin hinterlegt und nach Provider/Modell aufgeschlüsselt

---

## AI-Prompt-Strategie

### Prompt (an alle Provider identisch)
```
Analyze this webpage/design image and return ONLY a valid JSON array 
representing the layout as Elementor widgets. No explanation, only JSON.

For each section identify:
- number of columns (1, 2, 3, or 4)
- for each column: list of widgets with type and content

Supported widget types:
heading, text-editor, image, button, divider, spacer, icon-box, 
counter, gallery, testimonial, tabs, accordion, video, posts, google-maps

Return format:
[
  {
    "section": 1,
    "columns": 2,
    "widgets": [
      { "column": 1, "type": "heading", "content": "Title text" },
      { "column": 2, "type": "image", "content": "" }
    ]
  }
]
```

### Fallback
Falls die AI kein valides JSON zurückgibt: Rohantwort anzeigen + Fehlermeldung "Analyse fehlgeschlagen — bitte erneut versuchen".

---

## URL-Analyse-Strategie

Bei URL-Eingabe wird **kein Screenshot** gemacht. Stattdessen:
1. `class-url-fetcher.php` holt den HTML-Quelltext der Seite via WP HTTP API
2. HTML wird bereinigt (script/style-Tags entfernt, auf Body-Inhalt reduziert)
3. Bereinigtes HTML wird als Text an die AI geschickt (statt Bild)
4. Der AI-Prompt wird angepasst: "Analyze this HTML and return Elementor widget JSON..."

Vorteil: kein Screenshot-Service nötig, kein extra API-Key, funktioniert mit allen 3 Providern.

---

## Dateistruktur

```
ai-page-builder/
├── ai-page-builder.php              # Haupt-Plugin-Datei, Hooks, Aktivierung
├── includes/
│   ├── class-admin-page.php         # Admin-UI, Tabs, AJAX-Handler
│   ├── class-ai-analyzer.php        # Schnittstelle zu Gemini / Claude / OpenAI
│   ├── class-url-fetcher.php        # URL → HTML-Fetch + Bereinigung (WP HTTP API)
│   ├── class-elementor-builder.php  # AI-JSON → Elementor-_elementor_data JSON
│   ├── class-widget-map.php         # Widget-Typ → Elementor-Widget-Konfiguration
│   └── class-cost-tracker.php       # Token-Zähler, Kosten-Berechnung, Verlauf
├── assets/
│   ├── admin.js                     # Analyse-Request, Vorschau, Bestätigung (AJAX)
│   └── admin.css                    # Styles Admin-Seite
└── templates/
    └── preview.php                  # Vorschau-Template erkannte Elemente
```

---

## Elementor-Seiten-Erstellung

Elementor speichert Seitenstruktur als Post-Meta:
- `_elementor_data` → JSON-Array der Elemente
- `_elementor_edit_mode` → `"builder"`
- `_elementor_template_type` → `"page"`

Vorgehen:
1. `wp_insert_post()` mit Status `draft`
2. `update_post_meta()` mit den drei Meta-Keys
3. Link zurückgeben: `get_edit_post_link($post_id)`

---

## Phase 1 — Unterstützte Widgets (15)

| Erkannter Typ | Elementor Widget | Anmerkung |
|---|---|---|
| heading | heading | H1–H4 je nach Größe |
| text-editor | text-editor | Fließtext, Absätze |
| image | image | Platzhalter-Bild |
| button | button | Text aus Inhalt |
| divider | divider | Trennlinie |
| spacer | spacer | Leeraum |
| icon-box | icon-box | Icon + Titel + Text |
| counter | counter | Zahl + Label |
| gallery | image-gallery | Grid-Galerie |
| testimonial | testimonial | Zitat + Name |
| tabs | tabs | Tab-Container |
| accordion | accordion | Aufklapp-Elemente |
| video | video | YouTube/Vimeo |
| posts | posts | Blog-Posts-Grid |
| google-maps | google-maps | Karteneinbettung |

## Phase 2 — Spätere Erweiterung
Formulare, Slider, Preistabellen, Progress Bar, Icon-Liste, Social Icons, Countdown, Flip Box

---

## AI-Provider-Details

| Provider | Modell (Standard) | Kosten (ca.) | API-Key nötig |
|---|---|---|---|
| Google Gemini | gemini-2.0-flash | kostenlos (Free Tier) | Ja (kostenlos) |
| Anthropic Claude | claude-sonnet-4-6 | ~$3 / 1M Input-Token | Ja (bezahlt) |
| OpenAI | gpt-4o | ~$2.50 / 1M Input-Token | Ja (bezahlt) |

---

## Fehlerbehandlung

| Fehler | Verhalten |
|---|---|
| URL nicht erreichbar | Fehlermeldung, kein Absturz |
| AI gibt kein valides JSON | Rohantwort + "Erneut versuchen" |
| API-Key ungültig | Inline-Test beim Speichern mit Feedback |
| Elementor nicht installiert | Warnung beim Plugin-Aktivieren |
| Kostenlimit erreicht | Analyse blockiert mit Hinweis |
| Upload zu groß | Max. 10 MB, Fehlermeldung |

---

## Build-Prompt für Claude Code / ChatGPT Codex

```
Build a WordPress plugin called "AI Page Builder" from scratch.

## Goal
Analyze a design image or URL using AI Vision and automatically create 
an Elementor page in WordPress based on the detected layout.

## Plugin Structure
ai-page-builder/
├── ai-page-builder.php
├── includes/
│   ├── class-admin-page.php
│   ├── class-ai-analyzer.php
│   ├── class-url-fetcher.php
│   ├── class-elementor-builder.php
│   ├── class-widget-map.php
│   └── class-cost-tracker.php
├── assets/
│   ├── admin.js
│   └── admin.css
└── templates/
    └── preview.php

## Admin Interface
- WP main menu item "AI Page Builder"
- Tab 1 "Analyse": URL input OR image upload → "Analysieren" button → 
  preview of detected elements → toggle "Structure only / Structure + Content" 
  → page title input → "Create Elementor Page" button → link to editor
- Tab 2 "Settings": provider dropdown (Gemini/Claude/OpenAI), API key input 
  with inline validation, model dropdown, cost limit field
- Tab 3 "Costs": table of past analyses with tokens + cost per call, total cost

## AI Analysis
Send image to chosen provider with this prompt:
"Analyze this webpage/design image and return ONLY a valid JSON array 
representing the layout as Elementor widgets. No explanation, only JSON.
Supported types: heading, text-editor, image, button, divider, spacer, 
icon-box, counter, gallery, testimonial, tabs, accordion, video, posts, 
google-maps
Format: [{"section":1,"columns":2,"widgets":[{"column":1,"type":"heading","content":"Title"}]}]"

## Supported Providers
- Google Gemini (gemini-2.0-flash) — free tier
- Anthropic Claude (claude-sonnet-4-6) — paid
- OpenAI (gpt-4o) — paid

## Elementor Page Creation
Create WP post (draft), set post meta:
- _elementor_data → JSON array of elements
- _elementor_edit_mode → "builder"  
- _elementor_template_type → "page"

## Widget Mapping (Phase 1, 15 widgets)
heading→heading, text-editor→text-editor, image→image, button→button,
divider→divider, spacer→spacer, icon-box→icon-box, counter→counter,
gallery→image-gallery, testimonial→testimonial, tabs→tabs,
accordion→accordion, video→video, posts→posts, google-maps→google-maps

## Error Handling
- URL unreachable → error message
- AI returns invalid JSON → show raw response + retry hint
- Invalid API key → inline test on save with ✓/✗ feedback
- Elementor not installed → warning on plugin activation
- Cost limit reached → block analysis with notice
- Upload too large → max 10MB

## Cost Tracking
Store per-analysis: provider, input tokens, output tokens, cost (USD).
Hardcode prices: Gemini free, Claude Sonnet $3/1M input $15/1M output,
GPT-4o $2.50/1M input $10/1M output.

## Tech Stack
- PHP 8.0+, WordPress 6.0+, Elementor 3.0+ (free)
- Vanilla JS for admin (no React/Vue)
- WP AJAX API for async requests
- WP HTTP API for external requests
- WP Options API for settings storage
```

---

## Nicht im Scope (Phase 1)

- Kein Verkauf / keine Mehrbenutzer-Unterstützung
- Keine automatische Farb-/Font-Übernahme
- Keine Echtzeit-Vorschau im Elementor-Editor
- Keine Unterstützung für Elementor Pro-only Widgets
