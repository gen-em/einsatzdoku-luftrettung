#!/bin/sh
# Zweite Fassung der Gegenstellen: die ECHTEN Server statt der Nachbauten.
#
# `gegenstellen.py` stellt FTP, FTPS und SFTP in Python hin. Das ist portabel
# und braucht keine Rechte — aber es sind Nachbauten. Was auf einem Webspace
# tatsaechlich laeuft, ist vsftpd (oder ProFTPD) und OpenSSH, und die
# unterscheiden sich in genau den Kleinigkeiten, an denen ein FTP-Adapter
# scheitert: welche Listenbefehle es gibt, wie die Antworttexte lauten, ob ein
# chroot im Weg steht.
#
# Dieses Skript braucht root und Debian/Ubuntu:
#     apt-get install -y vsftpd openssh-server
#     sh tools/versandprobe/echte_gegenstellen.sh /tmp/versandprobe-echt
#
# Danach:  php tools/versandprobe/probe.php /tmp/versandprobe-echt --echt
#
# Beenden: sh tools/versandprobe/echte_gegenstellen.sh --stop
set -e

NUTZER=edprobe
PASSWORT='geheim-probe-2026'
PORT_FTP=2131
PORT_FTPS=2132
PORT_SFTP=2232
PASV_VON=31000
PASV_BIS=31050

# `--wechsel <wurzel>` startet OpenSSH mit dem ZWEITEN Hostschluessel neu.
# Das ist der Fall, den der Fingerabdruck-Riegel abfangen soll: dieselbe
# Adresse, ein anderer Server. Mit einem erfundenen Fingerabdruck laesst sich
# das nur halb pruefen — hier ist es ein echter zweiter Schluessel.
if [ "$1" = "--wechsel" ]; then
    W="${2:?Aufruf: echte_gegenstellen.sh --wechsel <wurzelverzeichnis>}"
    [ -f /run/sshd-probe.pid ] && kill "$(cat /run/sshd-probe.pid)" 2>/dev/null || true
    sed -i "s|^HostKey .*|HostKey $W/ssh/hostkey_zwei|" /etc/ssh/sshd-probe.conf
    /usr/sbin/sshd -f /etc/ssh/sshd-probe.conf -E "$W/sshd-auth.log"
    echo "GEWECHSELT auf $(ssh-keygen -lf "$W/ssh/hostkey_zwei.pub" | awk '{print $2}')"
    exit 0
fi
if [ "$1" = "--zurueck" ]; then
    W="${2:?Aufruf: echte_gegenstellen.sh --zurueck <wurzelverzeichnis>}"
    [ -f /run/sshd-probe.pid ] && kill "$(cat /run/sshd-probe.pid)" 2>/dev/null || true
    sed -i "s|^HostKey .*|HostKey $W/ssh/hostkey|" /etc/ssh/sshd-probe.conf
    /usr/sbin/sshd -f /etc/ssh/sshd-probe.conf -E "$W/sshd-auth.log"
    echo "ZURUECK auf $(ssh-keygen -lf "$W/ssh/hostkey.pub" | awk '{print $2}')"
    exit 0
fi
if [ "$1" = "--stop" ]; then
    [ -f /run/vsftpd-probe.pid ]     && kill "$(cat /run/vsftpd-probe.pid)"     2>/dev/null || true
    [ -f /run/vsftpd-probe-tls.pid ] && kill "$(cat /run/vsftpd-probe-tls.pid)" 2>/dev/null || true
    [ -f /run/sshd-probe.pid ]   && kill "$(cat /run/sshd-probe.pid)"   2>/dev/null || true
    rm -f /run/vsftpd-probe.pid /run/vsftpd-probe-tls.pid /run/sshd-probe.pid
    echo "GESTOPPT"
    exit 0
fi

# DAS WURZELVERZEICHNIS MUSS FUER DEN FTP-NUTZER DURCHSCHREITBAR SEIN.
# vsftpd wechselt nach der Anmeldung in das Heimverzeichnis; liegt auch nur
# ein Verzeichnis auf dem Weg dorthin auf 0700 root, meldet es
# „500 OOPS: cannot change directory" — was wie ein Anmeldefehler aussieht und
# keiner ist. Ein Pfad unter /srv ist die sichere Wahl, /tmp/... mit einem
# abgeschotteten Zwischenverzeichnis nicht.
WURZEL="${1:?Aufruf: echte_gegenstellen.sh <wurzelverzeichnis>}"
mkdir -p "$WURZEL"
WURZEL=$(cd "$WURZEL" && pwd)

# ---- Der Nutzer -----------------------------------------------------------
# Ein gewoehnlicher Systemnutzer mit Passwort. `/bin/sh` als Anmeldeschale und
# nicht `nologin`: vsftpd prueft ueber PAM gegen /etc/shells, und ein Nutzer
# mit nologin kommt dort nicht durch. Das Konto lebt nur in diesem Behaelter.
if ! id "$NUTZER" >/dev/null 2>&1; then
    useradd -m -d "$WURZEL/heim" -s /bin/sh "$NUTZER"
