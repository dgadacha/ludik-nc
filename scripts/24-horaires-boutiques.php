<?php
/**
 * Horaires d'ouverture des deux boutiques.
 *
 * Le site d'origine annonçait 9h30 ; le client confirme 8h30, ce que dit aussi
 * la fiche Google Business des deux magasins. Le samedi conserve son horaire
 * propre, le dimanche reste fermé.
 *
 * PrestaShop 9 stocke les horaires en JSON, un tableau de sept entrées du
 * lundi au dimanche, chacune contenant les plages de la journée. Un tableau
 * vide vaut « fermé ».
 *
 * Le thème regroupe ensuite les jours qui partagent le même horaire :
 * « Lundi au vendredi 8h30 - 18h30 », « Samedi 9h - 18h30 ».
 *
 * Usage : docker exec ludik-ps php /scripts/24-horaires-boutiques.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();

/* lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche */
$horaires = [
    ['08:30 - 18:30'],
    ['08:30 - 18:30'],
    ['08:30 - 18:30'],
    ['08:30 - 18:30'],
    ['08:30 - 18:30'],
    ['09:00 - 18:30'],
    [],
];
$json = json_encode($horaires);

$lignes = $db->executeS(
    'SELECT s.id_store, sl.id_lang, sl.name
       FROM ' . _DB_PREFIX_ . 'store s
       JOIN ' . _DB_PREFIX_ . 'store_lang sl ON sl.id_store = s.id_store
      WHERE s.active = 1'
);

$vues = [];
foreach ($lignes ?: [] as $ligne) {
    $db->update(
        'store_lang',
        ['hours' => pSQL($json)],
        'id_store = ' . (int) $ligne['id_store'] . ' AND id_lang = ' . (int) $ligne['id_lang']
    );
    $vues[$ligne['name']] = true;
}

foreach (array_keys($vues) as $nom) {
    echo $nom . " : lundi au vendredi 8h30 - 18h30, samedi 9h - 18h30, dimanche fermé\n";
}

Tools::clearSmartyCache();
Tools::clearAllCache();
echo "caches purgés\n";
