#!/usr/bin/env python3
"""Drei Gegenstellen für die Versandprobe: FTP, FTPS und SFTP.

Sie laufen auf 127.0.0.1 und dienen einem einzigen Zweck — den Adaptern aus
`server/sicherungsziel_lib.php` einen echten Server gegenüberzustellen. Ein
Adapter, der nur gegen eine Attrappe geprüft wurde, ist nicht geprüft: Die
Fehler dieser Protokolle stecken in Kleinigkeiten (passiver Modus, MLSD gegen
NLST, wann `ftp_pasv` erlaubt ist), und keine davon fällt einer Attrappe auf.

Was NICHT geprüft werden kann: das Verhalten echter Gegenstellen im Internet.
Aus diesem Behälter sind die Ports 21 und 22 nach draussen nicht erreichbar
(nur 443 wird durchgereicht) — die Abnahme gegen ein echtes Ziel gehört auf
die Maschine der Betreiberin oder auf den Produktivserver.

    python3 gegenstellen.py <wurzelverzeichnis>

Läuft im Vordergrund und schreibt "BEREIT" auf die Standardausgabe, sobald
alle drei horchen. Beenden mit Strg-C oder SIGTERM.

Braucht `pyftpdlib` und `paramiko` (pip install pyftpdlib paramiko).
"""
import os
import socket
import subprocess
import sys
import threading

PORT_FTP  = 2121
PORT_FTPS = 2122
PORT_SFTP = 2222
NUTZER    = 'probe'
NUTZER_NURLESEN = 'nurlesen'
PASSWORT  = 'geheim-probe-2026'

# --------------------------------------------------------------------------
# FTP und FTPS (pyftpdlib)
# --------------------------------------------------------------------------

def zertifikat(pfad):
    """Ein selbst ausgestelltes Zertifikat OHNE jede Vertrauenskette.

    Genau das ist der Punkt der FTPS-Prüfung: Kommt die Verbindung damit
    zustande, dann prüft `ext/ftp` das Zertifikat nicht. Das ist keine
    Vermutung mehr, sondern eine Messung.
    """
    if os.path.exists(pfad):
        return pfad
    subprocess.run(
        ['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes',
         '-keyout', pfad, '-out', pfad, '-days', '2', '-subj',
         '/CN=versandprobe-selbst-ausgestellt'],
        check=True, capture_output=True)
    return pfad


def _ftp_lauf(wurzel, port, tls, zertpfad, passiv_von, passiv_bis):
    """Läuft in einem EIGENEN PROZESS — und das ist kein Beiwerk.

    pyftpdlib hält seine Ereignisschleife in einer globalen Instanz. Zwei
    `FTPServer` in zwei Threads desselben Prozesses greifen darauf zu, und der
    zweite reisst dem ersten den Deskriptor weg („OSError: [Errno 9] Bad file
    descriptor", gemessen). Getrennte Prozesse haben getrennte Schleifen.
    """
    from pyftpdlib.authorizers import DummyAuthorizer
    from pyftpdlib.handlers import FTPHandler
    from pyftpdlib.servers import FTPServer

    rechte = DummyAuthorizer()
    rechte.add_user(NUTZER, PASSWORT, wurzel, perm='elradfmwMT')
    # Ein zweites Konto, das NUR LESEN darf. Es ist die Gegenprobe fuer
    # „das Ziel verweigert das Schreiben" — ein Fall, den die Abnahme von AP7
    # ausdruecklich verlangt (Stichwort „Platz voll") und der sich mit
    # Dateirechten nicht herstellen laesst: Dieser Server laeuft als root, und
    # root ignoriert Rechtebits. Der Server muss es also selbst ablehnen.
    rechte.add_user(NUTZER_NURLESEN, PASSWORT, wurzel, perm='elr')

    if tls:
        from pyftpdlib.handlers import TLS_FTPHandler
        handler = TLS_FTPHandler
        handler.certfile = zertpfad
    else:
        handler = FTPHandler
    handler.authorizer = rechte
    handler.banner = 'Versandprobe'
    # Der Bereich für den passiven Modus wird eng gehalten und ist je Server
    # ein anderer: Zwei Server, die sich dieselben Datenports nehmen, stolpern
    # übereinander, sobald beide gleichzeitig übertragen.
    handler.passive_ports = range(passiv_von, passiv_bis)

    FTPServer(('127.0.0.1', port), handler).serve_forever()


