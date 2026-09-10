<?php
/**
 * Sections de produits de la page d'accueil.
 *
 * La page en affichait quatre : nouveautés, produits vedettes, meilleures
 * ventes et promotions. Les quatre puisent dans le même catalogue et se
 * répétaient, avec dix produits serrés par ligne. On garde deux sections, un
 * objectif chacune, une rangée défilante de dix produits :
 *
 *   1. Nouveautés      (ps_newproducts)
 *   2. Meilleures ventes (ps_bestsellers)
 *
 * Les produits vedettes et les promotions sont décrochés de la page
 * d'accueil. La page « Prix réduits » a ensuite été retirée du site
 * (scripts/25-retirer-promotions.php) : le catalogue ne porte aucune remise.
 *
 * Usage : docker exec ludik-ps php /scripts/18-accueil-sections.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
$idHome = (int) Hook::getIdByName('displayHome');

/* 1. Dix produits par section : la rangée en montre cinq à la fois et les
      flèches font défiler le reste. */
foreach ([
    'NEW_PRODUCTS_NBR' => 10,
    'PS_BLOCK_BESTSELLERS_TO_DISPLAY' => 10,
    'CATEGORYPRODUCTS_DISPLAY_PRODUCTS' => 10,
    'PRODUCTS_VIEWED_NBR' => 10,
] as $cle => $valeur) {
    Configuration::updateValue($cle, $valeur);
}
echo "dix produits par section, cinq visibles\n";

/* 2. Décrochage des sections redondantes de la page d'accueil */
foreach (['ps_featuredproducts', 'ps_specials'] as $nom) {
    $module = Module::getInstanceByName($nom);
    if (!$module || !$module->id) {
        continue;
    }
    $supprimees = $db->delete(
        'hook_module',
        'id_module = ' . (int) $module->id . ' AND id_hook = ' . $idHome . ' AND id_shop = ' . $idShop
    );
    echo $nom . ' : ' . ($supprimees ? 'décroché de displayHome' : 'déjà absent') . "\n";
}

/* 3. Renumérotation : diaporama, rayons, nouveautés, meilleures ventes */
$ordreImpose = ['ps_imageslider', 'ludikhome', 'ps_newproducts', 'ps_bestsellers'];
$blocs = $db->executeS(
    'SELECT hm.id_module, m.name
       FROM ' . _DB_PREFIX_ . 'hook_module hm
       JOIN ' . _DB_PREFIX_ . 'module m ON m.id_module = hm.id_module
      WHERE hm.id_hook = ' . $idHome . ' AND hm.id_shop = ' . $idShop . '
      ORDER BY hm.position'
);
$noms = [];
foreach ($blocs as $bloc) {
    $noms[(int) $bloc['id_module']] = $bloc['name'];
}
$ordonnes = [];
foreach ($ordreImpose as $nom) {
    foreach ($noms as $id => $n) {
        if ($n === $nom) {
            $ordonnes[] = $id;
        }
    }
}
foreach (array_keys($noms) as $id) {
    if (!in_array($id, $ordonnes, true)) {
        $ordonnes[] = $id;
    }
}
$resume = [];
foreach ($ordonnes as $rang => $id) {
    $db->update(
        'hook_module',
        ['position' => $rang + 1],
        'id_hook = ' . $idHome . ' AND id_shop = ' . $idShop . ' AND id_module = ' . (int) $id
    );
    $resume[] = ($rang + 1) . '. ' . $noms[$id];
}
echo 'ordre de displayHome : ' . implode(' | ', $resume) . "\n";

Tools::clearSmartyCache();
Tools::clearAllCache();
echo "caches purgés\n";
