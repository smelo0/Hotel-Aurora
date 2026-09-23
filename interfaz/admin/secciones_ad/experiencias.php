<?php
// Conexión a la base de datos
$conexion = new mysqli('localhost', 'root', '', 'hotel');

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Consultar todas las experiencias registradas por el administrador
$resultado =$conexion->query("SELECT * FROM experiencias ORDER BY id DESC");

// Organizar las opciones por categoría
$experiencias_admin = [
    'planes' => [],
    'actividades' => [],
    'gastronomia' => []
];

if ($resultado &&$resultado->num_rows > 0) {
    while ($row =$resultado->fetch_assoc()) {
        $experiencias_admin[$row['categoria']][] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion']
        ];
    }
}
?>

<!-- Sección de Experiencias -->
<section id="experiencias" class="mx-auto mt-10 max-w-7xl px-4 reveal">
    <div class="grid gap-6 lg:grid-cols-3">
        
        <!-- Tarjeta 1: Planes Especiales -->
        <article class="room-card overflow-hidden bg-gray-900 border border-gray-800 flex flex-col justify-between shadow-xl">
            <div>
                <img src="https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&q=85&w=900" alt="Spa y bienestar" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                <div class="p-6">
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em] text-gray-400">Diversión y entretenimiento</p>
                    <h3 class="card-title mt-3 text-2xl font-black text-white">Planes especiales</h3>
                    <p class="muted-light mt-3 text-sm leading-7 text-gray-300">Rituales de relajación, masajes premium y circuitos privados para renovar cuerpo y mente.</p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <button type="button" onclick="abrirModal('planes')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-none transition duration-200 uppercase tracking-wider text-xs">
                    Ver Opciones
                </button>
            </div>
        </article>

        <!-- Tarjeta 2: Actividades -->
        <article class="room-card overflow-hidden bg-gray-900 border border-gray-800 flex flex-col justify-between shadow-xl">
            <div>
                <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&q=85&w=900" alt="Actividades" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                <div class="p-6">
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em] text-gray-400">Sale de tu zona de confort</p>
                    <h3 class="card-title mt-3 text-2xl font-black text-white">Actividades</h3>
                    <p class="muted-light mt-3 text-sm leading-7 text-gray-300">Explora las experiencias programadas y elige tus opciones preferidas.</p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <button type="button" onclick="abrirModal('actividades')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-none transition duration-200 uppercase tracking-wider text-xs">
                    Ver Opciones
                </button>
            </div>
        </article>

        <!-- Tarjeta 3: Gastronomía -->
        <article class="room-card overflow-hidden bg-gray-900 border border-gray-800 flex flex-col justify-between shadow-xl">
            <div>
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=85&w=900" alt="Alta gastronomía" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                <div class="p-6">
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em] text-gray-400">Sabores exclusivos</p>
                    <h3 class="card-title mt-3 text-2xl font-black text-white">Gastronomía</h3>
                    <p class="muted-light mt-3 text-sm leading-7 text-gray-300">Menú costero, cocina de autor y maridajes elegantes para una velada inolvidable.</p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <button type="button" onclick="abrirModal('gastronomia')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-none transition duration-200 uppercase tracking-wider text-xs">
                    Ver Opciones
                </button>
            </div>
        </article>

    </div>
</section>

<!-- Ventana Modal Desplegable -->
<div id="modalOverlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-gray-900 border border-gray-800 p-6 shadow-2xl max-w-lg w-full relative text-white">
        
        <button id="closeModalBtn" type="button" class="absolute top-4 right-4 text-gray-400 hover:text-white font-bold text-2xl transition">&times;</button>

        <h3 id="modalTitulo" class="text-xl font-black uppercase tracking-wider border-b border-gray-800 pb-3 mb-2 text-white"></h3>
        <p id="modalSubtitulo" class="text-gray-400 text-xs mb-4"></p>

        <!-- Contenedores generados dinámicamente -->
        <?php foreach ($experiencias_admin as $categoria =>$opciones): ?>
            <div id="categoria-<?= $categoria; ?>" class="categoria-contenido hidden space-y-3 max-h-72 overflow-y-auto pr-1">
                <?php if (!empty($opciones)): ?>
                    <?php foreach ($opciones as$opcion): ?>
                        <div class="p-4 bg-gray-800/80 border border-gray-700/60 flex flex-col justify-between gap-3 hover:border-blue-500 transition">
                            <div>
                                <h4 class="font-bold text-base text-blue-400"><?= htmlspecialchars($opcion['nombre']); ?></h4>
                                <p class="text-xs text-gray-300 mt-1 leading-relaxed"><?= htmlspecialchars($opcion['descripcion']); ?></p>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="seleccionarOpcion(<?= $opcion['id']; ?>, '<?= htmlspecialchars($opcion['nombre']); ?>')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition">
                                    Reservar / Seleccionar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-gray-500 italic p-4 text-center">No hay opciones disponibles en esta categoría por el momento.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="flex justify-end mt-5 border-t border-gray-800 pt-3">
            <button id="actionCloseBtn" type="button" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-5 py-2 text-xs font-semibold uppercase transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
const infoCategorias = {
    'planes': {
        titulo: 'Planes Especiales Disponibles',
        subtitulo: 'Selecciona la experiencia o paquete que deseas reservar:'
    },
    'actividades': {
        titulo: 'Actividades Propuestas',
        subtitulo: 'Selecciona la actividad que deseas realizar durante tu estadía:'
    },
    'gastronomia': {
        titulo: 'Experiencias Gastronómicas',
        subtitulo: 'Reserva tu menú especial o degustación para la velada:'
    }
};

const modalOverlay = document.getElementById('modalOverlay');
const closeModalBtn = document.getElementById('closeModalBtn');
const actionCloseBtn = document.getElementById('actionCloseBtn');

function abrirModal(categoria) {
    document.getElementById('modalTitulo').textContent = infoCategorias[categoria].titulo;
    document.getElementById('modalSubtitulo').textContent = infoCategorias[categoria].subtitulo;

    document.querySelectorAll('.categoria-contenido').forEach(el => el.classList.add('hidden'));

    const categoriaActiva = document.getElementById(`categoria-${categoria}`);
    if (categoriaActiva) {
        categoriaActiva.classList.remove('hidden');
    }

    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('flex');
}

function cerrarModal() {
    modalOverlay.classList.add('hidden');
    modalOverlay.classList.remove('flex');
}

closeModalBtn.addEventListener('click', cerrarModal);
actionCloseBtn.addEventListener('click', cerrarModal);

modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) cerrarModal();
});

function seleccionarOpcion(id, nombre) {
    alert(`Has seleccionado: ${nombre} (ID: ${id})`);
}
</script>