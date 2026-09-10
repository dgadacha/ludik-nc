<?php
/**
 * Prépare la recherche à facettes après un import de catalogue.
 *
 * L'audit a montré que les filtres ne renvoyaient rien : la table
 * ps_layered_category, qui associe chaque catégorie à ses filtres, était vide.
 *
 * L'ordre des étapes compte : rebuildLayeredStructure() remet à zéro la structure
 * et vide cette association, il doit donc passer AVANT buildLayeredCategories().
 *
 * Usage : docker exec ludik-ps php /scripts/15-index-facettes.php
 */
require_once '/var/www/html/config/config.inc.php';

$module = Module::getInstanceByName('ps_facetedsearch');
if (!$module) {
    exit("module ps_facetedsearch absent\n");
}

$db = Db::getInstance();
$t0 = microtime(true);
$compte = fn(string $table) => (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . $table);

/* 1. structure des filtres : attributs et caractéristiques déclarés filtrables */
$module->rebuildLayeredStructure();
echo "structure des filtres reconstruite\n";

/* 2. index des attributs de déclinaison (vide ici : le catalogue n'a pas de déclinaison) */
$module->indexAttributes();
printf("attributs indexés : %d\n", $compte('layered_product_attribute'));

/* 3. index des prix, nécessaire à la facette « prix » */
if ($compte('layered_price_index') === 0) {
    $module->fullPricesIndexProcess();
}
printf("prix indexés : %d\n", $compte('layered_price_index'));

/* 4. association des catégories aux gabarits de filtres : sans elle, les facettes
      s'affichent et comptent, mais le filtrage n'est jamais appliqué */
$module->buildLayeredCategories();
printf("catégories associées aux filtres : %d\n", $compte('layered_category'));

/* 5. cache des blocs de filtres */
$module->invalidateLayeredFilterBlockCache();
printf("blocs de filtres en cache : %d\n", $compte('layered_filter_block'));

printf("terminé en %.0fs\n", microtime(true) - $t0);
