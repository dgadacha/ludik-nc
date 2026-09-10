<?php
/**
 * Active ou désactive la combinaison CSS/JS de PrestaShop (option « CCC »).
 *
 * Le nom du fichier combiné ne dépend que de la liste des feuilles de style,
 * pas de leur contenu : pendant le travail sur le thème, le navigateur sert
 * indéfiniment l'ancienne version. On la coupe le temps des retouches, on la
 * remet avant la mise en production.
 *
 * Usage : php /scripts/dev-ccc.php on|off
 */
require_once '/var/www/html/config/config.inc.php';

$mode = $argv[1] ?? 'off';
$actif = $mode === 'on' ? '1' : '0';

foreach (['PS_CSS_THEME_CACHE', 'PS_JS_THEME_CACHE', 'PS_HTML_THEME_COMPRESSION'] as $cle) {
    Configuration::updateValue($cle, $actif);
}

echo 'combinaison CSS/JS : ' . ($actif === '1' ? 'activée' : 'désactivée') . PHP_EOL;
