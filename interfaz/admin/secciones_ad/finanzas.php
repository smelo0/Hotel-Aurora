<section id="sec-finanzas" class="seccion-contenido hidden">
    
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        
        <h3 class="text-2xl font-black text-primary tracking-tight mb-8">Módulo de Finanzas</h3>
        
        <table class="w-full text-left">
            
            <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                <tr>
                    <th class="p-4">Folio</th>
                    <th class="p-4">Huésped</th>
                    <th class="p-4">Monto Final</th>
                    <th class="p-4 text-right">Acción</th> </tr>
            </thead>
            
            <tbody class="divide-y divide-slate-100 text-sm font-semibold">
                
                <tr>
                    <td class="p-4 text-slate-500">#FX-1092</td>
                    <td class="p-4">Familia Gómez (Check-out)</td>
                    <td class="p-4 text-primary font-black">$850.00</td>
                    
                    <td class="p-4 text-right">
                        <button onclick="generarFactura(this)" class="bg-primary text-white px-6 py-2 rounded-lg text-[10px] uppercase tracking-widest font-black transition-all hover:bg-heading flex items-center gap-2 ml-auto">
                            <span class="material-symbols-outlined text-sm">receipt_long</span> Generar Factura
                        </button>
                    </td>
                </tr>
                
                <tr>
                    <td class="p-4 text-slate-500">#FX-1093</td>
                    <td class="p-4">Empresa TechCorp</td>
                    <td class="p-4 text-primary font-black">$3,200.00</td>
                    
                    <td class="p-4 text-right">
                        <button onclick="generarFactura(this)" class="bg-primary text-white px-6 py-2 rounded-lg text-[10px] uppercase tracking-widest font-black transition-all hover:bg-heading flex items-center gap-2 ml-auto">
                            <span class="material-symbols-outlined text-sm">receipt_long</span> Generar Factura
                        </button>
                    </td>
                </tr>
                
            </tbody>
        </table>
        
    </div>
</section>