<?php
/**
 * Coordonnées des deux boutiques.
 *
 * Le site d'origine ne donnait le téléphone que pour la Vallée des Colons.
 * Celui de Michel-Ange vient de la fiche Google Business de la boutique, qui
 * affiche « 1 Ave Michel-Ange, +687 30.31.01 ». Le numéro de la Vallée des
 * Colons y est confirmé : +687 46.55.46.
 *
 * Ces informations sont ensuite modifiables en back-office, dans
 * « Préférences > Magasins » : le thème les lit dans la fiche magasin.
 *
 * Usage : docker exec ludik-ps php /scripts/23-coordonnees-boutiques.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();

$coordonnees = [
    'Ludik Vallée des Colons' => ['phone' => '46 55 46', 'email' => 'librairie@ludik.nc'],
    'Ludik Michel-Ange' => ['phone' => '30 31 01', 'email' => 'contact@ludik.nc'],
];

$idLang = (int) Configuration::get('PS_LANG_DEFAULT');

foreach ($coordonnees as $nom => $champs) {
    $idStore = (int) $db->getValue(
        'SELECT id_store FROM ' . _DB_PREFIX_ . 'store_lang
          WHERE id_lang = ' . $idLang . ' AND name = "' . pSQL($nom) . '"'
    );
    if (!$idStore) {
        echo 'boutique introuvable : ' . $nom . PHP_EOL;
        continue;
    }

    $db->update('store', [
        'phone' => pSQL($champs['phone']),
        'email' => pSQL($champs['email']),
    ], 'id_store = ' . $idStore);

    echo $nom . ' : ' . $champs['phone'] . ' / ' . $champs['email'] . PHP_EOL;
}

Tools::clearSmartyCache();
Tools::clearAllCache();
echo "caches purgés\n";
