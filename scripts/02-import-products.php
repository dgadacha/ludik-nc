<?php
/**
 * Importe les produits Ludik dans PrestaShop (nom, prix XPF, description, stock,
 * catégories, image). Reprend là où il s'est arrêté grâce à /import/map-products.json.
 *
 * Usage : docker exec ludik-ps php /scripts/02-import-products.php [offset] [limit]
 */
require_once '/var/www/html/config/config.inc.php';

$offset = isset($argv[1]) ? (int) $argv[1] : 0;
$limit  = isset($argv[2]) ? (int) $argv[2] : 0;

$t0 = microtime(true);
$products = json_decode(file_get_contents('/import/products.json'), true);
$catMap   = json_decode(file_get_contents('/import/map-categories.json'), true);
$mapFile  = '/import/map-products.json';
$map      = file_exists($mapFile) ? json_decode(file_get_contents($mapFile), true) : [];

$langs   = array_map(fn($l) => (int) $l['id_lang'], Language::getLanguages(false));
$idHome  = (int) Configuration::get('PS_HOME_CATEGORY');
$idShop  = (int) Configuration::get('PS_SHOP_DEFAULT');
$srcDir  = '/import/img';

if ($limit) { $products = array_slice($products, $offset, $limit); }
elseif ($offset) { $products = array_slice($products, $offset); }

echo count($products) . " produits à traiter (déjà importés : " . count($map) . ")\n";

/* Un produit "Disponible" sur l'ancien site est en stock, sinon 0. */
function stockOf(string $avail): int
{
    $a = mb_strtolower($avail);
    if (str_contains($a, 'disponible') || str_contains($a, 'in stock')) { return 5; }
    return 0;
}

$done = 0; $fail = 0; $imgOk = 0;
foreach ($products as $p) {
    $src = (string) $p['src'];
    if (isset($map[$src])) { continue; }

    $cats = [];
    foreach ($p['cats'] as $c) {
        if (isset($catMap[(string) $c])) { $cats[] = (int) $catMap[(string) $c]; }
    }
    $cats = array_values(array_unique($cats));
    $default = $cats ? $cats[count($cats) - 1] : $idHome;

    $prod = new Product();
    $prod->price = (float) $p['price'];
    $prod->wholesale_price = 0;
    $prod->id_tax_rules_group = 0;               // pas de TVA en Nouvelle-Calédonie
    $prod->active = 1;
    $prod->visibility = 'both';
    $prod->available_for_order = 1;
    $prod->show_price = 1;
    $prod->indexed = 0;
    $prod->minimal_quantity = 1;
    $prod->id_category_default = $default;
    $prod->ean13 = preg_match('/^\d{13}$/', (string) $p['ean']) ? $p['ean'] : '';
    $prod->reference = 'LDK-' . $p['src'];
    $prod->condition = 'new';
    $prod->out_of_stock = 0;
    $prod->id_shop_default = $idShop;

    $slug = $p['link'] . '-' . $p['src'];
    foreach ($langs as $l) {
        $prod->name[$l] = $p['name'];
        $prod->link_rewrite[$l] = $slug;
        $short = $p['desc'];
        if (mb_strlen($short) > 700) {
            $cut = mb_substr($short, 0, 700);
            $lastDot = mb_strrpos($cut, '. ');
            $short = $lastDot > 300 ? mb_substr($cut, 0, $lastDot + 1) : rtrim($cut) . '…';
        }
        $prod->description_short[$l] = $short ? '<p>' . htmlspecialchars($short, ENT_QUOTES, 'UTF-8') . '</p>' : '';
        $prod->description[$l] = mb_strlen($p['desc']) > 700
            ? '<p>' . htmlspecialchars($p['desc'], ENT_QUOTES, 'UTF-8') . '</p>' : '';
        $prod->meta_title[$l] = mb_substr($p['name'], 0, 250);
        /* meta_description : pas de guillemet ni de caractère refusé par isGenericName */
        $meta = preg_replace('/[<>={}]/u', '', $p['desc'] ?: $p['name']);
        $prod->meta_description[$l] = mb_substr(trim($meta), 0, 300);
    }

    try {
        $prod->add(true, false);
        if ($cats) { $prod->addToCategories($cats); }
        StockAvailable::setQuantity((int) $prod->id, 0, stockOf($p['avail']), $idShop, false);

        if (!empty($p['img']) && file_exists($srcDir . '/' . $p['img'])) {
            $img = new Image();
            $img->id_product = (int) $prod->id;
            $img->position = 1;
            $img->cover = true;
            foreach ($langs as $l) { $img->legend[$l] = $p['name']; }
            if ($img->add()) {
                $dir = _PS_PRODUCT_IMG_DIR_ . $img->getImgFolder();
                if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                copy($srcDir . '/' . $p['img'], $dir . $img->id . '.jpg');
                $imgOk++;
            }
        }

        $map[$src] = (int) $prod->id;
        $done++;
    } catch (Exception $e) {
        $fail++;
        echo "ECHEC produit {$p['src']} : " . $e->getMessage() . "\n";
    }

    if ($done > 0 && $done % 500 === 0) {
        file_put_contents($mapFile, json_encode($map));
        $el = microtime(true) - $t0;
        printf("  %d importés - %.0fs - %.1f produits/s\n", $done, $el, $done / $el);
    }
}

file_put_contents($mapFile, json_encode($map));
$el = microtime(true) - $t0;
printf("Terminé : %d importés (%d images, %d échecs) en %.0fs - %.1f/s\n", $done, $imgOk, $fail, $el, $done / max($el, 1));
