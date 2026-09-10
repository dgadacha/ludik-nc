<?php
/**
 * Correctifs relevés par l'audit des pages (docs/AUDIT-PAGES.md) qui ne touchent
 * pas au thème : URL, facettes, pages de sélection, marques de démonstration.
 * Usage : docker exec ludik-ps php /scripts/13-correctifs-fonctionnels.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$idLang = (int) Configuration::get('PS_LANG_DEFAULT');

/* ---------- 1. l'URL de la page « meilleures ventes » contenait une espace ---------- */
$db->execute(
    'UPDATE ' . _DB_PREFIX_ . 'meta_lang ml
     JOIN ' . _DB_PREFIX_ . 'meta m ON m.id_meta = ml.id_meta
     SET ml.url_rewrite = "meilleures-ventes"
     WHERE m.page = "best-sales"'
);
echo "URL des meilleures ventes : meilleures-ventes\n";

/* ---------- 2. facette « Catégories » : elle générait 2 816 cases sur un rayon ---------- */
Configuration::updateValue('PS_LAYERED_FILTER_CATEGORY_DEPTH', 1);
Configuration::updateValue('PS_LAYERED_SHOW_QTIES', 1);
/* PS_LAYERED_FULL_TREE doit rester a 1 : sinon un rayon parent, qui n'a aucun
   produit en propre, s'affiche vide alors que ses sous-rayons en contiennent. */
Configuration::updateValue('PS_LAYERED_FULL_TREE', 1);
$supprimes = 0;
foreach ($db->executeS('SELECT id_layered_filter, filters FROM ' . _DB_PREFIX_ . 'layered_filter') as $f) {
    $filtres = json_decode($f['filters'], true);
    if (!is_array($filtres) || empty($filtres['filters'])) {
        continue;
    }
    $avant = count($filtres['filters']);
    $filtres['filters'] = array_values(array_filter(
        $filtres['filters'],
        fn($x) => ($x['type'] ?? '') !== 'category'
    ));
    if (count($filtres['filters']) !== $avant) {
        $db->update('layered_filter', ['filters' => pSQL(json_encode($filtres))],
                    'id_layered_filter = ' . (int) $f['id_layered_filter']);
        ++$supprimes;
    }
}
echo "facette « catégories » retirée de $supprimes gabarit(s) de filtres\n";

/* ---------- 3. « nouveautés » : tout le catalogue est arrivé le même jour ---------- */
Configuration::updateValue('PS_NB_DAYS_NEW_PRODUCT', 20);
Configuration::updateValue('NEW_PRODUCTS_NBR', 10);
echo "fenêtre des nouveautés : 20 jours\n";

/* ---------- 4. marques et fournisseurs de démonstration PrestaShop ---------- */
foreach (['Graphic Corner', 'Studio Design', 'Fashion Manufacturer'] as $nom) {
    $id = (int) $db->getValue('SELECT id_manufacturer FROM ' . _DB_PREFIX_ . 'manufacturer WHERE name = "' . pSQL($nom) . '"');
    if ($id) {
        (new Manufacturer($id))->delete();
        echo "marque de démonstration supprimée : $nom\n";
    }
}
foreach (['Fashion Supplier', 'Accessories Supplier'] as $nom) {
    $id = (int) $db->getValue('SELECT id_supplier FROM ' . _DB_PREFIX_ . 'supplier WHERE name = "' . pSQL($nom) . '"');
    if ($id) {
        (new Supplier($id))->delete();
        echo "fournisseur de démonstration supprimé : $nom\n";
    }
}
Configuration::updateValue('PS_DISPLAY_MANUFACTURERS', 0);
Configuration::updateValue('PS_DISPLAY_SUPPLIERS', 0);

/* ---------- 5. adresse de règlement par chèque, reprise des mentions légales ---------- */
Configuration::updateValue('CHEQUE_ADDRESS', "LUDIK SARL\n107 rue Auguste Bénébig\nVallée des Colons\n98800 Nouméa");
echo "adresse de règlement par chèque corrigée\n";

/* ---------- 6. titres de produits : le tome était répété ---------- */
$doublons = $db->executeS(
    'SELECT id_product, id_lang, name FROM ' . _DB_PREFIX_ . 'product_lang
     WHERE name LIKE "%- Tome %- Tome %" OR name LIKE "%- Volume %- Volume %"'
);
$corriges = 0;
foreach ($doublons ?: [] as $p) {
    $propre = preg_replace('/ - (Tome|Volume) (\d+) - \1 \2$/u', ' - $1 $2', $p['name']);
    if ($propre !== $p['name']) {
        $db->update('product_lang', ['name' => pSQL($propre)],
            'id_product = ' . (int) $p['id_product'] . ' AND id_lang = ' . (int) $p['id_lang']);
        ++$corriges;
    }
}
echo "titres avec tome répété corrigés : $corriges sur " . count($doublons ?: []) . " candidats\n";

/* ---------- 7. mention « Translator » en tête de description ---------- */
$n = (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_lang WHERE description_short LIKE "%>Translator %"');
$db->execute(
    'UPDATE ' . _DB_PREFIX_ . 'product_lang
     SET description_short = REPLACE(description_short, "<p>Translator ", "<p>")
     WHERE description_short LIKE "%>Translator %"'
);
echo "mentions « Translator » retirées : $n\n";

echo "Correctifs fonctionnels appliqués.\n";
