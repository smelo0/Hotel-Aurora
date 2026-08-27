<div id="vista-habitaciones" class="hidden max-w-6xl mx-auto px-6 pb-20 fade-in pt-10">
    <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-headline font-black text-primary">Nuestras Colecciones</h2>
        <p class="text-slate-500 mt-4 max-w-2xl mx-auto text-lg">Diseñadas meticulosamente para fusionar el confort moderno con la serenidad del entorno costero.</p>
    </div>

    <div class="space-y-16">
        <div class="flex flex-col md:flex-row gap-10 items-center bg-white p-6 md:p-10 rounded-[3rem] shadow-sm border border-primary/5 rotate-3 transition-transform hover:rotate-0">
            <div class="w-full md:w-1/2 h-80 rounded-[2rem] overflow-hidden shadow-xl">
                <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" alt="Suite Ejecutiva">
            </div>
            <div class="w-full md:w-1/2 space-y-6">
                <span class="text-[10px] font-black text-accent bg-accent/10 px-4 py-1.5 rounded-full uppercase tracking-widest">Nivel Premium</span>
                <h3 class="text-4xl font-black text-primary">Suite Ejecutiva Oceánica</h3>
                <p class="text-slate-500 leading-relaxed">Experimente el máximo lujo en nuestra suite más solicitada. Cuenta con ventanales de piso a techo, una sala de estar independiente y un balcón privado para disfrutar de los atardeceres.</p>
                <ul class="grid grid-cols-2 gap-4 text-sm font-bold text-slate-600">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">king_bed</span> Cama King Size</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">hot_tub</span> Jacuzzi Privado</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">room_service</span> Room Service 24/7</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">wifi</span> Wi-Fi Alta Velocidad</li>
                </ul>
                <button onclick="mostrarVista('vista-landing'); setTimeout(() => document.getElementById('motor-busqueda').scrollIntoView({behavior: 'smooth'}), 100);" class="bg-primary text-white px-10 py-4 rounded-xl font-black text-sm hover:bg-secondary transition-all shadow-xl hover:-translate-y-1 mt-4 inline-block">Ver Disponibilidad</button>
            </div>
        </div>

        <div class="flex flex-col md:flex-row-reverse gap-10 items-center bg-white p-6 md:p-10 rounded-[3rem] shadow-sm border border-primary/5 rotate-6 transition-transform hover:rotate-0">
            <div class="w-full md:w-1/2 h-80 rounded-[2rem] overflow-hidden shadow-xl">
                <img src="https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" alt="Habitación Premium">
            </div>
            <div class="w-full md:w-1/2 space-y-6">
                <span class="text-[10px] font-black text-slate-500 bg-slate-100 px-4 py-1.5 rounded-full uppercase tracking-widest">Estándar Superior</span>
                <h3 class="text-4xl font-black text-primary">Habitación Premium Queen</h3>
                <p class="text-slate-500 leading-relaxed">Diseñada para garantizar el máximo confort. Ideal para viajeros de negocios o parejas que buscan una estancia elegante y funcional con tecnología de punta.</p>
                <ul class="grid grid-cols-2 gap-4 text-sm font-bold text-slate-600">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">bed</span> Cama Queen Size</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">tv</span> Smart TV 55"</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">desk</span> Área de Trabajo</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg">coffee_maker</span> Máquina Nespresso</li>
                </ul>
                <button onclick="mostrarVista('vista-landing'); setTimeout(() => document.getElementById('motor-busqueda').scrollIntoView({behavior: 'smooth'}), 100);" class="border-2 border-primary text-primary px-10 py-4 rounded-xl font-black text-sm hover:bg-primary hover:text-white transition-all shadow-md mt-4 inline-block">Ver Disponibilidad</button>
            </div>
        </div>
    </div>
</div>