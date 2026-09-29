#!/usr/bin/env bash
# App-Bau — ein App-Paket bauen und signieren, einmal je Fassung (PK-08).
#
# Aufruf:  bash tools/kette/appbau.sh android <fassung|datei> <ausgabeordner>
#          bash tools/kette/appbau.sh uhr     <fassung|datei> <ausgabeordner>
#          bash tools/kette/appbau.sh --selbstprobe
#
# Anlass: Nr. 100 (die App-Auslieferung lief von Hand), E-PK-23, -53, -60.
#
# `<fassung>` kommt aus dem Tag (`android-v0.17.0` → `0.17.0`) und muss zur
# Datei passen, sonst rot vor dem Bau (E-PK-60); `datei` nimmt die Fassung
# aus der Datei (Probelauf ohne Tag).
#
# android — Umgebung: ANDROID_HOME, APK_SPEICHER_B64, APK_SPEICHER_PASSWORT,
#   APK_SCHLUESSEL_NAME, APK_SCHLUESSEL_PASSWORT, APK_ZERTIFIKAT_SHA256.
#   Gradle baut UNSIGNIERT und sieht den Schlüssel nie; signiert wird danach
#   mit `apksigner`, außerhalb von Gradle (E-PK-63). Ergebnis:
#   nadoku-X.Y.Z.apk (Handy) und nadoku-uhr-X.Y.Z.apk (Wear OS, E-PK-58).
# uhr — Umgebung: UHR_ENTWICKLERSCHLUESSEL_B64; `monkeyc` auf dem PATH
#   (`tools/uhr-pruefstand/pruefstand.sh aufbau-uebersetzen`, CIQ_ZIELE=alle).
#   Ergebnis: nadoku-X.Y.Z.iq, das Paket für den Connect-IQ-Store.
#
# Rückgabewert 0 = gebaut und geprüft, 1 = nicht, 2 = Bedienfehler.
set -uo pipefail
WURZEL=${WURZEL:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}

# Das App-Signaturzertifikat, wie es die Dokumentation seit B1 nennt
# (E-S4-27, R65). Die Variable trägt den vollen Wert; diese beiden Enden
# fangen einen Vertipper in ihr ab.
DOKU_ANFANG=078c
DOKU_ENDE=ad64

fehler() { echo "::error::$*" >&2; }
sag() { echo "$*"; }

# Das Store-Paket ist ein 7-Zip-Archiv, kein ZIP (gemessen 29.09.2026 an
# SDK 9.2.0: `file` sagt „7-zip archive data", `unzip` findet nichts).
ist_7z() { [ "$(head -c 6 "$1" 2>/dev/null | od -An -tx1 | tr -d ' \n')" = 377abcaf271c ]; }

fassung_android() { sed -n 's/^version=\([0-9.]*\)[[:space:]]*$/\1/p' "$1" | head -1; }
fassung_uhr() { sed -n 's/.*const APP_VERSION = "\([0-9.]*\)";.*/\1/p' "$1" | head -1; }

# „Signer #1 certificate SHA-256 digest: …" — genau EIN Unterzeichner.
zertifikat_aus() {
    local z
    z=$(grep -E '^Signer #[0-9]+ certificate SHA-256 digest:' <<<"$1")
    [ "$(grep -c . <<<"$z")" = 1 ] || { echo ""; return; }
    sed -E 's/.*digest: *//' <<<"$z" | tr -d ': ' | tr 'A-F' 'a-f'
}

