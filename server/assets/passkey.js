/* passkey.js — Passkeys anlegen und bestätigen (Schritt 18, SR-09;
 * E-SR-29 bis -32).
 *
 * ZWEI GRIFFE, BEIDE AN `data-`-ATTRIBUTEN, kein Inline-Skript (CSP):
 *
 *   [data-passkey-anlegen]      der Bereich „Passkey hinzufügen" in der Karte
 *                               „Zweitfaktor" (Einstellungen → Profil). Er
 *                               trägt Herausforderung, rp.id, Nutzerkennung,
 *                               Namen und die vorhandenen Kennungen; die
 *                               Antwort geht über `EdApi.postJson()` an
 *                               `api/passkey_anlegen.php`, danach lädt die
 *                               Seite neu (die Meldung legt der Endpunkt ab).
 *   [data-passkey-bestaetigen]  der Knopf „Mit Passkey bestätigen" im
 *                               Code-Schritt der Anmeldung und auf der
 *                               Bestätigungsseite. Die Antwort kommt in das
 *                               versteckte Feld `passkey_antwort` seines
 *                               Formulars, dazu der Haken „Gerät merken",
 *                               wenn es ihn gibt — dann wird abgeschickt.
 *
 * OHNE `PublicKeyCredential` BLEIBT ALLES VERBORGEN: Beide Bereiche tragen
 * `hidden` im Markup, und nur dieses Skript nimmt es weg. Ohne JavaScript
 * bleibt der Codeweg (E-SR-32).
 *
 * KEIN FREMDBESTANDTEIL (E-SR-30): Base64url und die zwei Aufrufe der
 * Plattform sind alles, was es braucht.
 */
(function () {
  'use strict';

  /** Base64url ↔ Bytes — die eine Stelle im Browser (im Server: pk_b64u()). */
  const b64u = {
    zu(puffer) {
      const bytes = new Uint8Array(puffer);
      let s = '';
      for (let i = 0; i < bytes.length; i++) { s += String.fromCharCode(bytes[i]); }
      return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    },
    von(text) {
      const b = atob(text.replace(/-/g, '+').replace(/_/g, '/')
                     + '==='.slice((text.length + 3) % 4));
      const a = new Uint8Array(b.length);
      for (let i = 0; i < b.length; i++) { a[i] = b.charCodeAt(i); }
      return a.buffer;
    },
  };

  const kennt = !!(window.PublicKeyCredential && navigator.credentials);

  /** Die Kennungen eines Bereichs als Liste für allow-/excludeCredentials. */
  function kennungen(el) {
    let liste = [];
    try { liste = JSON.parse(el.dataset.pkKennungen || '[]'); } catch (e) { liste = []; }
    return liste.map(id => ({ type: 'public-key', id: b64u.von(id) }));
  }

  /** Eine Meldung in die Zustandszeile des Bereichs — über EdHtml. */
  function melden(el, ton, text) {
    const z = el.querySelector('[data-passkey-zustand]');
    if (z) { z.innerHTML = EdHtml.meldung(ton, text); }
  }

  /** Was der Browser meldet, in einem Satz, den man versteht. */
  function grund(e) {
    if (e && e.name === 'NotAllowedError') {
      return 'Abgebrochen oder abgelaufen — nichts geändert.';
    }
    if (e && e.name === 'InvalidStateError') {
      return 'Dieser Passkey ist hier schon angelegt.';
    }
    return 'Der Browser konnte keinen Passkey verwenden.';
  }

  /* ---- Anlegen ----------------------------------------------------------- */
  document.querySelectorAll('[data-passkey-anlegen]').forEach(bereich => {
    if (!kennt) { return; }
    bereich.hidden = false;
    const knopf = bereich.querySelector('[data-passkey-knopf]');
    if (!knopf) { return; }
    knopf.addEventListener('click', async () => {
      knopf.disabled = true;
      melden(bereich, 'info', 'Der Browser fragt jetzt nach dem Passkey …');
      try {
        const d = bereich.dataset;
        const cred = await navigator.credentials.create({ publicKey: {
          challenge: b64u.von(d.pkHerausforderung),
          rp: { id: d.pkRpId, name: d.pkRpName },
          user: { id: b64u.von(d.pkNutzer), name: d.pkName, displayName: d.pkName },
          pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
          authenticatorSelection: { userVerification: 'preferred', residentKey: 'preferred' },
          attestation: 'none',
          timeout: 120000,
          excludeCredentials: kennungen(bereich),
        } });
        const feld = bereich.querySelector('input[name="pk_bezeichnung"]');
        const antw = await EdApi.postJson('api/passkey_anlegen.php', {
          antwort: {
            rawId: b64u.zu(cred.rawId),
            clientDataJSON: b64u.zu(cred.response.clientDataJSON),
            attestationObject: b64u.zu(cred.response.attestationObject),
          },
          bezeichnung: feld ? feld.value : '',
        }, { vorgang: 'Das Anlegen des Passkeys' });
        if (!antw.ok) { throw Object.assign(new Error(antw.meldung), { eigen: true }); }
        window.location.reload();
      } catch (e) {
        melden(bereich, 'fehler', e && e.eigen ? e.message : grund(e));
        knopf.disabled = false;
      }
    });
  });

  /* ---- Bestätigen (Anmeldung, Bestätigungsseite) ------------------------- */
  document.querySelectorAll('[data-passkey-bestaetigen]').forEach(bereich => {
    if (!kennt) { return; }
    bereich.hidden = false;
    const knopf = bereich.querySelector('[data-passkey-knopf]');
    const form = document.getElementById(bereich.dataset.pkFormular || '');
    if (!knopf || !form) { return; }
    knopf.addEventListener('click', async () => {
      knopf.disabled = true;
      try {
        const cred = await navigator.credentials.get({ publicKey: {
          challenge: b64u.von(bereich.dataset.pkHerausforderung),
          rpId: bereich.dataset.pkRpId,
          allowCredentials: kennungen(bereich),
          userVerification: 'preferred',
          timeout: 120000,
        } });
        const r = cred.response;
        form.querySelector('input[name="passkey_antwort"]').value = JSON.stringify({
          rawId: b64u.zu(cred.rawId),
          clientDataJSON: b64u.zu(r.clientDataJSON),
          authenticatorData: b64u.zu(r.authenticatorData),
          signature: b64u.zu(r.signature),
        });
        /* Der Haken „Gerät merken" steht im Codeformular daneben (E-SR-32:
           ein Passkey zählt wie ein App-Code). */
        const merken = document.getElementById('sw-merken');
        const ziel = form.querySelector('input[name="merken"]');
        if (ziel) { ziel.value = merken && merken.checked ? '1' : ''; }
        form.submit();
      } catch (e) {
        melden(bereich, 'fehler', grund(e));
        knopf.disabled = false;
      }
    });
  });
})();
