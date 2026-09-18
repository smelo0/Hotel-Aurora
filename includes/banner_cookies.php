<?php
// Al establecer la expiración en el pasado al guardar, o no leer la cookie previa,
// forzamos a que en la recarga vuelva a aparecer.
$tienePreferencia = false; // Cambiado a false para que siempre se muestre al cargar/recargar
?>

<?php if (!$tienePreferencia): ?>
<!-- Banner Principal -->
<div id="banner-cookies" style="
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #1a1a1a;
    color: #ffffff;
    padding: 15px 20px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    z-index: 9998;
    font-family: sans-serif;
    gap: 10px;
">
    <p style="margin: 0; font-size: 14px; max-width: 600px;">
        En Hotel Aurora utilizamos cookies propias y de terceros para asegurar la navegación y analizar el uso de la web. Puedes aceptarlas todas, rechazarlas o configurar tus preferencias.
    </p>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button onclick="guardarPreferencias('todas')" style="background-color: #053c0e; color: #fff; border: none; padding: 8px 14px; cursor: pointer; border-radius: 4px; font-weight: bold;">
            Aceptar todas
        </button>
        <button onclick="abrirModalConfiguracion()" style="background-color: #444; color: #fff; border: none; padding: 8px 14px; cursor: pointer; border-radius: 4px;">
            Configurar
        </button>
        <button onclick="guardarPreferencias('ninguna')" style="background-color: #91940f; color: #fff; border: none; padding: 8px 14px; cursor: pointer; border-radius: 4px;">
            Rechazar todas
        </button>
    </div>
</div>

<!-- Modal de Configuración (Oculto por defecto) -->
<div id="modal-cookies" style="
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    font-family: sans-serif;
">
    <div style="background-color: #222; color: #fff; padding: 25px; border-radius: 8px; max-width: 450px; width: 90%;">
        <h3 style="margin-top: 0; color: #ad8e28;">Configuración de Cookies</h3>
        <p style="font-size: 13px; color: #ccc;">Selecciona qué categorías de cookies deseas permitir:</p>
        
        <form id="form-cookies" style="display: flex; flex-direction: column; gap: 12px; margin: 20px 0;">
            <label style="display: flex; justify-content: space-between; align-items: center; font-size: 14px;">
                <span><strong>Técnicas / Necesarias:</strong> (Obligatorias para la sesión)</span>
                <input type="checkbox" checked disabled>
            </label>

            <label style="display: flex; justify-content: space-between; align-size: 14px;">
                <span><strong>Analíticas:</strong> (Métricas y estadísticas de uso)</span>
                <input type="checkbox" id="chk-analiticas">
            </label>

            <label style="display: flex; justify-content: space-between; align-items: center; font-size: 14px;">
                <span><strong>Marketing / Publicidad:</strong> (Anuncios personalizados)</span>
                <input type="checkbox" id="chk-marketing">
            </label>
        </form>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button onclick="cerrarModalConfiguracion()" style="background-color: #555; color: #fff; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px;">Cancelar</button>
            <button onclick="guardarSeleccionPersonalizada()" style="background-color: #053c0e; color: #fff; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; font-weight: bold;">Guardar Selección</button>
        </div>
    </div>
</div>

<script>
function abrirModalConfiguracion() {
    document.getElementById('modal-cookies').style.display = 'flex';
}

function cerrarModalConfiguracion() {
    document.getElementById('modal-cookies').style.display = 'none';
}

function aplicarYGuardarCookie(valorJSON) {
    // Se elimina la propiedad 'expires' para que sea solo una cookie temporal de sesión, 
    // o se borra inmediatamente fijando Max-Age=0 para pruebas.
    document.cookie = "preferencia_cookies=" + encodeURIComponent(valorJSON) + "; path=/; Max-Age=0; SameSite=Lax";

    // Ocultar elementos en la pantalla actual tras hacer clic
    document.getElementById('banner-cookies').style.display = 'none';
    cerrarModalConfiguracion();
}

function guardarPreferencias(tipo) {
    let seleccion = { tecnicas: true, analiticas: false, marketing: false };
    
    if (tipo === 'todas') {
        seleccion.analiticas = true;
        seleccion.marketing = true;
    }
    
    aplicarYGuardarCookie(JSON.stringify(seleccion));
}

function guardarSeleccionPersonalizada() {
    let seleccion = {
        tecnicas: true,
        analiticas: document.getElementById('chk-analiticas').checked,
        marketing: document.getElementById('chk-marketing').checked
    };
    
    aplicarYGuardarCookie(JSON.stringify(seleccion));
}
</script>
<?php endif; ?>