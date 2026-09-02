# Verkleinern und Verschleiern sind AUS (isMinifyEnabled = false).
#
# Warum: Die App hat rund zwei Dutzend Klassen und keine nennenswerte Groesse;
# was R8 einsparen wuerde, faellt neben den Compose-Bibliotheken nicht ins
# Gewicht. Was es kostet, ist ein Stapelauszug, den niemand mehr lesen kann --
# und der Gerätetest (E-R45-7) lebt davon, dass ein Fehlerbericht vom S24
# verwertbar ist.
#
# Die Datei steht trotzdem hier: Wird das spaeter umgestellt, ist der Ort
# schon da, an dem die Ausnahmen hingehoeren.
