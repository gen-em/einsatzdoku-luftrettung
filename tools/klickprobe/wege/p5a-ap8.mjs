/* Wege des Pakets P5a/AP8 — Betrieb → Status → Sicherheit.
 * ===========================================================================
 *
 *   E-P5a-08   Die Unterseite mit fünf Karten und dem einen Knopf, der
 *              etwas ändert: „Aufheben".
 *
 * WARUM DIESE SEITE HIER STEHT UND NICHT NUR IM BILDERLAUF. Der Bilderlauf
 * fotografiert; er hat noch nie einen Knopf gedrückt. „Aufheben" ist die
 * EINZIGE Handlung der Seite, sie geht über POST mit CSRF-Token, und sie
 * hängt an drei Dingen, die ein Bild nicht zeigt: dass `data-confirm` eine
 * Rückfrage öffnet, dass der Knopf ein Formular absendet, in dem er gar nicht
 * steht (`form=`), und dass danach die richtige Zeile fort ist.
 *
 * DER WEG STELLT SEINEN BESTAND SELBST HER UND RÄUMT IHN AB. Er legt eine
 * Sperre auf ein erfundenes Merkmal (`id:klickprobe-ap8@example.invalid`,
 * Topf `login`), hebt sie über die Oberfläche auf und löscht im `finally`
 * alles, was er angelegt hat — auch wenn er unterwegs scheitert. Eine echte
 * Sperre anzulegen wäre der andere Weg gewesen; er hieße, sich selbst
 * auszusperren.
 *
 * GEMESSEN WIRD AM DOM UND AN DER DATENBANK, nicht an der Absicht: die Zahl
 * der Sperrzeilen vorher und nachher, und ob ein Ereignis „aufgehoben" mit
 * dem Namen der handelnden Person entstanden ist. Ohne den zweiten Teil
 * belegte der Weg nur, dass eine Zeile verschwindet — nicht, dass der
 * Vorgang protokolliert wird, und genau dafür ist die Spalte `wer` da.
 */

import { execFileSync } from 'node:child_process';

const SEITE   = k => `${k.basis}/betrieb_sicherheit.php`;
const MERKMAL = 'id:klickprobe-ap8@example.invalid';
const TOPF    = 'login';

/** Ein kurzes PHP-Stück gegen die örtliche Installation fahren. */
function php(code) {
  return execFileSync('php', ['-r',
    'require "server/config.php"; require "server/db.php"; ' + code],
    { cwd: process.env.ED_WURZEL || '/home/user/einsatzdoku-luftrettung',
      encoding: 'utf-8' }).trim();
}

function sperreAnlegen() {
  php('db()->prepare("INSERT INTO rate_limits (topf, merkmal, versuche, fenster_start,'
    + ' gesperrt_bis, stufe, stufe_bis) VALUES (?,?,9,NOW(),'
    + ' DATE_ADD(NOW(), INTERVAL 900 SECOND),1,DATE_ADD(NOW(), INTERVAL 1 DAY))'
    + ' ON DUPLICATE KEY UPDATE gesperrt_bis = VALUES(gesperrt_bis)")'
    + '->execute(["' + TOPF + '", "' + MERKMAL + '"]);');
}

function aufraeumen() {
  php('db()->prepare("DELETE FROM rate_limits WHERE merkmal = ?")->execute(["' + MERKMAL + '"]);'
    + ' db()->prepare("DELETE FROM sicherheit_ereignisse WHERE merkmal = ?")->execute(["' + MERKMAL + '"]);');
}

/** Zählt, was in der Datenbank steht — die Gegenprobe zum DOM. */
function stand() {
  const roh = php('$p = db();'
    + ' $a = $p->prepare("SELECT COUNT(*) FROM rate_limits WHERE merkmal = ?");'
    + ' $a->execute(["' + MERKMAL + '"]);'
    + ' $b = $p->prepare("SELECT wer FROM sicherheit_ereignisse'
    + ' WHERE merkmal = ? AND art = \'aufgehoben\' ORDER BY id DESC LIMIT 1");'
    + ' $b->execute(["' + MERKMAL + '"]);'
    + ' echo json_encode(["sperren" => (int)$a->fetchColumn(),'
    + ' "wer" => $b->fetchColumn() ?: null]);');
  return JSON.parse(roh);
}

