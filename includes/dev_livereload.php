<?php
$devRemoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$devHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isDevLocal = in_array($devRemoteAddr, ['127.0.0.1', '::1'], true)
    || str_starts_with($devHost, 'localhost')
    || str_starts_with($devHost, '127.0.0.1');

$devScriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$devWatchEndpoint = str_contains($devScriptName, '/interfaz/') ? '../../controladores/dev_watch.php' : 'controladores/dev_watch.php';
?>
<?php if ($isDevLocal): ?>
<script>
(() => {
    const endpoint = <?php echo json_encode($devWatchEndpoint, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    let lastCssHash = null;
    let lastCodeHash = null;
    let checking = false;

    function hotSwapCss() {
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        const stamp = Date.now();
        links.forEach(link => {
            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('http://') || href.startsWith('https://')) return;
            const cleanUrl = href.replace(/([?&])_hr=\d+(&|$)/, '$1').replace(/[?&]$/, '');
            const sep = cleanUrl.includes('?') ? '&' : '?';
            link.setAttribute('href', `${cleanUrl}${sep}_hr=${stamp}`);
        });
    }

    async function pollChanges() {
        if (checking || document.hidden) return;
        checking = true;
        try {
            const res = await fetch(`${endpoint}?_=${Date.now()}`, { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.enabled) return;

            if (lastCssHash === null && lastCodeHash === null) {
                lastCssHash = data.cssHash;
                lastCodeHash = data.codeHash;
                return;
            }

            if (data.codeHash && data.codeHash !== lastCodeHash) {
                lastCodeHash = data.codeHash;
                window.location.reload();
                return;
            }

            if (data.cssHash && data.cssHash !== lastCssHash) {
                lastCssHash = data.cssHash;
                hotSwapCss();
            }
        } catch (_) {
            // Silencioso en caso de reinicio del servidor local
        } finally {
            checking = false;
        }
    }

    window.setInterval(pollChanges, 1500);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) pollChanges();
    });
})();
</script>
<?php endif; ?>
