# Vergleichswerkzeug

Kommt derselbe Bestand nach einem Umlauf unverändert wieder heraus? Eine
Zahl statt eines Eindrucks: Das Werkzeug benennt jede Abweichung vom
Referenzzustand.

## Aufruf

```bash
python3 kreislauf.py --art csv|edbak|edbak-alt --frisch   # Konto → Einspielen → Export → Vergleich
python3 vergleichen.py --art csv a.zip b.zip [--ausnahmen ausnahmen/csv_umlauf.json] [--bericht …]
python3 vergleichen.py --art edbak --testabweichung a.edbak a.edbak --passwort …   # Probe aufs Exempel
python3 pruefkonto.py anlegen|loeschen umlauf-<name>@gen-em.org   # Prüfkonto für Proben (RP-01)
```

## Was es misst

**Klartext**, Feld für Feld, über einen natürlichen Schlüssel (`client_ref`
im Backup, Diensttag und Beginn im CSV) — Chiffretext nie, sein IV ist
Zufall. Flüchtiges ersetzt `normalisieren.py` durch Marken; welche Felder
und warum `created_at` eines Einsatzes nicht mehr dazugehört, steht in
seinem Kopf. Drei Läufe gegen zwei Referenzen: Kopf von `kreislauf.py`.

## Was es braucht

Die örtliche Anlage über **HTTPS** (`127.0.0.1:8443`): Das Sitzungs-Cookie
trägt `Secure`, und Pythons `requests` schickt es über HTTP nicht — „Anmeldung
gescheitert: unbekannt" heißt dann: die Adresse prüfen, nicht das Passwort.

## Erwartete Zahl

**0 unerklärte Abweichungen**; die erwarteten stehen mit Zahl und Grund im
Bericht. Stand 14.09.2026 mit `--frisch`: csv 9 120 Vergleiche / 1 021
erwartet, edbak 287 687 / 16. `--testabweichung`: CSV 10, Backup 12 Proben.

## Was es nicht kann

Das Demo-Konto ist **veränderlich** — vor einem Vergleichsexport erst
zurücksetzen. Nach einem Wiederaufbau des Bestands weichen Gerätekennungen
und `created_at` ab, und das ist richtig (`docs/Technik.md`, Runbook).
