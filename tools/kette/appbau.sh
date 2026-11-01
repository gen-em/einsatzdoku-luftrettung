#!/usr/bin/env bash
# App-Bau — ein App-Paket bauen und signieren, einmal je Fassung.
#
# Aufruf:  bash tools/kette/appbau.sh android-bauen     <fassung|datei> <ausgabeordner>
#          bash tools/kette/appbau.sh android-signieren <fassung|datei> <eingang> <ausgabeordner>
#          bash tools/kette/appbau.sh uhr               <fassung|datei> <ausgabeordner>
#          bash tools/kette/appbau.sh --selbstprobe
#
# Anlass: Nr. 100 (die App-Auslieferung lief von Hand), E-PK-23, -53, -60.
#
# `<fassung>` kommt aus dem Tag (`android-v0.17.0` → `0.17.0`) und muss zur
# Datei passen, sonst rot vor dem Bau; `datei` nimmt die Fassung
# aus der Datei (Probelauf ohne Tag).
#
# android-bauen — Umgebung: ANDROID_HOME, und KEIN `APK_*`-Wert. Gradle baut
#   UNSIGNIERT, und zwar mit einer Umgebung aus einer Positivliste
#   (`env -i`, GRADLE_UMGEBUNG unten). Ergebnis: handy- und
#   uhr-release-unsigned.apk und `pruefsummen.txt` im Ausgabeordner.
# android-signieren — Umgebung: ANDROID_HOME, APK_SPEICHER_B64,
#   APK_SPEICHER_PASSWORT, APK_SCHLUESSEL_NAME, APK_SCHLUESSEL_PASSWORT,
#   APK_ZERTIFIKAT_SHA256. Signiert die unsignierten APKs aus <eingang> mit
#   `apksigner` und prüft das Zertifikat. Ergebnis: nadoku-X.Y.Z.apk (Handy)
#   und nadoku-uhr-X.Y.Z.apk (Wear OS). Gradle läuft hier NICHT.
#
# WARUM ZWEI UNTERBEFEHLE UND IN DER KETTE ZWEI JOBS. „Gradle sieht den
#   Schlüssel nie" reicht nicht, wenn es nur für die DATEI gilt: Die Umgebung
#   erbt Gradle vom Schritt, und stünden dort der Keystore als Base64 und
#   beide Passwörter, genügte `System.getenv()` jedem Plugin und jeder
#   Abhängigkeit zur Bauzeit. Ein eigener Schritt ohne die Werte schlösse
#   `getenv`, nicht aber einen Hintergrundprozess, den ein
#   bösartiger Bau zurücklässt — auf einem GitHub-Läufer lebt er in den
#   nächsten Schritt hinein und liest dort `/proc/<pid>/environ` desselben
#   Benutzers. Deshalb baut ein Job OHNE Geheimnisse und OHNE Umgebung, und
#   ein zweiter Job auf einem frischen Läufer signiert, ohne Gradle. Die
#   Positivliste beim Gradle-Aufruf ist der zweite Riegel für den Fall, dass
#   jemand dem Bau-Job doch einmal ein Geheimnis gibt; der erste ist, dass
#   `android-bauen` dann gar nicht erst baut.
# uhr — Umgebung: UHR_ENTWICKLERSCHLUESSEL_B64; `monkeyc` auf dem PATH
#   (`tools/uhr-pruefstand/pruefstand.sh aufbau-uebersetzen`, CIQ_ZIELE=alle).
#   Ergebnis: nadoku-X.Y.Z.iq, das Paket für den Connect-IQ-Store.
#
# Rückgabewert 0 = gebaut und geprüft, 1 = nicht, 2 = Bedienfehler.
set -uo pipefail
WURZEL=${WURZEL:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}

# Das App-Signaturzertifikat, wie es die Dokumentation nennt.
# Die Variable trägt den vollen Wert; diese beiden Enden
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

