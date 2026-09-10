<?php
/**
 * Incrémente la version des fichiers CSS/JS combinés.
 *
 * Le nom du fichier produit par l'option « CCC » de PrestaShop est
 *   theme-<empreinte de la liste des feuilles><PS_CCCCSS_VERSION>.css
 * L'empreinte ne dépend que de la liste des fichiers, pas de leur contenu :
 * après une retouche du thème, le nom restait identique et le navigateur
 * continuait de servir l'ancienne feuille. C'est ce que fait le bouton
 * « Vider le cache » du back-office ; on le reproduit en ligne de commande.
 *
 * Usage : php /scripts/cc.php
 */
require_once '/var/www/html/config/config.inc.php';

foreach (['PS_CCCCSS_VERSION', 'PS_CCCJS_VERSION'] as $cle) {
    $version = (int) Configuration::get($cle);
    Configuration::updateValue($cle, $version + 1);
    echo $cle . ' : ' . $version . ' -> ' . ($version + 1) . PHP_EOL;
}
