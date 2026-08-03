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
    // Bewusst OHNE "dc has :getFontDescent": Ein has-Test verengt den Typ von
    // dc auf genau dieses eine Symbol, danach kennt der Typpruefer
    // getFontHeight nicht mehr. Stattdessen faengt die Plausibilitaetspruefung
    // unbrauchbare Rueckgaben ab.
    function numH(dc as Graphics.Dc, font as Graphics.FontType) as Lang.Number {
        var h = dc.getFontHeight(font);
        var d = dc.getFontDescent(font);
        if (d <= 0 || d >= h) { return (h * 78) / 100; }   // Naeherung
        return h - d;
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
        dc.setColor(Cpr.paused ? Graphics.COLOR_YELLOW : ROT,
            Graphics.COLOR_TRANSPARENT);
        dc.drawText(dc.getWidth() / 2, dc.getHeight() - s(dc, 32),
            Graphics.FONT_XTINY, txt, Graphics.TEXT_JUSTIFY_CENTER);
    }
}