# Jede Zeile „… Signer … certificate SHA-256 digest: …" in den drei Formen,
# die apksigner kennt — „Signer #1", „Signer (minSdkVersion=…)" und seit den
# Build-Tools 37 „V3.0 Signer:": genau EIN Wert, sonst
# leer. Derselbe Wert zweimal ist ein Unterzeichner.
zertifikat_aus() {
    local z
    z=$(grep -E '^(V[0-9]+(\.[0-9]+)? )?Signer( #[0-9]+| \([^)]*\))?:? certificate SHA-256 digest:' <<<"$1" \
        | sed -E 's/.*digest: *//' | tr -d ': ' | tr 'A-F' 'a-f' | sort -u)
    if [ -n "$z" ] && [ "$(grep -c . <<<"$z")" = 1 ]; then echo "$z"; else echo ""; fi
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

# Was Gradle aus der Umgebung des Schritts sehen darf — und nichts sonst.
# PATH, HOME und die zwei Werkzeugwurzeln braucht der Bau; die Sprache, damit
# javac Umlaute in Quelltexten richtig liest; TMPDIR, wo es gesetzt ist.
# Ein Geheimnis gehört nie hierher, auch nicht „nur zum Prüfen".
GRADLE_UMGEBUNG=(PATH HOME USER JAVA_HOME ANDROID_HOME ANDROID_SDK_ROOT
                 GRADLE_USER_HOME LANG LC_ALL TMPDIR)

# Die Umgebung für `env -i` aus der Positivliste — nur gesetzte Werte.
gradle_umgebung() {
    local v
    for v in "${GRADLE_UMGEBUNG[@]}"; do
        [ -n "${!v+x}" ] && printf '%s=%s\n' "$v" "${!v}"
    done
}

# Liegt ein `APK_*`-Wert in der Umgebung? Dann baut `android-bauen` nicht.
apk_in_umgebung() { compgen -e | grep -q '^APK_'; }

android_bauen() {
    local soll="$1" aus="$2" f m roh
    f=$(fassung_pruefen "$soll" "$(fassung_android "$WURZEL/android/version.properties")" \
        android/version.properties) || return 1
    [ -n "${ANDROID_HOME:-}" ] || { fehler "ANDROID_HOME fehlt — nicht gebaut."; return 2; }
    # DER ERSTE RIEGEL: Der Bau-Job hat keine Geheimnisse. Steht doch ein
    # `APK_*` da, hat jemand ihm eines gegeben — gebaut wird dann nicht, und
    # nicht erst die Positivliste darunter soll es retten.
    if apk_in_umgebung; then
        fehler "In der Umgebung des Bauschritts steht ein APK_-Wert ($(compgen -e | grep '^APK_' | tr '\n' ' ')) — der Bau läuft ohne Schlüssel, im eigenen Job (E-R17-32). Nicht gebaut."
        return 1
    fi
    # Liegt eine signatur.properties da, signierte Gradle selbst — und der
    # Schlüssel stünde fremdem Build-Code offen, gegen den das Signieren außerhalb von Gradle gebaut ist.
    if [ -e "$WURZEL/android/signatur.properties" ]; then
        fehler "android/signatur.properties liegt da; die Kette signiert nur außerhalb von Gradle."
        return 1
    fi
    sag "Android $f — Release unsigniert bauen (Umgebung: ${GRADLE_UMGEBUNG[*]})"
    # DER ZWEITE RIEGEL: `env -i` mit der Positivliste. Mapfile statt
    # Wortzerlegung, damit ein Wert mit Leerzeichen ein Wert bleibt.
    local -a umg
    mapfile -t umg < <(gradle_umgebung)
    (cd "$WURZEL/android" && env -i "${umg[@]}" ./gradlew --no-daemon :handy:assembleRelease :uhr:assembleRelease) \
        || { fehler "Gradle-Bau gescheitert."; return 1; }
    mkdir -p "$aus"
    : > "$aus/pruefsummen.txt"
    for m in handy uhr; do
        roh="$WURZEL/android/$m/build/outputs/apk/release/$m-release-unsigned.apk"
        [ -f "$roh" ] || { fehler "$roh fehlt nach dem Bau."; return 1; }
        cp "$roh" "$aus/" || { fehler "$m-release-unsigned.apk ließ sich nicht kopieren."; return 1; }
        (cd "$aus" && sha256sum "$m-release-unsigned.apk" >> pruefsummen.txt) \
            || { fehler "Prüfsumme von $m-release-unsigned.apk gescheitert."; return 1; }
    done
    sag "Android $f unsigniert gebaut:"
    sed 's/^/  /' "$aus/pruefsummen.txt"
}

android_signieren() {
    local soll="$1" ein="$2" aus="$3" f bt tmp m name roh pruef ist erster=""
    f=$(fassung_pruefen "$soll" "$(fassung_android "$WURZEL/android/version.properties")" \
        android/version.properties) || return 1
    for v in ANDROID_HOME APK_SPEICHER_B64 APK_SPEICHER_PASSWORT APK_SCHLUESSEL_NAME \
             APK_SCHLUESSEL_PASSWORT APK_ZERTIFIKAT_SHA256; do
        [ -n "${!v:-}" ] || { fehler "$v fehlt — nicht signiert."; return 2; }
    done
    bt=$(ls -d "$ANDROID_HOME"/build-tools/* 2>/dev/null | sort -V | tail -1)
    [ -x "$bt/apksigner" ] || { fehler "apksigner fehlt unter $ANDROID_HOME/build-tools."; return 1; }
    sag "Android $f — signieren mit Build-Tools $(basename "$bt")"
    mkdir -p "$aus"
    tmp=$(mktemp -d); chmod 700 "$tmp"
    trap 'rm -rf "$tmp"' RETURN
    printf '%s' "$APK_SPEICHER_B64" | base64 -d > "$tmp/s.jks" \
        || { fehler "APK_SPEICHER_B64 ist kein Base64."; return 1; }
    for m in handy uhr; do
        roh="$ein/$m-release-unsigned.apk"
        [ -f "$roh" ] || { fehler "$roh fehlt — der Bau-Job hat es nicht übergeben."; return 1; }
        name=$([ "$m" = handy ] && echo "nadoku-$f.apk" || echo "nadoku-uhr-$f.apk")
        "$bt/zipalign" -P 16 -f 4 "$roh" "$tmp/$m.apk" \
            || { fehler "zipalign ($m) gescheitert."; return 1; }
        "$bt/apksigner" sign --ks "$tmp/s.jks" --ks-key-alias "$APK_SCHLUESSEL_NAME" \
            --ks-pass env:APK_SPEICHER_PASSWORT --key-pass env:APK_SCHLUESSEL_PASSWORT \
            --v4-signing-enabled false --out "$aus/$name" "$tmp/$m.apk" \
            || { fehler "apksigner sign ($m) gescheitert."; return 1; }
        # Die Ausgabe ist öffentlich (Zertifikat und Prüfsummen stehen in jeder
        # APK) und kommt bei jedem Scheitern ins Protokoll — ohne sie war
        # ein gescheiterter Lauf nicht zu deuten.
        pruef=$("$bt/apksigner" verify --print-certs "$aus/$name" 2>&1) || {
            fehler "apksigner verify ($m) gescheitert:"; sed 's/^/    /' <<<"$pruef" >&2
            rm -f "$aus/$name"; return 1; }
        ist=$(zertifikat_aus "$pruef")
        if ! zertifikat_passt "$ist" "$APK_ZERTIFIKAT_SHA256"; then
            fehler "$name trägt Zertifikat ${ist:-— nicht lesbar —}; erwartet ist APK_ZERTIFIKAT_SHA256 mit den Enden $DOKU_ANFANG…$DOKU_ENDE. Das Paket wäre eine andere App."
            echo "  apksigner $(basename "$bt") verify --print-certs:" >&2
            sed 's/^/    /' <<<"$pruef" >&2
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
    sag "Android $f signiert und geprüft."
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
    local aus3="Signer (minSdkVersion=24, maxSdkVersion=32) certificate SHA-256 digest: $gut
Signer (minSdkVersion=33, maxSdkVersion=2147483647) certificate SHA-256 digest: $gut"
    local aus4="$aus1
Signer #2 certificate SHA-256 digest: 1111$(printf 'c%.0s' {1..56})2222"
    pruefe "Zertifikat aus apksigner gelesen" '[ "$(zertifikat_aus "$aus1")" = "$gut" ]'
    pruefe "Derselbe Wert zweimal → ein Unterzeichner (F-PK-56)" '[ "$(zertifikat_aus "$aus2")" = "$gut" ]'
    pruefe "Nummerierung nach minSdkVersion → gelesen" '[ "$(zertifikat_aus "$aus3")" = "$gut" ]'
    local aus5="V3.0 Signer: certificate DN: CN=x
V3.0 Signer: certificate SHA-256 digest: $gut
V3.0 Signer: certificate SHA-1 digest: 00"
    pruefe "Form der Build-Tools 37, V3.0 Signer → gelesen (Lauf 105)" '[ "$(zertifikat_aus "$aus5")" = "$gut" ]'
    pruefe "Quellstempel zählt nicht als Unterzeichner" '[ "$(zertifikat_aus "$aus1
Source Stamp Signer certificate SHA-256 digest: 1111$(printf "d%.0s" {1..56})2222")" = "$gut" ]'
    pruefe "Zwei verschiedene Unterzeichner → nicht lesbar" '[ -z "$(zertifikat_aus "$aus4")" ]'
    pruefe "Keine Zeile → nicht lesbar" '[ -z "$(zertifikat_aus "DOES NOT VERIFY")" ]'
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
    # Die Fälle darunter laufen ohne fremde `APK_*`-Werte — auch dann, wenn
    # die Selbstprobe in einem Schritt steht, der welche hat.
    local ohne_apk='unset $(compgen -e | grep "^APK_") 2>/dev/null'
    mkdir -p "$t/android" && touch "$t/android/signatur.properties"
    printf 'version=0.17.0\n' > "$t/android/version.properties"
    pruefe "signatur.properties liegt da → rot, ohne zu bauen, mit diesem Grund" \
        'grep -q "signatur.properties liegt da" <<<"$( ( eval "$ohne_apk"; WURZEL="$t"; ANDROID_HOME=x android_bauen 0.17.0 "$t/aus" ) 2>&1 >/dev/null )"'
    pruefe "Bau ohne ANDROID_HOME → Bedienfehler 2" \
        '( eval "$ohne_apk"; WURZEL="$t"; unset ANDROID_HOME; android_bauen 0.17.0 "$t/aus" ) 2>/dev/null >/dev/null; [ $? = 2 ]'
    pruefe "Signieren ohne Schlüssel → Bedienfehler 2" \
        '( eval "$ohne_apk"; WURZEL="$t"; ANDROID_HOME=x android_signieren 0.17.0 "$t/ein" "$t/aus" ) 2>/dev/null >/dev/null; [ $? = 2 ]'

    # DIE UMGEBUNG DES GRADLE-AUFRUFS. Ein Ersatz-`gradlew`
    # schreibt auf, was er sieht, und legt die zwei unsignierten APKs hin —
    # gemessen wird der GANZE Weg durch `android_bauen()`, nicht die Liste.
    local g="$t/g"
    mkdir -p "$g/android"
    printf 'version=0.17.0\n' > "$g/android/version.properties"
    cat > "$g/android/gradlew" <<'GRADLEW'
#!/usr/bin/env bash
env > ../umgebung.txt
for m in handy uhr; do
    mkdir -p "$m/build/outputs/apk/release"
    printf 'apk-%s' "$m" > "$m/build/outputs/apk/release/$m-release-unsigned.apk"
done
GRADLEW
    chmod +x "$g/android/gradlew"
    pruefe "APK_-Wert in der Umgebung → rot, OHNE Gradle zu starten (erster Riegel)" \
        '( eval "$ohne_apk"; WURZEL="$g"; ANDROID_HOME=x; export APK_SPEICHER_PASSWORT=geheim; android_bauen 0.17.0 "$g/aus" ) >/dev/null 2>&1; [ $? = 1 ] && [ ! -e "$g/umgebung.txt" ]'
    ( eval "$ohne_apk"; WURZEL="$g"; export ANDROID_HOME=/x JOBS_TOKEN=geheim FTP_PASSWORD=geheim GH_TOKEN=geheim
      android_bauen 0.17.0 "$g/aus" ) >/dev/null 2>&1
    pruefe "Gradle lief und sah PATH, HOME und ANDROID_HOME" \
        'grep -q "^PATH=" "$g/umgebung.txt" && grep -q "^HOME=" "$g/umgebung.txt" && grep -qx "ANDROID_HOME=/x" "$g/umgebung.txt"'
    pruefe "DIE UMGEBUNG DES GRADLE-AUFRUFS ENTHÄLT KEIN APK_ (F-R17-03)" \
        '[ -s "$g/umgebung.txt" ] && ! grep -q "^APK_" "$g/umgebung.txt"'
    pruefe "…und keinen Wert außerhalb der Positivliste (JOBS_TOKEN, FTP_PASSWORD, GH_TOKEN)" \
        '[ -s "$g/umgebung.txt" ] && ! grep -qE "^(JOBS_TOKEN|FTP_PASSWORD|GH_TOKEN)=" "$g/umgebung.txt"'
    pruefe "Jede Zeile der Gradle-Umgebung steht in der Positivliste" \
        '[ -s "$g/umgebung.txt" ] && ! cut -d= -f1 "$g/umgebung.txt" | grep -vxE "$(IFS="|"; echo "${GRADLE_UMGEBUNG[*]}|PWD|SHLVL|_|OLDPWD")" | grep -q .'
    pruefe "Beide unsignierten APKs liegen im Ausgabeordner, mit Prüfsummen" \
        '[ -f "$g/aus/handy-release-unsigned.apk" ] && [ -f "$g/aus/uhr-release-unsigned.apk" ] && [ "$(wc -l < "$g/aus/pruefsummen.txt")" = 2 ] && (cd "$g/aus" && sha256sum -c --quiet pruefsummen.txt)'
    rm -rf "$t"
    echo
    echo "$ok erfüllt, $fehl nicht erfüllt"
    [ "$fehl" = 0 ]
}

case "${1:-}" in
    --selbstprobe) selbstprobe ;;
    android-bauen)
             [ $# = 3 ] || { echo "Aufruf: $0 android-bauen <fassung|datei> <ausgabe>" >&2; exit 2; }
             android_bauen "$2" "$3" ;;
    android-signieren)
             [ $# = 4 ] || { echo "Aufruf: $0 android-signieren <fassung|datei> <eingang> <ausgabe>" >&2; exit 2; }
             android_signieren "$2" "$3" "$4" ;;
    uhr)     [ $# = 3 ] || { echo "Aufruf: $0 uhr <fassung|datei> <ausgabe>" >&2; exit 2; }
             uhr_bauen "$2" "$3" ;;
    *) sed -n '2,6p' "$0" >&2; exit 2 ;;
esac
