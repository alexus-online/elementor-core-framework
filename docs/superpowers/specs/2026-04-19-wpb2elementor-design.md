# WPB2Elementor Plugin — Design Spec

## Kontext

Bestehende WordPress-Site (tubeampmanufactur.de) mit WPBakery + WooCommerce + WPML (DE/EN) soll auf eine neue Elementor Pro Instanz migriert werden. ~15 Seiten, lokal in Local by Flywheel. Inhalte wurden bereits übertragen, post_content enthält WPBakery-Shortcodes.

## Ziel

Separates WordPress-Plugin das WPBakery-Shortcodes automatisch in Elementor-JSON konvertiert und in `_elementor_data` Post-Meta schreibt. Einmalige Nutzung, danach optional in Layrix integrierbar.

---

## Architektur

```
wpb2elementor/
├── wpb2elementor.php          # Haupt-Plugin-Datei
├── includes/
│   ├── class-parser.php       # WPBakery Shortcode Parser
│   ├── class-mapper.php       # Mapping-Tabelle WPBakery → Elementor
│   ├── class-converter.php    # Elementor JSON Generator
│   ├── class-claude-api.php   # Claude API für unbekannte Widgets
│   └── class-admin-ui.php     # WordPress Admin Seite
└── assets/
    └── admin.css              # Minimales Styling
```

### Datenfluss

```
post_content (WPBakery Shortcodes)
    → Parser     → strukturierte PHP-Array
    → Mapper     → bekannte Widgets → Elementor Widget-Typen
    → Unbekannte → Claude API (wenn Key) oder HTML-Platzhalter
    → Converter  → Elementor JSON Array
    → _elementor_data (Post Meta) speichern
    → _elementor_edit_mode auf "builder" setzen
```

---

## Admin UI

**Pfad:** WordPress Admin → Tools → WPB2Elementor

### Einstellungsbereich (oben)
- Claude API Key Eingabefeld (optional)
- Speichern Button

### Seitenliste
| Spalte | Inhalt |
|--------|--------|
| Seite | Titel + Link |
| Status | ⚠ WPBakery / ✅ Elementor |
| Aktion | [Konvertieren] / [Zurücksetzen] / — |

- "Alle konvertieren" Button oben
- Erfolgs-/Fehlermeldungen nach jeder Aktion
- Bereits konvertierte Seiten werden nicht überschrieben

---

## WPBakery → Elementor Mapping

| WPBakery | Elementor Widget |
|----------|-----------------|
| `vc_row` | Section |
| `vc_column` | Column |
| `vc_column_text` | Text Editor |
| `vc_custom_heading` | Heading |
| `vc_single_image` | Image |
| `vc_btn` | Button |
| `vc_separator` | Divider |
| `vc_empty_space` | Spacer |
| `vc_video` | Video |
| `vc_gallery` | Image Gallery |
| `vc_icon` | Icon |
| `vc_raw_html` | HTML |
| `vc_accordion` | Accordion |
| `vc_tabs` | Tabs |
| Unbekannt | HTML-Platzhalter oder Claude API |

### Übernommene Styling-Attribute
- Hintergrundfarben (`css` Attribut)
- Abstände (padding/margin)
- Text-Ausrichtung
- Spaltenbreiten (`width` Attribut)

---

## 3 Modi

### Modus-Auswahl (automatisch)
```
API Key vorhanden?
    → JA  → Claude API Modus
    → NEIN → Statisches Mapping + Prompt-Export
```

### Claude API Modus
- Unbekannte Widgets → Claude sendet Elementor JSON zurück
- Modell: claude-haiku-4-5 (günstigster, reicht für Strukturaufgaben)
- Geschätzte Kosten: $0.50–1.00 für 15 Seiten

### Statisches Mapping Modus
- Bekannte Widgets → automatisch konvertiert
- Unbekannte Widgets → HTML-Platzhalter mit originalem Shortcode

### Prompt-Export
- Nach Konvertierung: `wpb2elementor-prompts.txt` im Plugin-Ordner
- Enthält fertige Prompts für alle unbekannten Widgets
- Benutzer kopiert Prompt in claude.ai, Antwort manuell einfügen

---

## Fehlerbehandlung & Sicherheit

- **Backup:** Vor jeder Konvertierung wird `post_content` als `_wpb2el_backup` Post-Meta gesichert
- **Rückgängig:** "Zurücksetzen" Button stellt original `post_content` wieder her und löscht `_elementor_data`
- **Kein Überschreiben:** Seiten mit bestehendem Elementor-Inhalt werden übersprungen (Status ✅)
- **Fehler:** Bei Parsing-Fehler bleibt original Inhalt unberührt, Fehlermeldung in Admin UI

---

## Nicht im Scope

- WPML-Übersetzungen (EN-Seiten müssen separat konvertiert werden)
- WooCommerce-Seiten (Shop, Kasse, Warenkorb — funktionieren automatisch)
- Revolution Slider (wird separat manuell übernommen)
- Live-Preview vor Konvertierung
