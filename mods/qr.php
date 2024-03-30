<?php
if ($section == "evaluation" && $go == "default") {
    ?>


    <h1>qr</h1>
    Skenovani QR<br>

    <button id="scan-button" class="button">Scan QR</button>
    <div class="collapse" id="qrScannerDisplay">
        <video id="qr-video" style="max-width: 100%"></video>
    </div>
    <script type="module">
        import QrScanner from './mods/qr-scanner.min.js';

        const video = document.getElementById('qr-video');


        const scanner = new QrScanner(video, result => showResult(result.data), {
            highlightScanRegion: true,
            highlightCodeOutline: true
        });


        function showResult(data) {
            console.log(data);
            if (data && data.startsWith("<?php echo $base_url; ?>")) {
                window.location = data;
            }
        }

        function click() {
            console.log('click');
            $('#qrScannerDisplay').collapse('show');
            scanner.start();
        }

        document.getElementById('scan-button').addEventListener('click', click);

    </script>
    <?php
}
?>
