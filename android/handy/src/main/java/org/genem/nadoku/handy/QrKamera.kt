package org.genem.nadoku.handy

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import org.genem.nadoku.handy.kopplung.QrLeser
import java.util.concurrent.Executors

/**
 * Die Kamera-Hülle für den Kopplungs-QR (E-S4-15).
 *
 * SIE IST BEWUSST DÜNN. Was hier passiert, ist Kamera aufbauen, Bilder an
 * [QrLeser] geben, beim ersten Treffer melden und abbauen. Alles, woran etwas
 * falsch sein kann — das Bildformat, die Zeilenbreite, die Auswertung des
 * gefundenen Textes — liegt in [QrLeser] und [org.genem.nadoku.handy.kopplung.QrInhalt]
 * und ist ohne Kamera geprüft.
 *
 * UNGEPRÜFT BLEIBT genau dieser Aufbau: Es gibt im Container keinen Emulator
 * (E-R45-8) und damit keine Kamera. Das steht auf der Prüfliste des
 * Gerätetests.
 *
 * KEEP_ONLY_LATEST: Bei dreißig Bildern je Sekunde und einer Erkennung, die
 * länger als ein Bild braucht, liefe sonst eine Warteschlange voll — die App
 * suchte dann in Bildern von vor zwei Sekunden.
 */
@Composable
fun QrKamera(
    aufFund: (String) -> Unit,
    aufFehlenderFreigabe: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val kontext = LocalContext.current
    val lebenslauf = LocalLifecycleOwner.current
    val fundMelder by rememberUpdatedState(aufFund)

    var freigegeben by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(kontext, Manifest.permission.CAMERA) ==
                PackageManager.PERMISSION_GRANTED
        )
    }

    val frage = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { erlaubt ->
        freigegeben = erlaubt
        if (!erlaubt) aufFehlenderFreigabe()
    }

    LaunchedEffect(Unit) {
        if (!freigegeben) frage.launch(Manifest.permission.CAMERA)
    }

    if (!freigegeben) {
        Box(modifier)
        return
    }

    val rechenknecht = remember { Executors.newSingleThreadExecutor() }
    DisposableEffect(Unit) { onDispose { rechenknecht.shutdown() } }

    // Genau EIN Fund. Ohne diese Sperre meldete dieselbe Kopplung so oft, wie
    // der Code im Bild bleibt — und jede Meldung löste eine Kopplungsanfrage
    // aus, die der Ratenschutz von pair.php zählt.
    val schonGemeldet = remember { java.util.concurrent.atomic.AtomicBoolean(false) }

    AndroidView(
        modifier = modifier.fillMaxSize(),
        factory = { k ->
            val sicht = PreviewView(k)
            val anbieter = ProcessCameraProvider.getInstance(k)
            anbieter.addListener({
                val kamera = anbieter.get()

                val vorschau = Preview.Builder().build().also {
                    it.surfaceProvider = sicht.surfaceProvider
                }
                val auswertung = ImageAnalysis.Builder()
                    .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                    .build()
                    .also { stufe ->
                        stufe.setAnalyzer(rechenknecht) { bild ->
                            try {
                                val text = QrLeser.lese(bild)
                                if (text != null && schonGemeldet.compareAndSet(false, true)) {
                                    sicht.post { fundMelder(text) }
                                }
                            } finally {
                                // Ohne close() bleibt der Bildpuffer belegt und
                                // die Kamera liefert kein weiteres Bild mehr.
                                bild.close()
                            }
                        }
                    }

                kamera.unbindAll()
                kamera.bindToLifecycle(
                    lebenslauf, CameraSelector.DEFAULT_BACK_CAMERA, vorschau, auswertung
                )
            }, ContextCompat.getMainExecutor(k))
            sicht
        },
    )
}
