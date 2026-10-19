# Kennzeichnung — das Sollmaß

Jedes Feld, dessen Inhalt Ende-zu-Ende-verschlüsselt ist (`CLAUDE.md` 4,
`docs/Technik.md` 4.98), trägt ein Schloss — im Formular
(`server/einsatz_form.php`) **und** in der Leseansicht (`server/einsatz.php`).
Diese Tabelle sagt je Feld, **wo**; `kennzeichnung.php` hält den Quelltext
dagegen (Backlog Nr. 170). Eine Zeile, die hier fehlt, misst niemand — wer ein
Feld in den Blob legt, trägt es zuerst hier ein; ein Blobfeld des Katalogs
ohne Zeile ist ein Befund.

Formen: `label:` Beschriftung mit `$SCHLOSS` · `ortsfeld:` Präfix eines
`ui_ortsfeld()` mit `'geschuetzt' => true` · `karte:` Titel einer
`ui_karte_start()` mit `'geschuetzt' => true` · `dt:` Beschriftung in
`dtGeschuetzt()` · `katalog:` Spalte mit `'store' => 'pat'`, gezeichnet über
`PAT_KAT` · seit S11-05b: `katalogort:` Ortsfeld des Katalogs im Blob, im
Formular über `'geschuetzt' => $pat` · `titel:` von Hand gesetzter
Kartentitel mit dem Zeichen · `keins` — das Feld gibt es an dieser Stelle
nicht.

| Feld | Formular | Leseansicht |
|---|---|---|
| Einsatznummer | `label:Einsatznummer` | `dt:Einsatznummer` |
| Nachname | `label:Nachname` | `dt:Name` |
| Vorname | `label:Vorname` | `dt:Name` |
| Geburtsdatum | `label:Geburtsdatum` | `dt:Geboren` |
| Alter | `label:Alter` | `dt:Alter` |
| Diagnose | `label:Diagnose` | `dt:Diagnose` |
| Einsatzort (Adresse und Koordinate) | `ortsfeld:loc` | `dt:Einsatzort` |
| Beschreibung des Einsatzorts | `label:Beschreibung Einsatzort` | `dt:Beschreibung Einsatzort` |
| Manueller Abfahrtort (Adresse und Koordinate) | `ortsfeld:start` | `dt:Abfahrtort` |
| Notizen des Einsatzes | `karte:Notizen` | `katalog:notes` |
| Transportziel (Bezeichnung und Koordinate, seit S11-05b) | `katalogort:transport_dest` | `katalog:transport_dest` |
| Reanimation (seit S11-04 in der Einsatzhülle) | `karte:Reanimation` | `titel:Reanimation` |
| Höhe des Einsatzorts (seit S11-05b im Blob, gerechnet) | `keins` | `dt:Höhe Einsatzort` |

Die Klartext-Freitextfelder stehen nicht in der Tabelle: Die Prüfung liest sie
aus dem Katalog — jedes Feld vom Typ `text` oder `textarea` ohne
`'store' => 'pat'` trägt die Kleinzeile „Klartext — keine Patientendaten".