/** Die Zeilen der Karte „Aktive Sperren" im DOM. */
const zeilen = k => k.seite.evaluate(() => Array.from(
  document.querySelectorAll('#k-sperren .zeile')).map(z => ({
    text: (z.querySelector('.zeile-haupt') || {}).textContent || '',
    knopf: !!z.querySelector('.zeile-aktionen button, .zeile-aktionen a'),
  })));

export const wege = [
  {
    name: 'p5a-ap8-sperre-aufheben',
    paket: 'P5a-AP8', punkt: 'E-P5a-08', rolle: 'admin',
    soll: 'Die Sperre steht in der Karte, die Rückfrage kommt, nach dem '
        + 'Bestätigen ist die Zeile fort, die Datenbankzeile auch — und ein '
        + 'Ereignis „aufgehoben" trägt den Namen der handelnden Person',
    async fahren(k) {
      aufraeumen();
      sperreAnlegen();
      try {
        await k.gehZu(SEITE(k));
        const vorher = await zeilen(k);
        const meine = vorher.filter(z => z.text.includes('klickprobe-ap8'));
        if (meine.length !== 1 || !meine[0].knopf) {
          return {
            ist: `${vorher.length} Zeilen in „Aktive Sperren", davon `
               + `${meine.length} mit dem Prüfmerkmal`
               + (meine.length === 1 ? ' — aber ohne Knopf' : ''),
            ok: false,
            bemerkung: 'Soll: genau eine Zeile mit dem Prüfmerkmal, und sie '
                     + 'trägt einen Knopf „Aufheben"',
          };
        }
        await k.bild('p5a-ap8-sperre-steht');

        /* DIE RUECKFRAGE IST TEIL DES WEGES. `data-confirm` haengt an
         * `assets/confirm.js`; ohne das Skript liefe der Klick durch, und
         * das waere ein anderer Weg als der, den eine Betreiberin geht. */
        const knopf = k.seite.locator('#k-sperren .zeile')
          .filter({ hasText: 'klickprobe-ap8' })
          .locator('.zeile-aktionen button, .zeile-aktionen a').first();
        await knopf.click();
        await k.seite.waitForTimeout(300);
        const dialogDa = await k.seite.evaluate(
          () => !!document.querySelector('dialog[open], .blatt[open]'));
        await k.bild('p5a-ap8-rueckfrage');
        if (!dialogDa) {
          return { ist: 'Der Klick hat keine Rückfrage geöffnet', ok: false,
                   bemerkung: 'Soll: `data-confirm` öffnet einen Dialog, bevor '
                            + 'die Sperre fällt' };
        }

        await k.seite.getByRole('button', { name: 'Aufheben', exact: true }).last().click();
        await k.seite.waitForLoadState('domcontentloaded');
        await k.seite.waitForTimeout(400);

        const nachher = await zeilen(k);
        const nochDa  = nachher.filter(z => z.text.includes('klickprobe-ap8')).length;
        const db      = stand();
        const meldung = await k.seite.evaluate(() => {
          const m = document.querySelector('.meldung');
          return m ? m.textContent.replace(/\s+/g, ' ').trim() : null;
        });
        await k.bild('p5a-ap8-nach-dem-aufheben');

        const ok = nochDa === 0 && db.sperren === 0
                && typeof db.wer === 'string' && db.wer.includes('@');
        return {
          ist: `Zeilen mit Prüfmerkmal: ${meine.length} → ${nochDa}; `
             + `rate_limits: 1 → ${db.sperren}; Ereignis „aufgehoben" durch `
             + `„${db.wer ?? '—'}"; Meldung: „${(meldung ?? '—').slice(0, 70)}"`,
          ok,
          bemerkung: ok ? ''
            : 'Soll: Zeile fort, rate_limits 0, und ein Ereignis „aufgehoben" '
            + 'mit der Kontokennung der handelnden Person',
        };
      } finally {
        aufraeumen();
      }
    },
  },
];
