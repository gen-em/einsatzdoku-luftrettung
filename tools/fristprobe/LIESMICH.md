# Fristprobe — misst der Inhaltsschlüssel dieselbe Frist wie die Sitzung?

```
node tools/fristprobe/pruefe.mjs
```

Rückgabewert `0` = die Frist gleitet wie die der Sitzung, `1` = sie tut es
nicht (oder die Probe selbst ist gescheitert). Braucht keine Datenbank und
keine Installation — nur Chromium und die echten Auslieferungsdateien.

## Warum es sie gibt

**Weil die vorgeschriebene Abnahme nichts belegt.** R44 verlangt: „eine
Sitzung über mehr als 30 Minuten mit Bedienung — im ersten Fall kein Dialog".
Dieser Fall ist **vor und nach der Änderung** grün. Ein Prüfmittel, das in
beiden Zuständen dasselbe sagt, misst nicht die Änderung.

Der Grund dafür ist ein Irrtum im R44-Eintrag selbst, im Rahmenplan-Archiv am
01.09.2026 berichtigt: Läuft die Frist des Inhaltsschlüssels ab, ruft
`contentKey()` `verwerfeInhalt()` — und das lässt den **Datenschlüssel `edk`
bewusst liegen**. Eine Zeile später entpackt `EdCrypto.getContentKey()` den
Inhaltsschlüssel daraus **ohne Passwort** neu. Der Fristablauf kostet also ein
**stilles Neu-Entpacken**, keinen Dialog. Der Dialog fällt nur, wenn `edk`
fehlt oder die Hülle nicht passt — neuer Tab, Browser-Neustart,
Passwort-Reset.

Der Unterschied, den R44 macht, ist deshalb kein verschwundener Dialog,
sondern **eine Zahl**: wie oft der Schlüssel über eine Schicht neu entpackt
werden muss, obwohl ununterbrochen gearbeitet wird.

## Was gemessen wird

Acht Stunden Dienst, alle fünf Minuten eine Seite aufgerufen, **97 Aufrufe
ohne eine einzige Pause**:

| | vorher | nachher |
|---|---|---|
| Neu-Entpackungen des Inhaltsschlüssels | **17** | **1** |
| Leerlauf über die Frist hinaus → Neu-Entpackung | 1 | 1 |

Die zweite Zeile ist die Gegenprobe: Die Frist **greift weiterhin**. Die
Änderung macht sie gleitend, sie schafft sie nicht ab.

## Wie sie das macht

- **Die Uhr wird vorgestellt, nicht abgewartet.** `Date.now()` wird durch
  einen Zähler ersetzt, den die Probe in Schritten vorrückt. Acht Stunden
  dauern damit Millisekunden, und gemessen wird dieselbe Logik.
- **`keyguard.js` ist die echte Auslieferungsdatei**, über einen lokalen
  HTTP-Server geladen (`sessionStorage` braucht eine echte Herkunft, `file://`
  genügt nicht — dasselbe Vorgehen wie in `tools/abmelde-probe/`).
- **Die Fassung von vorher steht wortgleich in `probe.html`**, bis auf die
  eine fehlende Zeile. Nur so lässt sich der Unterschied nebeneinander
  zeigen; ein Vergleich gegen Git wäre hier kein Vergleich, sondern zwei
  Läufe.
- **`EdCrypto` ist ein Doppel**, das zählt statt zu rechnen. Gegenstand ist
  die Frist, nicht die Kryptografie.

**Eine Falle, die hier schon zugeschnappt ist:** Das Doppel muss **vor**
`keyguard.js` geladen werden. `crypto.js` legt `EdCrypto` als `const` auf
oberster Ebene an, und ein `const` ist keine Eigenschaft von `window` — eine
spätere Zuweisung an `window.EdCrypto` überschreibt ihn nicht. Der erste
Anlauf scheiterte genau daran, sichtbar als „Kein Schlüssel bei Minute 0".

## Was sie NICHT belegt

- **Nicht, dass der Entsperrdialog seltener wird.** Er kommt vom tabweisen
  `sessionStorage` und bleibt, so gewollt (Handbuch, „ein Tab, ein
  Schlüssel").
- **Nicht das Verhalten der echten Sitzung.** `auth_guard.php` läuft hier
  nicht mit; dass die Sitzungsfrist eine Inaktivitätsfrist ist, steht dort
  im Code und ist nicht Gegenstand dieser Messung.
- **Nicht die Kryptografie.** Das `EdCrypto`-Doppel entpackt nichts, es zählt.
