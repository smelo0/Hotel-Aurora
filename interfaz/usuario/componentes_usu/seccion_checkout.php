<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobación de autenticación
$usuario_autenticado = isset($_SESSION['usuario_id']) || isset($_SESSION['id_usuario']);

// Si el usuario ya está logueado, autocompletamos sus datos
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? '';
$email_usuario = $_SESSION['email'] ?? $_SESSION['correo'] ?? '';
$telefono_usuario = $_SESSION['telefono'] ?? '';
?>

<div id="vista-checkout" class="max-w-6xl mx-auto px-6 pb-20 fade-in pt-10">
    <button type="button" onclick="mostrarVista('vista-resultados')" class="text-sm font-bold text-slate-500 flex items-center gap-1 hover:text-primary mb-8 cursor-pointer">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span> Volver a selección
    </button>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 space-y-8">
            <div>
                <h2 class="text-3xl font-headline font-black text-primary">Detalles del Huésped</h2>
                <p class="text-sm text-slate-500 mt-1">Estás a un paso de tu descanso ideal. Completa tu reserva.</p>
            </div>

            <!-- Formulario con atributos 'name' configurados para PHP -->
            <form id="form-checkout" class="space-y-8" onsubmit="procesarPagoFinal(event)">
                
                <!-- ID de la habitación seleccionada (oculto) -->
                <input type="hidden" id="id_habitacion" name="id_habitacion" value="1">
                <input type="hidden" id="fecha_entrada" name="fecha_entrada" value="<?php echo $_GET['checkin'] ?? date('Y-m-d'); ?>">
                <input type="hidden" id="fecha_salida" name="fecha_salida" value="<?php echo $_GET['checkout'] ?? date('Y-m-d', strtotime('+3 days')); ?>">

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-primary/5">
                    <h3 class="text-lg font-black text-primary mb-6 border-b border-slate-100 pb-4">1. Información Personal</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nombre Completo *</label>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre_usuario); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:border-primary mt-2">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Correo Electrónico *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email_usuario); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:border-primary mt-2">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Teléfono *</label>
                            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($telefono_usuario); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:border-primary mt-2">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-primary/5">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                        <h3 class="text-lg font-black text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined">lock</span> 2. Pago Seguro
                        </h3>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Número de Tarjeta *</label>
                            <input type="text" name="numero_tarjeta" maxlength="19" placeholder="0000 0000 0000 0000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:border-primary mt-2 font-mono tracking-widest">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Caducidad *</label>
                                <input type="text" name="caducidad" maxlength="5" placeholder="MM/AA" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:border-primary mt-2 text-center tracking-widest">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">CVC *</label>
                                <input type="password" name="cvc" maxlength="4" placeholder="123" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:border-primary mt-2 text-center tracking-widest">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" id="btn-finalizar" class="w-full bg-primary text-white py-5 rounded-2xl font-black text-lg uppercase tracking-widest hover:bg-secondary transition-all shadow-2xl hover:shadow-primary/40 flex justify-center items-center gap-2 cursor-pointer">
                    Confirmar y Pagar
                </button>
            </form>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl shadow-xl border border-primary/10 overflow-hidden sticky top-28">
                <div class="bg-primary p-6 text-white text-center">
                    <h3 class="font-headline font-black text-xl">Tu Estancia</h3>
                </div>
                <div class="p-6">
                    <h4 id="checkout-room-name" class="font-black text-primary text-lg mb-6">Suite Deluxe Mar</h4>
                    
                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div class="flex justify-between text-sm mb-3 text-slate-500">
                            <span class="font-bold">Precio (3 noches)</span>
                            <span id="checkout-subtotal" class="font-black text-slate-700">$300.000</span>
                        </div>
                        <div class="flex justify-between text-sm mb-6 text-slate-500 border-b border-slate-200 pb-4">
                            <span class="font-bold">Impuestos (19%)</span>
                            <span id="checkout-taxes" class="font-black text-slate-700">$57.000</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="font-black text-slate-800 text-sm uppercase tracking-widest">Total a Pagar</span>
                            <span id="checkout-total" class="text-3xl font-black text-primary">$357.000</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Exponer la constante global de login
const IS_LOGGED_IN = <?php echo $usuario_autenticado ? 'true' : 'false'; ?>;

function procesarPagoFinal(e) {
    e.preventDefault();

    // 1. Validar inicio de sesión
    if (!IS_LOGGED_IN) {
        alert("Debes iniciar sesión o registrarte para completar el pago.");
        window.location.href = "../loggins/index_usu.php";
        return;
    }

    const btn = document.getElementById('btn-finalizar');
    btn.disabled = true;
    btn.innerHTML = "Procesando pago...";

    const formData = new FormData(document.getElementById('form-checkout'));

    // 2. Enviar datos al controlador mediante Fetch API / AJAX
    fetch('../../controladores/guardar_reserva.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.exito) {
            alert("¡Reserva y pago realizados con éxito!");
            window.location.href = "interfaz_usu.php?estado=reserva_exitosa";
        } else {
            alert("Error al procesar la reserva: " + (data.mensaje || "inténtalo de nuevo."));
            btn.disabled = false;
            btn.innerHTML = "Confirmar y Pagar";
        }
    })
    .catch(error => {
        console.error("Error en el servidor:", error);
        alert("Ocurrió un error de conexión al procesar el pago.");
        btn.disabled = false;
        btn.innerHTML = "Confirmar y Pagar";
    });
}

function mostrarVista(vistaId) {
    document.querySelectorAll('.fade-in').forEach(el => el.classList.add('hidden'));
    const vistaTarget = document.getElementById(vistaId);
    if(vistaTarget) vistaTarget.classList.remove('hidden');
}
</script>







            