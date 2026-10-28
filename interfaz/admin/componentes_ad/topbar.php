<!-- Modificación: Se mantiene la cabecera del administrador sin botón duplicado de salida. -->
<header class="flex-1 min-w-full flex items-center justify-between px-12 h-24 bg-[#f8fbfb]/90 backdrop-blur-md sticky top-0 z-30 border-b border-primary/10 shadow-sm">
    <div>
        <h2 class="font-headline font-black text-primary text-2xl tracking-tight">
            Hola, <?php echo explode(' ', $_SESSION['emp_auth']['nombre_usuario'])[0]; ?>
        </h2>
        <div class="flex items-center gap-2 mt-0.5">
            <p id="fechaHoy" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"></p>
            <span class="text-primary/20">•</span>
            <p class="text-[10px] font-bold text-secondary uppercase tracking-widest">Panel Admin</p>
        </div>
        <!-- boton de traduccion gon google -->
        
    </div>

    <div class="CLI">
        <p>CLI</p>
    </div>

    <style>
    @keyframes neonFlickerFull {
        0%, 100% {
            /* Estado encendido (brillo total) */
            background-image: radial-gradient(circle, #ff3344 20%, #cc0022 90%);
            color: #ffffff;
            text-shadow: 
                0 0 5px #ffffff,
                0 0 10px #ff1a33,
                0 0 20px #ff1a33,
                0 0 40px #ff1a33;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.5),
                inset 0 0 15px rgba(0, 0, 0, 0.8),
                0 0 20px rgba(255, 26, 51, 0.6);
        }
        50% {
            /* fuente */ 
            color: #FFBDBD; /* Color de texto apagado/oscuro */
            text-shadow: none;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.5),
                inset 0 0 15px rgba(0, 0, 0, 0.8);
        }
    }

    .CLI {
        width: 150px;
        height: 50px;
        margin: 50px auto;
        display: flex;
        align-items: center;
        justify-content: center;

        background-color: #2b2b2b;
        border: 2px solid #1f1f1f;
        border-radius: 6px;

        font-family: 'Arial Black', sans-serif;
        font-size: 1rem;
        font-weight: 900;
        letter-spacing: 4px;

        /* Aplica la animación de parpadeo */
        animation: neonFlickerFull 5s infinite;
    }
    </style>

    <?php include __DIR__ . '/../../../includes/translate.php' ?>
    <!-- Modificación: Se eliminó botón duplicado de salida en el header; el cierre de sesión queda solo en el sidebar. -->
</header>
