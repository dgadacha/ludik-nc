<?php
/**
 * Génère les déclinaisons d'images produits attendues par le thème.
 * Les sources font 600 px : les formats plus grands sont ramenés à cette taille.
 *
 * Usage : docker exec ludik-ps php /scripts/08-thumbnails.php <part> <total>
 *   ex. 8 process : for i in 0 1 2 3 4 5 6 7; do php /scripts/08-thumbnails.php $i 8 & done
 */
require_once '/var/www/html/config/config.inc.php';

$part = isset($argv[1]) ? (int) $argv[1] : 0;
$total = isset($argv[2]) ? max(1, (int) $argv[2]) : 1;

/* formats retenus, adaptés à la taille des sources */
$keep = ['small_default', 'cart_default', 'home_default', 'medium_default',
         'large_default', 'default_xs', 'default_sm', 'default_md',
         'default_lg', 'default_xl', 'product_main'];
$maxSource = 600;

$types = [];
foreach (ImageType::getImagesTypes('products', true) as $t) {
    if (in_array($t['name'], $keep, true)) {
        $types[] = [
            'name' => $t['name'],
            'w' => min((int) $t['width'], $maxSource),
            'h' => min((int) $t['height'], $maxSource),
        ];
    }
}
if ($part === 0) {
    echo 'formats générés : ' . implode(', ', array_map(fn($t) => $t['name'] . ' ' . $t['w'], $types)) . PHP_EOL;
}

$rows = Db::getInstance()->executeS(
    'SELECT id_image, id_product FROM ' . _DB_PREFIX_ . 'image
     WHERE MOD(id_image, ' . $total . ') = ' . $part . ' ORDER BY id_image'
);

$done = 0; $skip = 0; $t0 = microtime(true);
foreach ($rows as $r) {
    $img = new Image((int) $r['id_image']);
    $dir = _PS_PRODUCT_IMG_DIR_ . $img->getImgFolder();
    $src = $dir . $img->id . '.jpg';
    if (!file_exists($src)) { $skip++; continue; }
    foreach ($types as $t) {
        $dst = $dir . $img->id . '-' . $t['name'] . '.jpg';
        if (file_exists($dst)) { continue; }
        ImageManager::resize($src, $dst, $t['w'], $t['h'], 'jpg');
    }
    $done++;
    if ($part === 0 && $done % 500 === 0) {
        $el = microtime(true) - $t0;
        printf("  part 0 : %d images - %.0fs - %.1f/s (total estimé %.0f min)\n",
            $done, $el, $done / $el, (count($rows) / max($done / $el, .01)) / 60);
    }
}
printf("part %d : %d images traitées, %d sans source - %.0fs\n", $part, $done, $skip, microtime(true) - $t0);
