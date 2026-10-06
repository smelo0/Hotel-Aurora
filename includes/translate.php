<?php
$translateAssetsPath = str_contains(
    (string) ($_SERVER['SCRIPT_NAME'] ?? ''),
    '/interfaz_usu.php'
) ? 'assets' : '../../assets';
?>
<link rel="stylesheet" href="<?php echo $translateAssetsPath; ?>/css/translate.css?v=1">

<div class="language-switcher">
    <svg class="language-switcher__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"></path>
    </svg>
    <label id="language-select-label" for="language-select">Idioma</label>
    <select id="language-select" aria-labelledby="language-select-label" data-action="change-language">
        <option value="es">Español</option>
        <option value="en">English</option>
        <option value="pt">Português</option>
        <option value="fr">Français</option>
        <option value="it">Italiano</option>
    </select>
</div>

<div id="google_translate_element" class="language-translate-target"></div>
<script src="<?php echo $translateAssetsPath; ?>/js/translate.js?v=1"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
