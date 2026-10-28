    <?php
    $sessionFileWeb = __DIR__ . "/../CLI/session.json";

    if (file_exists($sessionFileWeb)) {
        echo 
        '<div id="CLI" class="CLIAnimation">
            <p>CLI</p>
        </div>';
    } else {
        echo 
        '<div id="CLI" class="CLIClean">
            <p>CLI</p>
        </div>';
    };

    ?>
    
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
        30% {
            /* Estado apagado (la fuente se oscurece y pierde el brillo) */
            background-image: radial-gradient(circle, #ff1a35f6 20%, #ff1a35f6 90%);
            color: #ffd0d0; /* Color de texto apagado/oscuro */
            text-shadow: none;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.5),
                inset 0 0 15px rgba(0, 0, 0, 0.8);
        }
        50% {
            /* Estado apagado (la fuente se oscurece y pierde el brillo) */
            background-image: radial-gradient(circle, #ff1a35f2 20%, #ff1a35f0 90%);
            color: #e8bdbd; /* Color de texto apagado/oscuro */
            text-shadow: none;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.5),
                inset 0 0 15px rgba(0, 0, 0, 0.8);
        }
    }

    .CLIAnimation {
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
        animation: neonFlickerFull 2s ease-in-out infinite;
    }

    .CLIClean {
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
    };

    </style>

    
