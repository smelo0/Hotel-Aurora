<!-- Habitaciones agrupadas por tipo: el huésped elige tipo + cantidad y se crea una sola reserva. -->
<div id="vista-resultados" class="hidden w-full px-4 md:px-8 pb-20 fade-in pt-6">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <button onclick="mostrarVista('vista-landing')" class="text-sm font-bold text-slate-500 flex items-center gap-1 hover:text-primary mb-4">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Volver a buscar
            </button>
            <h2 class="text-3xl font-headline font-black text-primary">Habitaciones Disponibles</h2>
            <p class="text-sm text-slate-500 mt-1">Selecciona el tipo de habitación y cuántas habitaciones necesitas.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 w-full" id="gridTiposHabitacion">
        <?php
        require_once __DIR__ . '/../../../configuracion/conexion.php';
        require_once __DIR__ . '/../../../src/Usuario/PortalRepository.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $repository = new \App\Usuario\PortalRepository();
        $catalogo = $repository->fetchRoomCatalog($conexion);
        $tipos = [];

        foreach ($catalogo as $habitacion) {
            if (($habitacion['est_hab'] ?? '') === 'Mantenimiento' || ($habitacion['est_hab'] ?? '') === 'Sucia') {
                continue;
            }

            $tipo = trim((string) ($habitacion['tipo_hab'] ?? ''));
            if ($tipo === '') {
                $tipo = 'Habitación';
            }

            if (!isset($tipos[$tipo])) {
                $tipos[$tipo] = [
                    'tipo' => $tipo,
                    'precio' => (float) $habitacion['pre_hab'],
                    'ids' => [],
                    'total' => 0,
                    'descripcion' => (string) $habitacion['obs_hab'],
                    'imagen' => $habitacion['img_hab'] ?? null,
                    'caracteristicas' => is_array($habitacion['car_hab'] ?? null) ? $habitacion['car_hab'] : [],
                ];
            }

            $tipos[$tipo]['ids'][] = (int) $habitacion['cod_hab'];
            $tipos[$tipo]['total']++;
        }

        $imagenes = [
            'Suite' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&q=80&w=800',
            'Doble' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=800',
            'Sencilla' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=800'
        ];
        $caracteristicasDefault = [
            'Suite' => ['King', 'Balcón', 'Wi-Fi'],
            'Doble' => ['Queen', 'Escritorio', 'Wi-Fi'],
            'Sencilla' => ['Twin', 'Baño Privado', 'Wi-Fi']
        ];

        if ($tipos !== []):
            foreach ($tipos as $tipo => $grupo):
                $idsJson = htmlspecialchars(
                    json_encode($grupo['ids'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $tipoSeguro = htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8');
                $imagen = !empty($grupo['imagen']) ? $grupo['imagen'] : ($imagenes[$tipo] ?? $imagenes['Doble']);
                $features = $grupo['caracteristicas'] ?: ($caracteristicasDefault[$tipo] ?? ['Wi-Fi', 'Baño privado']);
                $precio = number_format((float) $grupo['precio'], 0, ',', '.');
        ?>
        <article
            class="room-type-card w-full bg-white rounded-3xl border border-primary/5 overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-300 flex flex-col"
            data-room-type="<?php echo $tipoSeguro; ?>"
            data-room-ids='<?php echo $idsJson; ?>'
            data-room-total="<?php echo (int) $grupo['total']; ?>"
        >
            <div class="relative h-64 overflow-hidden">
                <img src="<?php echo htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover" alt="<?php echo $tipoSeguro; ?>">
                <div class="absolute top-4 right-4 px-3 py-1.5 rounded-full bg-white/90 backdrop-blur text-[10px] font-black uppercase tracking-widest text-primary">
                    <span data-room-available-count><?php echo (int) $grupo['total']; ?></span> disponibles
                </div>
            </div>

            <div class="p-7 flex flex-col flex-1">
                <h3 class="text-2xl font-black text-primary mb-1"><?php echo $tipoSeguro; ?></h3>
                <p class="text-xs font-bold text-accent mb-3 uppercase">Tipo de habitación</p>
                <p class="text-sm text-slate-500 mb-6 leading-relaxed"><?php echo htmlspecialchars($grupo['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="flex flex-wrap gap-2 mb-6">
                    <?php foreach (array_slice($features, 0, 5) as $feature): ?>
                        <span class="flex items-center gap-1.5 text-xs font-bold text-primary bg-primary/5 px-3 py-1.5 rounded-lg">
                            <span class="material-symbols-outlined text-[16px]">check</span>
                            <?php echo htmlspecialchars((string) $feature, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="bg-green-50/50 border border-green-100 p-4 rounded-xl mb-6">
                    <p class="text-xs font-bold text-green-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Cancelación gratuita incluida.
                    </p>
                    <p class="text-xs font-bold text-green-700 flex items-center gap-2 mt-2">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Desayuno buffet incluido.
                    </p>
                </div>

                <div class="mt-auto border-t border-slate-100 pt-5">
                    <div class="flex items-end justify-between gap-4 mb-5">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Precio por habitación / noche</p>
                            <p class="text-3xl font-black text-primary leading-none">$<?php echo $precio; ?><span class="text-xs text-slate-400 font-bold ml-1">COP</span></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-3">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest" for="cantidad-<?php echo md5($tipo); ?>">Cantidad</label>
                        <select
                            id="cantidad-<?php echo md5($tipo); ?>"
                            class="room-quantity-selector flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-primary outline-none"
                            data-room-quantity
                            data-max="<?php echo (int) $grupo['total']; ?>"
                        >
                            <?php for ($cantidad = 1; $cantidad <= min(20, (int) $grupo['total']); $cantidad++): ?>
                                <option value="<?php echo $cantidad; ?>"><?php echo $cantidad; ?> habitación<?php echo $cantidad === 1 ? '' : 'es'; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button
                        type="button"
                        data-room-select
                        onclick="openBookingModalType(this)"
                        class="w-full bg-primary text-white px-6 py-3.5 rounded-xl font-black text-sm hover:bg-secondary transition-all shadow-xl hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Elegir <?php echo $tipoSeguro; ?>
                    </button>
                    <p data-room-availability class="hidden mt-3 text-xs font-bold text-amber-600"></p>
                </div>
            </div>
        </article>
        <?php
            endforeach;
        else:
        ?>
            <div class="col-span-full text-center py-12">
                <p class="text-slate-500 text-lg">No hay habitaciones disponibles en este momento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
