<?php
/**
 * Retrait des boutons de partage de la fiche produit.
 *
 * Le module ps_sharebuttons posait « Partager » et trois icônes (Facebook, X,
 * Pinterest) dans le bloc d'achat de la fiche, juste sous les arguments de
 * réassurance. Ludik n'a de présence que sur Facebook, et l'endroit est celui
 * où le visiteur décide d'acheter : les liens sortants y sont hors sujet.
 *
 * Le module est décroché de son emplacement, pas désinstallé : le rétablir se
 * fait en back-office, « Apparence > Positions », en le raccrochant à
 * displayProductAdditionalInfo.
 *
 * Usage : docker exec ludik-ps php /scripts/26-retirer-partage.php
 */
require_once '/var/www/html/config/config.inc.php';

$module = Module::getInstanceByName('ps_sharebuttons');
if (!Validate::isLoadedObject($module)) {
    echo "module ps_sharebuttons absent : rien à faire\n";

    return;
}

$db = Db::getInstance();
$emplacements = $db->executeS(
    'SELECT h.id_hook, h.name
     FROM ' . _DB_PREFIX_ . 'hook_module hm
     JOIN ' . _DB_PREFIX_ . 'hook h ON h.id_hook = hm.id_hook
     WHERE hm.id_module = ' . (int) $module->id
);

if (!$emplacements) {
    echo "ps_sharebuttons n'est accroché à aucun emplacement\n";
} else {
    foreach ($emplacements as $emplacement) {
        $module->unregisterHook((int) $emplacement['id_hook']);
        echo 'décroché de ' . $emplacement['name'] . PHP_EOL;
    }
}

Tools::clearAllCache();
echo "caches purgés\n";
