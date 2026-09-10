<?php
/**
 * Construit l'index de recherche du catalogue.
 * L'audit a montré que la recherche ne renvoyait rien : l'index n'avait jamais été
 * construit après l'import (les produits sont créés avec indexed = 0).
 *
 * Usage : docker exec ludik-ps php /scripts/14-index-recherche.php [taille_de_lot]
 * Le script est rejouable : il reprend là où il s'est arrêté, en s'appuyant sur
 * le drapeau `indexed` de chaque produit.
 */
require_once '/var/www/html/config/config.inc.php';

$lot = isset($argv[1]) ? max(50, (int) $argv[1]) : 500;
$db = Db::getInstance();

$restant = (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product WHERE indexed = 0');
$total = (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product');
printf("produits à indexer : %d sur %d\n", $restant, $total);

$t0 = microtime(true);
$faits = 0;
while (true) {
    $avant = (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product WHERE indexed = 0');
    if ($avant === 0) {
        break;
    }
    /* full = false : n'indexe que les produits dont indexed = 0 */
    Search::indexation(false);
    $apres = (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product WHERE indexed = 0');
    if ($apres === $avant) {
        echo "l'indexation ne progresse plus, arrêt\n";
        break;
    }
    $faits += $avant - $apres;
    $el = microtime(true) - $t0;
    printf("  %d indexés, %d restants - %.0fs (%.0f/s)\n", $faits, $apres, $el, $faits / max($el, 1));
}

printf("mots indexés : %d\n", (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'search_word'));
printf("entrées d'index : %d\n", (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'search_index'));
printf("terminé en %.0fs\n", microtime(true) - $t0);
