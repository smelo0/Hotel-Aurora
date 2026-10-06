<section id="sec-configuracion" class="seccion-contenido hidden">
    
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm max-w-4xl">
        
        <h3 class="text-2xl font-black text-primary tracking-tight mb-2">Seguridad y Control de Accesos</h3>
        <p class="text-xs text-slate-400 mb-8">Administra los permisos específicos para cada rol del sistema.</p>
        
        <div class="space-y-6">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h4 class="font-black text-amber-900">Gestión de permisos</h4>
                <p class="mt-1 text-xs text-amber-800">Los permisos se administran por rol y se verifican en el servidor.</p>
                <?php if (usuario_tiene_permiso($conexion, 'roles.ver')): ?>
                    <button type="button" onclick="navegar('roles', document.querySelector('[data-permiso=&quot;roles.ver&quot;]'))" class="mt-3 rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white hover:brightness-110">
                        Abrir gestión de roles
                    </button>
                <?php endif; ?>
            </div>

            <div class="p-6 border border-slate-200 rounded-xl bg-slate-50">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h4 class="font-black text-heading">Cuenta de acceso</h4>
                        <p class="text-xs text-slate-500 mt-1">Cambia el correo asociado a tu cuenta de administrador o empleado.</p>
                    </div>
                </div>

                <?php $correoActual = $_SESSION['emp_auth']['correo_usuario'] ?? ''; ?>
                <?php if (isset($_GET['success']) && $_GET['success'] === 'correo_actualizado'): ?>
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">Correo actualizado correctamente.</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'correo_duplicado'): ?>
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-600">Este correo ya está registrado en otra cuenta.</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'correo_invalido'): ?>
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-600">Ingresa un correo válido.</div>
                <?php endif; ?>

                <form method="POST" action="../../controladores/actualizar_correo.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400 mb-2">Correo actual</label>
                        <input type="text" value="<?php echo htmlspecialchars($correoActual, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700" disabled>
                    </div>

                    <div>
                        <label for="nuevo_correo" class="block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400 mb-2">Nuevo correo</label>
                        <input id="nuevo_correo" name="nuevo_correo" type="email" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary" placeholder="nuevo@hotelaurora.com">
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-black text-white shadow hover:brightness-110 transition-all">
                        Guardar correo
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</section>