def ftp_starten(wurzel, port, tls, zertpfad=None, passiv_von=30000):
    import multiprocessing
    p = multiprocessing.Process(
        target=_ftp_lauf,
        args=(wurzel, port, tls, zertpfad, passiv_von, passiv_von + 50),
        daemon=True)
    p.start()
    return p


# --------------------------------------------------------------------------
# SFTP (paramiko)
# --------------------------------------------------------------------------

def sftp_starten(wurzel, port, hostkey):
    import paramiko

    # JEDER Anmeldeversuch wird mitgeschrieben. Das ist die Messgrösse für die
    # wichtigste Zusage des SFTP-Adapters: Bei einem unerwarteten
    # Hostschlüssel darf KEIN Passwort über die Leitung gehen. „Der Adapter
    # bricht ab" liesse sich behaupten; „die Gegenstelle hat keinen
    # Anmeldeversuch gesehen" ist gezählt.
    protokoll = os.path.join(os.path.dirname(wurzel), 'anmeldungen.log')

    def merken(zeile):
        with open(protokoll, 'a') as f:
            f.write(zeile + '\n')

    class Rechte(paramiko.ServerInterface):
        def check_auth_password(self, nutzer, passwort):
            gut = nutzer == NUTZER and passwort == PASSWORT
            merken('passwort %s %s' % (nutzer, 'gut' if gut else 'schlecht'))
            return paramiko.AUTH_SUCCESSFUL if gut else paramiko.AUTH_FAILED

        def check_auth_publickey(self, nutzer, key):
            merken('schluessel %s' % nutzer)
            # Für die Probe genügt: JEDER Schlüssel dieses Nutzers gilt. Was
            # hier geprüft wird, ist der Weg durch den Adapter (Schlüssel
            # lesen, Passphrase, Anmeldung), nicht die Rechteverwaltung eines
            # OpenSSH-Servers.
            return (paramiko.AUTH_SUCCESSFUL if nutzer == NUTZER
                    else paramiko.AUTH_FAILED)

        def get_allowed_auths(self, nutzer):
            return 'password,publickey'

        def check_channel_request(self, art, chanid):
            return (paramiko.OPEN_SUCCEEDED if art == 'session'
                    else paramiko.OPEN_FAILED_ADMINISTRATIVELY_PROHIBITED)

    class Dateien(paramiko.SFTPServerInterface):
        """Ein SFTP-Server auf einem Verzeichnis, mit Riegel gegen Ausbruch."""

        def _echt(self, pfad):
            p = os.path.normpath(os.path.join(wurzel, pfad.lstrip('/')))
            if not (p == wurzel or p.startswith(wurzel + os.sep)):
                raise PermissionError(pfad)
            return p

        def list_folder(self, pfad):
            try:
                p = self._echt(pfad)
                raus = []
                for name in os.listdir(p):
                    st = os.stat(os.path.join(p, name))
                    eintrag = paramiko.SFTPAttributes.from_stat(st)
                    eintrag.filename = name
                    raus.append(eintrag)
                return raus
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)

        def stat(self, pfad):
            try:
                return paramiko.SFTPAttributes.from_stat(os.stat(self._echt(pfad)))
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)

        lstat = stat

        def open(self, pfad, flags, attr):
            try:
                p = self._echt(pfad)
                if flags & os.O_WRONLY:
                    modus = 'ab' if flags & os.O_APPEND else 'wb'
                elif flags & os.O_RDWR:
                    modus = 'a+b' if flags & os.O_APPEND else 'r+b'
                else:
                    modus = 'rb'
                f = open(p, modus)
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)
            griff = paramiko.SFTPHandle(flags)
            griff.filename = p
            griff.readfile = f
            griff.writefile = f
            return griff

        def remove(self, pfad):
            try:
                os.remove(self._echt(pfad))
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)
            return paramiko.SFTP_OK

        def rename(self, alt, neu):
            try:
                os.rename(self._echt(alt), self._echt(neu))
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)
            return paramiko.SFTP_OK

        def mkdir(self, pfad, attr):
            try:
                os.mkdir(self._echt(pfad))
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)
            return paramiko.SFTP_OK

        def rmdir(self, pfad):
            try:
                os.rmdir(self._echt(pfad))
            except OSError as e:
                return paramiko.SFTPServer.convert_errno(e.errno)
            return paramiko.SFTP_OK

        def chattr(self, pfad, attr):
            return paramiko.SFTP_OK

    def bedienen(sock):
        t = paramiko.Transport(sock)
        t.add_server_key(hostkey)
        t.set_subsystem_handler('sftp', paramiko.SFTPServer, Dateien)
        try:
            t.start_server(server=Rechte())
            kanal = t.accept(30)
            if kanal is None:
                return
            while t.is_active():
                t.join(1)
        except Exception:
            pass
        finally:
            try:
                t.close()
            except Exception:
                pass

    horcher = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    horcher.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    horcher.bind(('127.0.0.1', port))
    horcher.listen(20)

    def schleife():
        while True:
            try:
                sock, _ = horcher.accept()
            except OSError:
                return
            threading.Thread(target=bedienen, args=(sock,), daemon=True).start()

    threading.Thread(target=schleife, daemon=True).start()
    return horcher


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        return 2
    wurzel = os.path.abspath(sys.argv[1])
    for teil in ('ftp', 'ftps', 'sftp'):
        os.makedirs(os.path.join(wurzel, teil), exist_ok=True)
        # Ein Grundpfad, der NICHT die Wurzel ist. Im Betrieb heisst er
        # `/backups/einsatzdoku`; mit `/` allein bleibt die Zusammensetzung
        # in sz_pfad() ungeprueft.
        os.makedirs(os.path.join(wurzel, teil, 'tief', 'darunter'), exist_ok=True)

    import paramiko
    hostkey_pfad = os.path.join(wurzel, 'hostkey')
    if os.path.exists(hostkey_pfad):
        hostkey = paramiko.RSAKey(filename=hostkey_pfad)
    else:
        hostkey = paramiko.RSAKey.generate(2048)
        hostkey.write_private_key_file(hostkey_pfad)

    # Ein NUTZERschlüssel für die Anmeldung mit privatem Schlüssel — einmal
    # ohne und einmal mit Passphrase. Der Server nimmt jeden Schlüssel dieses
    # Nutzers an; geprüft wird der Weg durch den Adapter, nicht die
    # Rechteverwaltung eines OpenSSH-Servers.
    nutzerkey_pfad = os.path.join(wurzel, 'nutzerschluessel')
    if not os.path.exists(nutzerkey_pfad):
        nk = paramiko.RSAKey.generate(2048)
        nk.write_private_key_file(nutzerkey_pfad)
        nk.write_private_key_file(nutzerkey_pfad + '-mit-passwort',
                                  password='passphrase-der-probe')

    ftp_starten(os.path.join(wurzel, 'ftp'), PORT_FTP, False, passiv_von=30000)
    ftp_starten(os.path.join(wurzel, 'ftps'), PORT_FTPS, True,
                zertifikat(os.path.join(wurzel, 'zert.pem')), passiv_von=30100)
    sftp_starten(os.path.join(wurzel, 'sftp'), PORT_SFTP, hostkey)

    # Erst melden, wenn alle drei Ports tatsächlich horchen. Ein „BEREIT",
    # das vor dem Horchen kommt, macht aus einem Startproblem einen
    # Verbindungsfehler in der Probe — und der zeigt dann auf den Adapter.
    for port in (PORT_FTP, PORT_FTPS, PORT_SFTP):
        for _ in range(100):
            with socket.socket() as s:
                s.settimeout(0.3)
                if s.connect_ex(('127.0.0.1', port)) == 0:
                    break
            import time
            time.sleep(0.1)
        else:
            print('FEHLER: Port %d horcht nicht.' % port, file=sys.stderr)
            return 1

    # Der Fingerabdruck wird ausgegeben, damit die Probe ihn gegen den halten
    # kann, den der Adapter errechnet. Dieselbe Schreibweise wie bei OpenSSH.
    import base64
    import hashlib
    abdruck = 'SHA256:' + base64.b64encode(
        hashlib.sha256(hostkey.asbytes()).digest()).decode().rstrip('=')
    # Auch als Datei, damit die Probe ihn lesen kann, ohne die Ausgabe dieses
    # Prozesses mitzuschneiden.
    with open(os.path.join(wurzel, 'fingerabdruck.txt'), 'w') as f:
        f.write(abdruck + '\n')
    print('FINGERABDRUCK ' + abdruck, flush=True)
    print('BEREIT', flush=True)
    try:
        threading.Event().wait()
    except KeyboardInterrupt:
        pass
    return 0


if __name__ == '__main__':
    sys.exit(main())
