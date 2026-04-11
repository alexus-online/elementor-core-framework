# Legacy Bridge Repo Template

Diese Vorlage ist fuer das alte GitHub-Repository `elementor-core-framework` gedacht.

Ziel:
- Alte Installationen sollen bei GitHub-Update-Checks nicht mehr ins Leere laufen.
- Die alte Hauptdatei bleibt vorhanden.
- WordPress sieht eine hoehere Version und kann auf das neue Layrix-Repo aktualisieren.

So verwenden:
1. Diese beiden Dateien in das alte Repo kopieren.
2. Im aktuellen Layrix-Repo liegt der Root-Shim als `layrix-framework.php`.
3. Im alten Bridge-Repo die Datei `elementor-core-framework.php` bewusst unter dem alten Dateinamen im Repo-Root ablegen.
4. Optional eine kleine `CHANGELOG.md` im alten Repo mit Hinweis auf den Umzug ergaenzen.
5. Das alte Repo mit einer hoeheren Bridge-Version taggen oder auf `master`/`main` bereitstellen.

Wichtig:
- Die `Update URI` zeigt auf das neue Repo `alexus-online/layrix`.
- Die Versionsnummer der Bridge muss hoeher sein als die alte installierte Version.
- Die alte Installation laedt damit kuenftig den Layrix-Updater statt an der Alt-URL zu haengen.