fi
echo "$NUTZER:$PASSWORT" | chpasswd
mkdir -p "$WURZEL/heim/ftp" "$WURZEL/heim/sftp" "$WURZEL/heim/tief/darunter"
chown -R "$NUTZER:$NUTZER" "$WURZEL/heim"

# ---- vsftpd ---------------------------------------------------------------
# `allow_writeable_chroot` ist noetig, weil das Heimverzeichnis selbst
# beschreibbar ist; ohne die Zeile verweigert vsftpd den Dienst mit einer
# Meldung, die wie ein Anmeldefehler aussieht (500 OOPS).
# `seccomp_sandbox=NO`: Die Sandkiste von vsftpd braucht Systemaufrufe, die
# in einem Behaelter oft gesperrt sind.
mkdir -p /var/run/vsftpd/empty
cat > /etc/vsftpd-probe.conf <<EOF
listen=YES
listen_ipv6=NO
listen_port=$PORT_FTP
background=NO
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
dirmessage_enable=NO
chroot_local_user=YES
allow_writeable_chroot=YES
seccomp_sandbox=NO
pasv_enable=YES
pasv_min_port=$PASV_VON
pasv_max_port=$PASV_BIS
pasv_address=127.0.0.1
port_enable=YES
connect_from_port_20=NO
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd
xferlog_enable=NO
syslog_enable=NO
EOF
# Die Ausgabe geht in eine Logdatei und NICHT in die Pipe des Aufrufers: Ein
# Hintergrunddienst, der die Standardausgabe offen haelt, laesst jedes
# `... | tail` haengen, bis das Zeitlimit zuschlaegt. Genau das ist beim
# ersten Anlauf passiert.
/usr/sbin/vsftpd /etc/vsftpd-probe.conf >>"$WURZEL/vsftpd.log" 2>&1 &
echo $! > /run/vsftpd-probe.pid

# ---- vsftpd noch einmal, diesmal mit TLS (FTPS) ---------------------------
# Selbst ausgestelltes Zertifikat ohne jede Vertrauenskette — dasselbe wie bei
# den Nachbauten, und aus demselben Grund: Kommt die Verbindung zustande, dann
# prueft `ext/ftp` das Zertifikat nicht.
[ -f "$WURZEL/zert.pem" ] || openssl req -x509 -newkey rsa:2048 -nodes \
    -keyout "$WURZEL/zert.pem" -out "$WURZEL/zert.pem" -days 2 \
    -subj '/CN=versandprobe-selbst-ausgestellt' 2>/dev/null
sed -e "s/^listen_port=.*/listen_port=$PORT_FTPS/" \
    -e "s/^pasv_min_port=.*/pasv_min_port=$((PASV_VON+100))/" \
    -e "s/^pasv_max_port=.*/pasv_max_port=$((PASV_BIS+100))/" \
    /etc/vsftpd-probe.conf > /etc/vsftpd-probe-tls.conf
# `require_ssl_reuse=NO` ist noetig: PHP nimmt fuer den Datenkanal eine NEUE
# TLS-Sitzung, und vsftpd verlangt sonst die Wiederverwendung der Sitzung des
# Steuerkanals — die Uebertragung bricht dann mit „522" ab, waehrend die
# Anmeldung geklappt hat.
#
# `ssl_tlsv1_2=YES` steht hier NICHT, obwohl es naheliegt. Gemessen an
# vsftpd 3.0.5 (Ubuntu noble): Mit dieser Zeile beendet sich vsftpd sofort mit
# Status 2 und OHNE Meldung — weder auf der Standardausgabe noch im Log. Eine
# halbe Stunde Suche, weil ein Dienst schweigend nicht startet.
cat >> /etc/vsftpd-probe-tls.conf <<EOF
ssl_enable=YES
rsa_cert_file=$WURZEL/zert.pem
rsa_private_key_file=$WURZEL/zert.pem
force_local_data_ssl=YES
force_local_logins_ssl=YES
require_ssl_reuse=NO
EOF
/usr/sbin/vsftpd /etc/vsftpd-probe-tls.conf >>"$WURZEL/vsftpd-tls.log" 2>&1 &
echo $! > /run/vsftpd-probe-tls.pid

