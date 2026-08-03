// Einsatzdoku — Geometrie-Helfer.
//
// Alle Oberflaechen waren auf 260x260 (Fenix 6 Pro) ausgemessen. Mit der
// FR945 (240x240) und der Venu 3s (390x390) kamen zwei weitere Groessen dazu.
// Statt drei Satz Zahlen zu pflegen, werden die urspruenglichen Werte relativ
// gerechnet: s(dc, v) liefert bei 260 EXAKT v zurueck (Ganzzahlrechnung,
// v * 260 / 260 == v), skaliert sonst linear mit der Displayhoehe.
//
// Damit bleibt die Fenix pixelgenau wie zuvor — das ist Abnahmekriterium.
using Toybox.Graphics;
using Toybox.Lang;
using Toybox.Math;

module Ui {

    const REF = 260;                 // Bezugsgeraet: Fenix 6 Pro

    // Laenge v (in Bezugspixeln) auf das aktuelle Display umrechnen
    function s(dc as Graphics.Dc, v as Lang.Number) as Lang.Number {
        return (v * dc.getHeight()) / REF;
    }

    // Anteil der Displayhoehe, z. B. pct(dc, 75) = 75 % der Hoehe
    function pct(dc as Graphics.Dc, p as Lang.Number) as Lang.Number {
        return (dc.getHeight() * p) / 100;
    }

    // Sichtbare Hoehe einer Schrift.
    //
    // getFontHeight() liefert die volle Schriftbox samt Unterlaenge. Bei den
    // Ziffernschriften bleibt die leer — rechnet man Bloecke damit, entsteht
    // unter jeder Zahl eine Luecke von rund 15 Pixeln, und der Block wirkt zu
    // weit oben. Fuer alles, was direkt unter einer Zahl steht, ist deshalb
    // diese Hoehe die richtige.
    //
    // Warum ein fester Faktor und keine Abfrage: Toybox.Graphics.Dc kennt
    // weder getFontDescent noch getFontAscent. Ein "dc has :getFontDescent"
    // uebersteht zwar den Compiler, verengt aber den Typ von dc auf genau
    // dieses Symbol — danach ist getFontHeight nicht mehr auffindbar.
    //
    // NUM_VIS_PCT ist empirisch: Auf der Fenix 6 Pro misst FONT_NUMBER_THAI_HOT
    // 72 px Schriftbox bei rund 15 px leerer Unterlaenge. Wer die Abstaende
    // unter Zahlen aendern will, dreht hier — an einer Stelle fuer alle
    // Oberflaechen.
    const NUM_VIS_PCT = 78;

    function numH(dc as Graphics.Dc, font as Graphics.FontType) as Lang.Number {
        return (dc.getFontHeight(font) * NUM_VIS_PCT) / 100;
    }

    // Nutzbare Breite eines RUNDEN Displays auf Hoehe y (Mitte der Textzeile).
    // Nahe Ober- und Unterkante laeuft der Kreis zu — eine Zeile, die in der
    // Mitte passt, wird dort abgeschnitten. Alle drei Zielgeraete sind rund.
    function chordW(dc as Graphics.Dc, y as Lang.Number) as Lang.Number {
        var d = (dc.getWidth() < dc.getHeight()) ? dc.getWidth() : dc.getHeight();
        var r = d / 2;
        var dy = y - (dc.getHeight() / 2);
        if (dy < 0) { dy = -dy; }
        if (dy >= r) { return 0; }
        var half = Math.sqrt((r * r - dy * dy).toFloat());
        var w = (half * 2).toNumber() - s(dc, 16);   // Sicherheitsrand
        return (w > 0) ? w : 0;
    }

    // Groesste Schrift aus der Liste, die in dieser Zeile vollstaendig passt.
    // Reihenfolge: von gross nach klein.
    //
    // top ist die OBERKANTE der Zeile, lineH ihre Hoehe. Gemessen wird an der
    // Kante, die weiter von der Displaymitte entfernt liegt — bei einer Zeile
    // unterhalb der Mitte also unten, oberhalb der Mitte oben. Die Zeilenmitte
    // zu messen reicht nicht: Der Kreis laeuft innerhalb einer einzigen
    // Textzeile schon spuerbar zu, und die Schrift wuerde an ihrer engsten
    // Stelle abgeschnitten.
    function fitFont(dc as Graphics.Dc, text as Lang.String, top as Lang.Number,
                     lineH as Lang.Number, fonts as Lang.Array) as Graphics.FontType {
        var mid = dc.getHeight() / 2;
        var edge = (top + lineH / 2 >= mid) ? (top + lineH) : top;
        var avail = chordW(dc, edge);
        for (var i = 0; i < fonts.size(); i++) {
            if (dc.getTextWidthInPixels(text, fonts[i]) <= avail) { return fonts[i]; }
        }
        return fonts[fonts.size() - 1];
    }

    // Schrift fuer Hinweiszeilen. Auf grossen Displays eine Stufe groesser:
    // Schriften sind Geraetekonstanten und skalieren NICHT mit s() — auf der
    // Venu 3s waere FONT_XTINY im Verhaeltnis zum Display zu klein.
    function fontHint(dc as Graphics.Dc) as Graphics.FontType {
        return (dc.getHeight() >= 320) ? Graphics.FONT_TINY : Graphics.FONT_XTINY;
    }

    // Markenfarben (Brand Guidelines). Geraete mit kleiner Palette runden
    // selbst auf den naechsten darstellbaren Ton.
    const ORANGE = 0xFF8F1F;         // Philipp Orange
    const BLAU   = 0x4280E5;         // Max Blau
    const ROT    = 0xD63338;         // Newroz Rot

    // Hinweis auf eine laufende oder pausierte Reanimation, unten am Rand.
    // Auf Tempo- und Statistikseite gleich — er gehoert nicht zum zentrierten
    // Block, sonst wanderte dieser je nach Rea-Zustand.
    function drawResusMarker(dc as Graphics.Dc) as Void {
        if (!Cpr.active) { return; }
        var txt = Cpr.paused ? "REA pausiert" : "REA läuft";
        dc.setColor(Cpr.paused ? BLAU : ROT,
            Graphics.COLOR_TRANSPARENT);
        dc.drawText(dc.getWidth() / 2, dc.getHeight() - s(dc, 32),
            Graphics.FONT_XTINY, txt, Graphics.TEXT_JUSTIFY_CENTER);
    }
}
