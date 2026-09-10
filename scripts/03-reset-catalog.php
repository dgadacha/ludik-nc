<?php
/**
 * Vide le catalogue (produits de démonstration inclus) pour repartir d'une base propre.
 * Conserve la configuration de la boutique, les magasins, les pages CMS.
 * Usage : docker exec ludik-ps php /scripts/03-reset-catalog.php --yes
 */
require_once '/var/www/html/config/config.inc.php';

if (!in_array('--yes', $argv, true)) {
    exit("Ajoutez --yes pour confirmer la remise à zéro du catalogue.\n");
}

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idHome = (int) Configuration::get('PS_HOME_CATEGORY');
$idRoot = (int) Configuration::get('PS_ROOT_CATEGORY');

$tables = [
    'product', 'product_shop', 'product_lang', 'product_attribute', 'product_attribute_shop',
    'product_attribute_combination', 'product_attribute_image', 'product_supplier',
    'product_carrier', 'product_download', 'product_group_reduction_cache', 'product_sale',
    'product_tag', 'category_product', 'image', 'image_shop', 'image_lang',
    'stock_available', 'stock', 'specific_price', 'specific_price_rule', 'pack',
    'feature_product', 'accessory', 'search_index', 'search_word', 'cart_product',
    'customization', 'customization_field', 'customization_field_lang', 'wishlist_product',
    'attribute_impact', 'layered_product_attribute',
];
foreach ($tables as $t) {
    if ($db->executeS("SHOW TABLES LIKE '{$p}{$t}'")) {
        $db->execute("TRUNCATE TABLE `{$p}{$t}`");
        echo "vidé : {$t}\n";
    }
}

/* catégories : on garde uniquement la racine et Accueil */
$db->execute("DELETE FROM `{$p}category` WHERE id_category NOT IN ({$idRoot}, {$idHome})");
$db->execute("DELETE FROM `{$p}category_lang` WHERE id_category NOT IN ({$idRoot}, {$idHome})");
$db->execute("DELETE FROM `{$p}category_shop` WHERE id_category NOT IN ({$idRoot}, {$idHome})");
$db->execute("DELETE FROM `{$p}category_group` WHERE id_category NOT IN ({$idRoot}, {$idHome})");
echo "catégories supprimées (racine et Accueil conservées)\n";

$db->execute("ALTER TABLE `{$p}product` AUTO_INCREMENT = 1");
$db->execute("ALTER TABLE `{$p}category` AUTO_INCREMENT = " . ($idHome + 1));
$db->execute("ALTER TABLE `{$p}image` AUTO_INCREMENT = 1");

Category::regenerateEntireNtree();

/* fichiers images produits */
$dir = _PS_PRODUCT_IMG_DIR_;
exec('find ' . escapeshellarg($dir) . ' -mindepth 1 -maxdepth 1 -type d -exec rm -rf {} +');
echo "images produits supprimées\n";

/* cartes des identifiants */
@unlink('/import/map-categories.json');
@unlink('/import/map-products.json');
echo "cartes d'identifiants réinitialisées\n";
echo "Catalogue remis à zéro.\n";