# ---- OpenSSH --------------------------------------------------------------
# Eine EIGENE Konfiguration und ein eigener Hostschluessel — der des Systems
# bleibt unangetastet. Der zweite Schluessel (`hostkey_zwei`) ist fuer die
# Probe des Fingerabdruck-Riegels: Ein Neustart damit ist ein Server, der sich
# unter derselben Adresse mit einem ANDEREN Schluessel meldet.
mkdir -p /run/sshd "$WURZEL/ssh"
[ -f "$WURZEL/ssh/hostkey" ]      || ssh-keygen -q -t rsa -b 2048 -N '' -f "$WURZEL/ssh/hostkey"
[ -f "$WURZEL/ssh/hostkey_zwei" ] || ssh-keygen -q -t rsa -b 2048 -N '' -f "$WURZEL/ssh/hostkey_zwei"
[ -f "$WURZEL/ssh/nutzerschluessel" ] || ssh-keygen -q -t rsa -b 2048 -N '' -f "$WURZEL/ssh/nutzerschluessel"
[ -f "$WURZEL/ssh/nutzerschluessel-mit-passwort" ] || {
    cp "$WURZEL/ssh/nutzerschluessel" "$WURZEL/ssh/nutzerschluessel-mit-passwort"
    ssh-keygen -q -p -N 'passphrase-der-probe' -f "$WURZEL/ssh/nutzerschluessel-mit-passwort"
}
mkdir -p "$WURZEL/heim/.ssh"
cat "$WURZEL/ssh/nutzerschluessel.pub" > "$WURZEL/heim/.ssh/authorized_keys"
chown -R "$NUTZER:$NUTZER" "$WURZEL/heim/.ssh"
chmod 700 "$WURZEL/heim/.ssh"; chmod 600 "$WURZEL/heim/.ssh/authorized_keys"

SCHLUESSEL="${SCHLUESSEL:-$WURZEL/ssh/hostkey}"
cat > /etc/ssh/sshd-probe.conf <<EOF
Port $PORT_SFTP
ListenAddress 127.0.0.1
HostKey $SCHLUESSEL
PidFile /run/sshd-probe.pid
UsePAM no
PasswordAuthentication yes
PubkeyAuthentication yes
PermitRootLogin no
AllowUsers $NUTZER
StrictModes no
Subsystem sftp /usr/lib/openssh/sftp-server
LogLevel INFO
EOF
# `-E` schreibt das Protokoll in eine DATEI statt ins Syslog. Es ist die
# Messgroesse fuer den Fingerabdruck-Riegel: Bei einem unerwarteten
# Hostschluessel darf die Gegenstelle KEINEN Anmeldeversuch sehen.
rm -f "$WURZEL/sshd-auth.log"
/usr/sbin/sshd -f /etc/ssh/sshd-probe.conf -E "$WURZEL/sshd-auth.log"

# ---- Fingerabdruck ausgeben ----------------------------------------------
# Dieselbe Schreibweise wie in der Anwendung: SHA256: plus base64 ohne
# Fuellzeichen. Hier rechnet ssh-keygen sie aus — eine unabhaengige Quelle,
# nicht dieselbe Formel noch einmal.
ABDRUCK=$(ssh-keygen -lf "${SCHLUESSEL}.pub" | awk '{print $2}')
echo "$ABDRUCK" > "$WURZEL/fingerabdruck.txt"
ssh-keygen -lf "$WURZEL/ssh/hostkey_zwei.pub" | awk '{print $2}' > "$WURZEL/fingerabdruck_zwei.txt"
cp "$WURZEL/ssh/nutzerschluessel" "$WURZEL/nutzerschluessel"
cp "$WURZEL/ssh/nutzerschluessel-mit-passwort" "$WURZEL/nutzerschluessel-mit-passwort"
chmod 600 "$WURZEL/nutzerschluessel" "$WURZEL/nutzerschluessel-mit-passwort"

# Warten, bis beide horchen — ein "BEREIT" vor dem Horchen macht aus einem
# Startproblem einen Verbindungsfehler in der Probe.
# Die Pruefung laeuft ueber python3 und NICHT ueber `/dev/tcp/...`. Das ist
# eine Bash-Eigenheit; dieses Skript hat `#!/bin/sh`, und /bin/sh ist auf
# Debian und Ubuntu dash. Dort schlaegt jeder Versuch fehl, und die Schleife
# meldet „Ports horchen nicht", waehrend alle drei laengst horchen — genau so
# gemessen beim ersten Anlauf.
python3 - "$PORT_FTP" "$PORT_FTPS" "$PORT_SFTP" <<'PY' || {
import socket, sys, time
ports = [int(p) for p in sys.argv[1:]]
frist = time.time() + 15
for p in ports:
    while True:
        with socket.socket() as s:
            s.settimeout(0.3)
            if s.connect_ex(('127.0.0.1', p)) == 0:
                break
        if time.time() > frist:
            print('Port %d horcht nicht.' % p, file=sys.stderr)
            sys.exit(1)
        time.sleep(0.1)
PY
    echo "FEHLER: Ports horchen nicht." >&2; exit 1
}

echo "FINGERABDRUCK $ABDRUCK"
echo "NUTZER $NUTZER"
echo "BEREIT"
