#!/usr/bin/env python3
"""Ein SMTPS-Gegenpart fuer die Mailprobe — klein, laut und steuerbar.

WARUM EIGEN UND NICHT `aiosmtpd`: Die Probe will nicht messen, ob ein
Mailserver funktioniert, sondern wie sich `smtp.php` verhaelt, wenn er es
NICHT tut. Dafuer muss die Gegenstelle auf Kommando langsam sein, auf
Kommando ablehnen und auf Kommando mitten im Gespraech schweigen. Eine
fertige Bibliothek koennte das erste, nicht das zweite und dritte.

`smtp.php` verbindet mit `ssl://host:port` und `verify_peer => true`. Die
Gegenstelle braucht also implizites TLS (Port 465, kein STARTTLS) und ein
Zertifikat, dem der Client traut — `probe.php` legt beides an und setzt
`SSL_CERT_FILE` auf die eigene Wurzel.

Betriebsarten (`--art`):
  ok        nimmt alles an, antwortet sofort           -> Zustellung
  ablehnen  550 auf RCPT TO                            -> Fehlschlag mit Grund
  langsam   jede Antwort nach `--verzug` Sekunden      -> Fristprobe
  vielzeilig  EHLO-Antwort in 12 Fortsetzungszeilen,
              je `--verzug` Sekunden                   -> die Rechnung aus
                                                          E-P5a-36 nachstellen
  stumm     begruesst und schweigt dann                -> Abbruch
"""
import argparse, socket, ssl, sys, threading, time

def zeile(verbindung, text, verzug=0.0):
    if verzug:
        time.sleep(verzug)
    verbindung.sendall((text + "\r\n").encode("utf-8"))

def gespraech(v, art, verzug):
    datei = v.makefile("rb")
    zeile(v, "220 mailprobe bereit")
    im_body = False
    # AUTH LOGIN ist ein Dreischritt: 334 (Name?), 334 (Passwort?), 235 (gut).
    # Die erste Fassung antwortete dreimal 334 — und `smtp.php` brach beim
    # dritten Schritt ab, BEVOR es je ein RCPT TO schickte. Die Probe mass
    # damit ueberall denselben Fehler, und die Betriebsart `ablehnen` war
    # ununterscheidbar von `ok`. Ein Gegenpart, der das Protokoll nur
    # ungefaehr spricht, misst nichts.
    auth_schritt = 0
    while True:
        roh = datei.readline()
        if not roh:
            return
        b = roh.decode("utf-8", "replace").rstrip("\r\n")
        if im_body:
            if b == ".":
                im_body = False
                zeile(v, "250 2.0.0 angenommen", verzug)
            continue
        oben = b.upper()
        if oben.startswith("EHLO") or oben.startswith("HELO"):
            if art == "vielzeilig":
                for i in range(12):
                    zeile(v, "250-X-MESSUNG-%02d" % i, verzug)
                zeile(v, "250 OK", verzug)
            else:
                zeile(v, "250-mailprobe", verzug)
                zeile(v, "250 AUTH LOGIN", verzug)
        elif oben.startswith("AUTH"):
            auth_schritt = 1
            zeile(v, "334 VXNlcm5hbWU6", verzug)
        elif oben.startswith("MAIL FROM"):
            zeile(v, "250 2.1.0 Absender ok", verzug)
        elif oben.startswith("RCPT TO"):
            if art == "ablehnen":
                zeile(v, "550 5.1.1 <geheim@example.invalid>: user unknown", verzug)
            else:
                zeile(v, "250 2.1.5 Empfaenger ok", verzug)
        elif oben.startswith("DATA"):
            zeile(v, "354 Los", verzug)
            im_body = True
        elif oben.startswith("QUIT"):
            zeile(v, "221 2.0.0 Tschuess", verzug)
            return
        elif auth_schritt == 1:
            auth_schritt = 2
            zeile(v, "334 UGFzc3dvcmQ6", verzug)      # der Name kam, jetzt das Passwort
        elif auth_schritt == 2:
            auth_schritt = 3
            zeile(v, "235 2.7.0 angemeldet", verzug)  # das Passwort kam
        else:
            zeile(v, "500 5.5.1 unbekannter Befehl", verzug)

def main():
    p = argparse.ArgumentParser()
    p.add_argument("--port", type=int, default=2465)
    p.add_argument("--art", default="ok",
                   choices=["ok", "ablehnen", "langsam", "vielzeilig", "stumm"])
    p.add_argument("--verzug", type=float, default=0.0)
    p.add_argument("--zert", required=True)
    p.add_argument("--schluessel", required=True)
    a = p.parse_args()
    verzug = a.verzug if a.art in ("langsam", "vielzeilig") else 0.0

    ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    ctx.load_cert_chain(a.zert, a.schluessel)

    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    s.bind(("127.0.0.1", a.port))
    s.listen(8)
    print("mailprobe-gegenstelle: %s auf 127.0.0.1:%d (Verzug %.1f s)"
          % (a.art, a.port, verzug), flush=True)
    while True:
        roh, _ = s.accept()
        try:
            v = ctx.wrap_socket(roh, server_side=True)
        except Exception as ex:
            print("  TLS gescheitert: %s" % ex, flush=True)
            roh.close()
            continue
        if a.art == "stumm":
            # Begruessen und dann schweigen: Der Client laeuft in seine Frist.
            t = threading.Thread(target=lambda: (zeile(v, "220 mailprobe bereit"),
                                                 time.sleep(600)), daemon=True)
        else:
            t = threading.Thread(target=gespraech, args=(v, a.art, verzug), daemon=True)
        t.start()

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        sys.exit(0)