# Passt der gemessene Wert zur Variablen UND zu den dokumentierten Enden?
zertifikat_passt() {    # zertifikat_passt <gemessen> <erwartet>
    local ist="$1" soll
    soll=$(printf '%s' "$2" | tr -d ': ' | tr 'A-F' 'a-f')
    [ -n "$ist" ] && [ ${#ist} = 64 ] && [ "$ist" = "$soll" ] \
        && [ "${ist:0:4}" = "$DOKU_ANFANG" ] && [ "${ist: -4}" = "$DOKU_ENDE" ]
}

fassung_pruefen() {     # fassung_pruefen <soll|datei> <gelesen> <datei>
    local soll="$1" ist="$2" datei="$3"
    if [ -z "$ist" ]; then fehler "Die Fassung in $datei ist nicht lesbar."; return 1; fi
    if [ "$soll" != datei ] && [ "$soll" != "$ist" ]; then
        fehler "Der Tag sagt $soll, $datei sagt $ist — gebaut wird nicht (E-PK-60)."
        return 1
    fi
    echo "$ist"
}

android_bauen() {
    local soll="$1" aus="$2" f bt tmp m name roh ist erster=""
    f=$(fassung_pruefen "$soll" "$(fassung_android "$WURZEL/android/version.properties")" \
        android/version.properties) || return 1
    for v in ANDROID_HOME APK_SPEICHER_B64 APK_SPEICHER_PASSWORT APK_SCHLUESSEL_NAME \
             APK_SCHLUESSEL_PASSWORT APK_ZERTIFIKAT_SHA256; do
        [ -n "${!v:-}" ] || { fehler "$v fehlt — nicht gebaut."; return 2; }
    done
    # Liegt eine signatur.properties da, signierte Gradle selbst — und der
    # Schlüssel stünde fremdem Build-Code offen, gegen den E-PK-63 gebaut ist.
    if [ -e "$WURZEL/android/signatur.properties" ]; then
        fehler "android/signatur.properties liegt da; die Kette signiert nur außerhalb von Gradle."
        return 1
    fi
    sag "Android $f — Release unsigniert bauen"
    (cd "$WURZEL/android" && ./gradlew --no-daemon :handy:assembleRelease :uhr:assembleRelease) \
        || { fehler "Gradle-Bau gescheitert."; return 1; }
    bt=$(ls -d "$ANDROID_HOME"/build-tools/* 2>/dev/null | sort -V | tail -1)
    [ -x "$bt/apksigner" ] || { fehler "apksigner fehlt unter $ANDROID_HOME/build-tools."; return 1; }
    mkdir -p "$aus"
    tmp=$(mktemp -d); chmod 700 "$tmp"
    trap 'rm -rf "$tmp"' RETURN
    printf '%s' "$APK_SPEICHER_B64" | base64 -d > "$tmp/s.jks" \
        || { fehler "APK_SPEICHER_B64 ist kein Base64."; return 1; }
    for m in handy uhr; do
        roh="$WURZEL/android/$m/build/outputs/apk/release/$m-release-unsigned.apk"
        [ -f "$roh" ] || { fehler "$roh fehlt nach dem Bau."; return 1; }
        name=$([ "$m" = handy ] && echo "nadoku-$f.apk" || echo "nadoku-uhr-$f.apk")
        "$bt/zipalign" -P 16 -f 4 "$roh" "$tmp/$m.apk" \
            || { fehler "zipalign ($m) gescheitert."; return 1; }
        "$bt/apksigner" sign --ks "$tmp/s.jks" --ks-key-alias "$APK_SCHLUESSEL_NAME" \
            --ks-pass env:APK_SPEICHER_PASSWORT --key-pass env:APK_SCHLUESSEL_PASSWORT \
            --v4-signing-enabled false --out "$aus/$name" "$tmp/$m.apk" \
            || { fehler "apksigner sign ($m) gescheitert."; return 1; }
        ist=$(zertifikat_aus "$("$bt/apksigner" verify --print-certs "$aus/$name" 2>&1)")
        if ! zertifikat_passt "$ist" "$APK_ZERTIFIKAT_SHA256"; then
            fehler "$name trägt Zertifikat ${ist:-— nicht lesbar —}; erwartet ist APK_ZERTIFIKAT_SHA256 mit den Enden $DOKU_ANFANG…$DOKU_ENDE. Das Paket wäre eine andere App."
            rm -f "$aus/$name"; return 1
        fi
        [ -z "$erster" ] && erster="$ist"
        [ "$ist" = "$erster" ] || { fehler "Handy und Uhr tragen verschiedene Zertifikate — der Data Layer stellte nichts zu (E-S4-01)."; return 1; }
        local badge vn pk
        badge=$("$bt/aapt2" dump badging "$aus/$name" 2>/dev/null | head -1)
        vn=$(sed -n "s/.*versionName='\([^']*\)'.*/\1/p" <<<"$badge")
        pk=$(sed -n "s/.*package: name='\([^']*\)'.*/\1/p" <<<"$badge")
        if [ "$vn" != "$f" ] || [ "$pk" != "org.genem.nadoku" ]; then
            fehler "$name meldet Paket '$pk', Fassung '$vn' — erwartet org.genem.nadoku, $f."
            return 1
        fi
        sag "  $name  $(sha256sum "$aus/$name" | cut -d' ' -f1)  Zertifikat $ist"
    done
    sag "Android $f gebaut, signiert, geprüft."
}

uhr_bauen() {
    local soll="$1" aus="$2" f tmp
    f=$(fassung_pruefen "$soll" "$(fassung_uhr "$WURZEL/watch/source/Const.mc")" \
        watch/source/Const.mc) || return 1
    [ -n "${UHR_ENTWICKLERSCHLUESSEL_B64:-}" ] || { fehler "UHR_ENTWICKLERSCHLUESSEL_B64 fehlt — nicht gebaut."; return 2; }
    command -v monkeyc >/dev/null || { fehler "monkeyc fehlt auf dem PATH."; return 1; }
    mkdir -p "$aus"
    tmp=$(mktemp -d); chmod 700 "$tmp"
    trap 'rm -rf "$tmp"' RETURN
    printf '%s' "$UHR_ENTWICKLERSCHLUESSEL_B64" | base64 -d > "$tmp/k.der" \
        || { fehler "UHR_ENTWICKLERSCHLUESSEL_B64 ist kein Base64."; return 1; }
    sag "Uhr $f — Store-Paket bauen"
    # monkeyc skaliert das Symbol über java.awt und braucht dafür headless.
    JAVA_TOOL_OPTIONS="-Djava.awt.headless=true" \
        monkeyc -e -r -f "$WURZEL/watch/monkey.jungle" -o "$aus/nadoku-$f.iq" -y "$tmp/k.der" \
        || { fehler "monkeyc -e gescheitert."; return 1; }
    ist_7z "$aus/nadoku-$f.iq" || { fehler "nadoku-$f.iq ist kein Connect-IQ-Paket (keine 7z-Kennung)."; return 1; }
    sag "  nadoku-$f.iq  $(sha256sum "$aus/nadoku-$f.iq" | cut -d' ' -f1)"
    sag "Uhr $f gebaut und signiert."
}

selbstprobe() {
    local ok=0 fehl=0 t
    pruefe() { if eval "$2"; then ok=$((ok+1)); echo "  [ok ] $1"; else fehl=$((fehl+1)); echo "  [FEHL] $1"; fi; }
    echo "Selbstprobe des App-Baus — ohne SDK und ohne Schlüssel"
    echo
    t=$(mktemp -d)
    printf '# Kopf\n#   0.14.1 alt\nversion=0.17.0\n' > "$t/v.properties"
    printf '    const APP_VERSION = "3.1.0";\n' > "$t/Const.mc"
    pruefe "Fassung aus version.properties (Kommentare übergangen)" '[ "$(fassung_android "$t/v.properties")" = 0.17.0 ]'
    pruefe "Fassung aus Const.mc" '[ "$(fassung_uhr "$t/Const.mc")" = 3.1.0 ]'
    pruefe "Tag gleich Datei → Fassung" '[ "$(fassung_pruefen 0.17.0 0.17.0 x 2>/dev/null)" = 0.17.0 ]'
    pruefe "Tag ungleich Datei → rot (E-PK-60)" '! fassung_pruefen 0.18.0 0.17.0 x 2>/dev/null >/dev/null'
    pruefe "Probelauf „datei“ → Fassung der Datei" '[ "$(fassung_pruefen datei 0.17.0 x)" = 0.17.0 ]'
    pruefe "Unlesbare Fassung → rot" '! fassung_pruefen datei "" x 2>/dev/null >/dev/null'
    local gut="078c$(printf 'a%.0s' {1..56})ad64"
    local aus1="Signer #1 certificate DN: CN=x
Signer #1 certificate SHA-256 digest: $gut
Signer #1 certificate SHA-1 digest: 00"
    local aus2="$aus1
Signer #2 certificate SHA-256 digest: $gut"
    pruefe "Zertifikat aus apksigner gelesen" '[ "$(zertifikat_aus "$aus1")" = "$gut" ]'
    pruefe "Zwei Unterzeichner → nicht lesbar" '[ -z "$(zertifikat_aus "$aus2")" ]'
    pruefe "Gleich der Variablen, Enden passen → passt" 'zertifikat_passt "$gut" "$gut"'
    pruefe "Variable mit Doppelpunkten und Großbuchstaben → passt" \
        'zertifikat_passt "$gut" "$(sed -E "s/(..)/\1:/g; s/:$//" <<<"$gut" | tr a-f A-F)"'
    local fremd="1111$(printf 'b%.0s' {1..56})2222"
    pruefe "Anderes Zertifikat, Variable sagt dasselbe → passt NICHT (Enden)" '! zertifikat_passt "$fremd" "$fremd"'
    pruefe "Richtige Enden, Variable anders → passt NICHT" '! zertifikat_passt "$gut" "$fremd"'
    pruefe "Leer → passt nicht" '! zertifikat_passt "" "$gut"'
    printf '7z\274\257\047\034rest' > "$t/p.iq"; printf 'PK\003\004rest' > "$t/z.iq"
    pruefe "7z-Kennung erkannt" 'ist_7z "$t/p.iq"'
    pruefe "ZIP ist kein Connect-IQ-Paket" '! ist_7z "$t/z.iq"'
    pruefe "Fehlende Datei ist keines" '! ist_7z "$t/fehlt.iq"'
    mkdir -p "$t/android" && touch "$t/android/signatur.properties"
    printf 'version=0.17.0\n' > "$t/android/version.properties"
    pruefe "signatur.properties liegt da → rot, ohne zu bauen, mit diesem Grund" \
        'grep -q "signatur.properties liegt da" <<<"$( ( WURZEL="$t"; ANDROID_HOME=x APK_SPEICHER_B64=x APK_SPEICHER_PASSWORT=x APK_SCHLUESSEL_NAME=x APK_SCHLUESSEL_PASSWORT=x APK_ZERTIFIKAT_SHA256=x android_bauen 0.17.0 "$t/aus" ) 2>&1 >/dev/null )"'
    pruefe "Fehlender Schlüssel → Bedienfehler 2" \
        '( WURZEL="$t"; unset APK_SPEICHER_B64; ANDROID_HOME=x android_bauen 0.17.0 "$t/aus" ) 2>/dev/null >/dev/null; [ $? = 2 ]'
    rm -rf "$t"
    echo
    echo "$ok erfüllt, $fehl nicht erfüllt"
    [ "$fehl" = 0 ]
}

case "${1:-}" in
    --selbstprobe) selbstprobe ;;
    android) [ $# = 3 ] || { echo "Aufruf: $0 android <fassung|datei> <ausgabe>" >&2; exit 2; }
             android_bauen "$2" "$3" ;;
    uhr)     [ $# = 3 ] || { echo "Aufruf: $0 uhr <fassung|datei> <ausgabe>" >&2; exit 2; }
             uhr_bauen "$2" "$3" ;;
    *) sed -n '2,6p' "$0" >&2; exit 2 ;;
esac
