/**
 * Lector de Código de Barras para Activos
 * Utiliza Html5-Qrcode, la mejor librería open-source cross-browser.
 * Solo se activa en dispositivos móviles.
 */
(function() {
    'use strict';

    if (window.innerWidth > 767) return;

    let html5QrCode = null;
    let isScanning = false;

    const SEARCH_URL_ATTR = 'data-scan-url';

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        const scanFab = document.getElementById('scanFab');
        const scannerOverlay = document.getElementById('scannerOverlay');
        const scannerClose = document.getElementById('scannerClose');
        const scannerStatus = document.getElementById('scannerStatus');
        const scannerResult = document.getElementById('scannerResult');

        if (!scanFab || !scannerOverlay) return;

        const searchUrl = scanFab.getAttribute(SEARCH_URL_ATTR);

        // Al hacer click en el FAB
        scanFab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.history.pushState({ scannerOpen: true }, '');
            openScanner(scannerOverlay, scannerStatus, searchUrl);
        });

        // Interceptar botón "Atrás" de Android
        window.addEventListener('popstate', function(e) {
            if (isScanning || scannerOverlay.classList.contains('show')) {
                closeScanner(scannerOverlay);
            }
        });

        // Cerrar con la X
        scannerClose.addEventListener('click', function() {
            window.history.back();
        });

        // Reintentar escanear
        document.getElementById('scannerRetry')?.addEventListener('click', function() {
            scannerResult.classList.remove('show');
            if (html5QrCode) {
                html5QrCode.resume();
            } else {
                startReading(scannerStatus, searchUrl);
            }
        });

        // Cerrar desde el resultado
        document.getElementById('scannerResultClose')?.addEventListener('click', function() {
            window.history.back();
        });
    }

    async function openScanner(overlay, status, searchUrl) {
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        status.textContent = 'Iniciando cámara...';
        status.className = 'scanner-status';

        // Cargar librería dinámicamente si no existe
        try {
            await loadHtml5Qrcode();
            startReading(status, searchUrl);
        } catch (err) {
            showStatus(status, 'Error cargando el lector: ' + err.message, 'error');
        }
    }

    function startReading(status, searchUrl) {
        isScanning = true;
        showStatus(status, 'Apunta al código de barras', '');

        // El div donde la librería inyectará el video.
        // Reemplazamos el <video> manual por un div para la librería
        let viewfinder = document.querySelector('.scanner-viewfinder');
        let videoEl = document.getElementById('scannerVideo');
        if (videoEl) {
            videoEl.remove(); // Quitamos nuestro video manual
            let readerDiv = document.createElement('div');
            readerDiv.id = 'html5qr-reader';
            readerDiv.style.width = '100%';
            readerDiv.style.height = '100%';
            viewfinder.prepend(readerDiv);
        }

        let frame = document.querySelector('.scanner-frame');
        if (frame) frame.style.display = 'none'; // Ocultar frame manual, la librería dibuja el suyo

        html5QrCode = new Html5Qrcode("html5qr-reader");

        // Configuración óptima para códigos 1D, QR y Data Matrix
        const config = {
            fps: 15,
            qrbox: { width: 280, height: 150 },
            aspectRatio: 1.0,
            disableFlip: false,
            formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.DATA_MATRIX,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.ITF,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E
            ]
        };

        // Solicitar cámara trasera
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText, decodedResult) => {
                // Éxito: Encontró un código
                html5QrCode.pause(); // Pausar cámara inmediatamente
                let code = decodedText.trim();
                
                // Parser para extraer Service Tag (Dell) o S/N si está en formato compuesto
                const stMatch = code.match(/ST:\s*([A-Za-z0-9]+)/i);
                if (stMatch && stMatch[1]) {
                    code = stMatch[1];
                } else {
                    const snMatch = code.match(/S\/?N:\s*([A-Za-z0-9\-]+)/i);
                    if (snMatch && snMatch[1]) {
                        code = snMatch[1];
                    }
                }

                showStatus(status, 'Código detectado: ' + code, 'success');
                lookupPlaca(code, searchUrl);
            },
            (errorMessage) => {
                // Fallo de lectura en este frame (normal, ignorar)
            }
        ).catch((err) => {
            console.error(err);
            showStatus(status, 'No se pudo acceder a la cámara. Revisa permisos.', 'error');
        });
    }

    async function lookupPlaca(code, searchUrl) {
        const status = document.getElementById('scannerStatus');
        const resultDiv = document.getElementById('scannerResult');
        const resultIcon = document.getElementById('scannerResultIcon');
        const resultTitle = document.getElementById('scannerResultTitle');
        const resultPlaca = document.getElementById('scannerResultPlaca');
        const resultGoBtn = document.getElementById('scannerResultGo');

        showStatus(status, 'Buscando activo...', '');

        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('placa', code);
            const tabId = sessionStorage.getItem('_tab_id');
            if (tabId) url.searchParams.set('_tab', tabId);

            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.found) {
                resultIcon.className = 'scanner-result-icon found';
                resultIcon.innerHTML = '<i class="bi bi-check-lg"></i>';
                resultTitle.textContent = 'Activo encontrado';
                
                if (data.serial && code.toUpperCase() === data.serial.toUpperCase()) {
                    resultPlaca.textContent = 'Serial: ' + data.serial;
                } else {
                    resultPlaca.textContent = 'Placa: ' + data.placa;
                }
                
                resultGoBtn.style.display = 'block';
                resultGoBtn.onclick = function() {
                    let detailUrl = data.url;
                    if (tabId) {
                        try {
                            const u = new URL(detailUrl, window.location.origin);
                            if (!u.searchParams.has('_tab')) u.searchParams.set('_tab', tabId);
                            detailUrl = u.toString();
                        } catch(e) {}
                    }
                    window.location.href = detailUrl;
                };
            } else {
                resultIcon.className = 'scanner-result-icon not-found';
                resultIcon.innerHTML = '<i class="bi bi-x-lg"></i>';
                resultTitle.textContent = 'Activo no encontrado';
                resultPlaca.textContent = 'Código leído: ' + code;
                resultGoBtn.style.display = 'none';
            }
            resultDiv.classList.add('show');
        } catch (err) {
            showStatus(status, 'Error al consultar el activo.', 'error');
            if (html5QrCode) html5QrCode.resume();
        }
    }

    function loadHtml5Qrcode() {
        return new Promise(function(resolve, reject) {
            if (window.Html5Qrcode) return resolve();
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.onload = resolve;
            script.onerror = () => reject(new Error('Fallo al cargar librería.'));
            document.head.appendChild(script);
        });
    }

    function closeScanner(overlay) {
        isScanning = false;

        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(err => {
                console.error("Fallo al detener escáner", err);
                html5QrCode = null;
            });
        }

        const resultDiv = document.getElementById('scannerResult');
        if (resultDiv) resultDiv.classList.remove('show');

        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    function showStatus(el, text, type) {
        if (!el) return;
        el.textContent = text;
        el.className = 'scanner-status' + (type ? ' ' + type : '');
    }
})();
