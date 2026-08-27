<!-- INICIO: Renderizado dinámico de habitaciones disponibles desde SQL -->

<div id="vista-resultados" class="hidden w-full px-4 md:px-8 pb-20 fade-in pt-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 w-full">
        <div>
            <button onclick="mostrarVista('vista-landing')" class="text-sm font-bold text-slate-500 flex items-center gap-1 hover:text-primary mb-4"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Volver a buscar</button>
            <h2 class="text-3xl font-headline font-black text-primary">Habitaciones Disponibles</h2>
            <p class="text-sm text-slate-500 mt-1">Fechas Seleccionadas | 2 Adultos</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full max-w-none">
        <?php
        // CONSULTA SQL: Extraer TODAS las habitaciones con estado 'Disponible' desde la base de datos
        require __DIR__ . '/../../configuracion/conexion.php';
        
        $sqlHabitaciones = "SELECT cod_hab, num_hab, tipo_hab, pre_hab, est_hab, obs_hab FROM habitacion WHERE est_hab = 'Disponible' ORDER BY num_hab ASC";
        /**@var mysqli $conexion */
        $resultadoHabitaciones = $conexion->query($sqlHabitaciones);

        
        
        // Verificar si la consulta fue exitosa
        if ($resultadoHabitaciones && $resultadoHabitaciones->num_rows > 0) {
            // Bucle FOREACH: Iterar sobre cada habitación disponible desde la BD
            while ($habitacion = $resultadoHabitaciones->fetch_assoc()) {
                $id_hab = htmlspecialchars($habitacion['cod_hab']);
                $numero_hab = htmlspecialchars($habitacion['num_hab']);
                $tipo_hab = htmlspecialchars($habitacion['tipo_hab']);
                $precio_hab = number_format((float)$habitacion['pre_hab'], 2);
                $obs_hab = htmlspecialchars($habitacion['obs_hab'] ?? 'Habitación premium con todas las comodidades');
                
                // Determinar imagen según tipo de habitación
                $imagenes = [
                    'Suite' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&q=80&w=800',
                    'Doble' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=800',
                    'Sencilla' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=800'
                ];
                $imagen = $imagenes[$tipo_hab] ?? $imagenes['Doble'];
                
                // Determinar características según tipo de habitación
                $caracteristicas = [
                    'Suite' => ['King', 'Balcón', 'Wi-Fi'],
                    'Doble' => ['Queen', 'Escritorio', 'Wi-Fi'],
                    'Sencilla' => ['Twin', 'Baño Privado', 'Wi-Fi']
                ];
                $features = $caracteristicas[$tipo_hab] ?? ['Wi-Fi', 'Aire AC'];
        ?>
        
        <!-- TARJETA DE HABITACIÓN PREMIUM RENDERIZADA DINÁMICAMENTE DESDE LA BD -->
        <div class="w-full h-full bg-white rounded-3xl border border-primary/5 overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-300 group flex flex-col">
            <div class="relative h-72 overflow-hidden">
                <img src="<?php echo $imagen; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="<?php echo $tipo_hab; ?>">
            </div>
            <div class="p-8 flex flex-col flex-1">
                <h3 class="text-2xl font-black text-primary mb-1">Habitación <?php echo $numero_hab; ?></h3>
                <p class="text-xs font-bold text-accent mb-3"><?php echo strtoupper($tipo_hab); ?></p>
                <p class="text-sm text-slate-500 mb-6 leading-relaxed"><?php echo $obs_hab; ?></p>
                
                <div class="flex flex-wrap gap-4 mb-8">
                    <?php foreach ($features as $feature): ?>
                        <span class="flex items-center gap-1.5 text-xs font-bold text-primary bg-primary/5 px-3 py-1.5 rounded-lg">
                            <span class="material-symbols-outlined text-[16px]">check</span> <?php echo $feature; ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="bg-green-50/50 border border-green-100 p-4 rounded-xl mb-6">
                    <p class="text-xs font-bold text-green-700 flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">check_circle</span> Cancelación Gratuita incluida.</p>
                    <p class="text-xs font-bold text-green-700 flex items-center gap-2 mt-2"><span class="material-symbols-outlined text-[16px]">check_circle</span> Desayuno Buffet Incluido.</p>
                </div>

                <div class="mt-auto flex items-end justify-between pt-6 border-t border-slate-100">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Precio por Noche</p>
                        <p class="text-4xl font-black text-primary leading-none">$<?php echo $precio_hab; ?><span class="text-sm text-slate-400 font-bold ml-1">USD</span></p>
                    </div>
                    <button onclick="iniciarCheckout('Habitación <?php echo $numero_hab; ?> - <?php echo $tipo_hab; ?>', <?php echo (float)$habitacion['pre_hab']; ?>)" class="bg-primary text-white px-8 py-3.5 rounded-xl font-black text-sm hover:bg-secondary transition-all shadow-xl hover:-translate-y-1">Elegir</button>
                </div>
            </div>
        </div>
        
        <?php
            } // Fin del bucle while
        } else {
            // Si no hay habitaciones disponibles mostrar mensaje
            echo '<div class="col-span-full text-center py-12"><p class="text-slate-500 text-lg">No hay habitaciones disponibles en este momento.</p></div>';
        }
        

        /**@var mysqli $conexion
         *
         */
        $conexion->close();

        ?>
    </div>
</div>
<!-- FIN: Renderizado dinámico de habitaciones disponibles desde SQL -->