<?php
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$isSubdir = str_contains($scriptName, '/interfaz/');
$translateAssetsPath = $isSubdir ? '../../assets' : 'assets';
?>
<link rel="stylesheet" href="<?php echo $translateAssetsPath; ?>/css/translate.css?v=5">

<div class="language-switcher notranslate" translate="no">
    <svg class="language-switcher__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"></path>
    </svg>
    <label id="language-select-label" for="language-select" class="notranslate" translate="no">Idioma</label>
    <select id="language-select" class="notranslate" translate="no" aria-labelledby="language-select-label" data-action="change-language">
        <option value="es" class="notranslate" translate="no">Español</option>
        <option value="en" class="notranslate" translate="no">English</option>
        <option value="pt" class="notranslate" translate="no">Português</option>
        <option value="fr" class="notranslate" translate="no">Français</option>
        <option value="it" class="notranslate" translate="no">Italiano</option>
    </select>
</div>

<div id="google_translate_element" class="language-translate-target notranslate" translate="no"></div>
<script src="<?php echo $translateAssetsPath; ?>/js/translate.js?v=5"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
