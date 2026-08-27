<nav class="bg-white px-6 md:px-10 h-20 flex items-center justify-between fixed top-0 w-full z-50 border-b border-slate-200 transition-all shadow-sm">
    <div class="flex items-center gap-2 cursor-pointer" onclick="mostrarVista('vista-landing')">
        <span class="material-symbols-outlined text-primary text-3xl">hotel</span>
        <span class="font-headline font-black text-primary text-xl tracking-tighter">AURORA</span>
    </div>
    
    <div class="hidden md:flex h-full items-center gap-8">
        <button onclick="mostrarVista('vista-habitaciones')" class="text-sm font-bold text-slate-500 hover:text-primary transition-colors">Habitaciones</button>
        <button onclick="mostrarVista('vista-experiencias')" class="text-sm font-bold text-slate-500 hover:text-primary transition-colors">Experiencias</button>
        <button onclick="mostrarVista('vista-galeria')" class="text-sm font-bold text-slate-500 hover:text-primary transition-colors">Galería</button>
    </div>

    <div class="flex items-center gap-4">
        <?php if(isset($_SESSION['user_auth']['id_usuario'])): ?>
            <span class="hidden md:block text-sm font-bold text-slate-600">Hola, <?php echo htmlspecialchars($_SESSION['user_auth']['nombre_usuario']); ?></span>
            <button type="button" onclick="abrirModalLogoutUsuario()" class="hidden md:flex text-sm font-bold text-red-500 hover:text-red-700 transition-colors items-center gap-1">
                <span class="material-symbols-outlined text-[18px]">logout</span> Salir
            </button>
        <?php else: ?>
            <a href="interfaz/loggins/index_usu.php?vista=registro" class="hidden md:block text-sm font-bold text-primary hover:text-secondary transition-colors">Registrarse</a>
        <?php endif; ?>

        <button onclick="mostrarVista('vista-landing'); setTimeout(() => document.getElementById('motor-busqueda').scrollIntoView({behavior: 'smooth'}), 100);" class="bg-primary text-white px-6 py-2.5 rounded-full font-black text-xs uppercase tracking-widest hover:bg-secondary transition-all shadow-lg hover:shadow-xl">
            Reservar
        </button>
    </div>
</nav>
