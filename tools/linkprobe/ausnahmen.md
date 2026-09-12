# Ausnahmen und bekannte Abweichungen

Die Grundregel lautet: **jeder Verweis nennt einen Parameter, den seine
Zielseite liest.** Zwei Tabellen halten fest, was davon abweicht — und beide
sind absichtlich kurz.

Der Regelfall braucht **keinen** Eintrag: Eine Zielseite, die ihre Parameter
über eine Variable liest (`$_GET[$name]`, wie `konten_param()` in
`admin_users.php`), erkennt die Probe selbst und zählt sie getrennt.

Die erste Spalte ist jeweils der **Verweis** in der Form `seite.php?name=`,
nicht die Zeilennummer: So überlebt die Liste jede Umsortierung des
Quelltextes. Ein Eintrag, den kein Verweis mehr braucht, wird gemeldet und
macht den Lauf rot — eine tote Zeile ist eine Regel, die niemand mehr prüft,
und bei einer behobenen Abweichung ist genau das der Punkt, an dem sie hier
verschwinden muss.

## 1. Ausnahmen — der Abgleich ist nicht zu führen

Ein Eintrag heißt nicht „hier darf der Verweis ins Leere gehen", sondern
„hier ist der Abgleich aus einem benannten Grund nicht zu führen". Diese
Tabelle ist heute leer, und das ist der gewünschte Zustand.

| Verweis | Grund |
|---|---|

## 2. Bekannte Abweichungen — Fehler mit Nummer

Hier steht, was die Probe zu Recht findet und was **noch nicht behoben ist**.
Jeder Eintrag nennt seine Backlog-Nummer; ohne Nummer gehört nichts hierher.
Das ist der Unterschied zum Ausblenden: Der Fund bleibt sichtbar, er ist
gezählt, und er hat einen Ort, an dem über ihn entschieden wird.

| Verweis | Backlog | Sache |
|---|---|---|

Diese Tabelle ist seit Web 19.1.2 **leer**, und das ist der gewünschte
Zustand. Ihr einziger Eintrag war `index.php?day=` (Backlog Nr. 151) — der
Verweis nach einem Import, der still auf den jüngsten statt auf den
importierten Diensttag führte. Er ist behoben: `api/import_commit.php`
liefert seither die Tageskennung als `first_day_id`, und `import_ui.js`
verweist auf `index.php?d=`. Die Zeile ist mit der Behebung verschwunden,
wie es der Kopf dieses Dokuments verlangt.
