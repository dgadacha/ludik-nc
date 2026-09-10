<?php
/**
 * Crée l'arborescence de catégories Ludik dans PrestaShop.
 * Lit /import/categories.json et écrit /import/map-categories.json (id source -> id PrestaShop).
 * Usage : docker exec ludik-ps php /scripts/01-import-categories.php
 */
require_once '/var/www/html/config/config.inc.php';

$t0 = microtime(true);
$data = json_decode(file_get_contents('/import/categories.json'), true);
$cats = $data['categories'];
$langs = array_map(fn($l) => (int) $l['id_lang'], Language::getLanguages(false));
$idHome = (int) Configuration::get('PS_HOME_CATEGORY');
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');

$mapFile = '/import/map-categories.json';
$map = file_exists($mapFile) ? json_decode(file_get_contents($mapFile), true) : [];

echo count($cats) . " catégories à traiter (déjà faites : " . count($map) . ")\n";

$done = 0;
$slugSeen = [];
foreach ($cats as $c) {
    $src = (string) $c['src'];
    if (isset($map[$src])) { continue; }

    $parent = $idHome;
    if (!empty($c['parent']) && isset($map[(string) $c['parent']])) {
        $parent = (int) $map[(string) $c['parent']];
    }

    // link_rewrite unique
    $slug = $c['link'];
    if (isset($slugSeen[$slug])) {
        $slug .= '-' . $c['src'];
    }
    $slugSeen[$slug] = true;

    $cat = new Category();
    $cat->doNotRegenerateNTree = true;      // arbre régénéré une seule fois à la fin
    $cat->id_parent = $parent;
    $cat->active = 1;
    $cat->is_root_category = false;
    foreach ($langs as $l) {
        $cat->name[$l] = $c['name'];
        $cat->link_rewrite[$l] = $slug;
        $cat->description[$l] = '';
        $cat->meta_title[$l] = $c['name'] . ' - Ludik.nc';
    }
    try {
        $cat->add(true, false);
        $map[$src] = (int) $cat->id;
        $done++;
    } catch (Exception $e) {
        echo "ECHEC cat {$c['src']} {$c['name']} : " . $e->getMessage() . "\n";
    }
    if ($done % 250 === 0 && $done > 0) {
        file_put_contents($mapFile, json_encode($map));
        printf("  %d créées - %.0fs\n", $done, microtime(true) - $t0);
    }
}

file_put_contents($mapFile, json_encode($map));
echo "Régénération de l'arbre imbriqué...\n";
Category::regenerateEntireNtree();
printf("Terminé : %d catégories créées, %d au total - %.0fs\n", $done, count($map), microtime(true) - $t0);
