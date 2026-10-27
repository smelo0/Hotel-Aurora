<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>HOTEL AURORA - Admin Pro</title>
    
    <link rel="stylesheet" href="../../assets/css/estilos_base.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏨</text></svg>">

    <script>
    // Se declara globalmente en el HEAD para que esté lista antes de que renderice el sidebar
    window.navegar = function(sec, btn) {
        if (!sec) return;

        // Ocultar todas las secciones
        document.querySelectorAll('.seccion-contenido').forEach(s => s.classList.add('hidden'));

        // Mostrar la sección seleccionada
        const targetId = sec.startsWith('sec-') ? sec : 'sec-' + sec;
        const seccion = document.getElementById(targetId) || document.getElementById(sec);

        if (seccion) {
            seccion.classList.remove('hidden');
        } else if (typeof sec === 'string' && (sec.includes('.php') || sec.includes('/'))) {
            window.location.href = sec;
        }

        // Marcar botón activo
        if (btn) {
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active-nav', 'hover:bg-slate-50'));
            btn.classList.add('active-nav');
        }
    };
    </script>
   
    <link rel="stylesheet" href="CSS/estilos_ad.css?v=1">
</head>
