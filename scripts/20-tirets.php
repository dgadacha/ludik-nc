<?php
/**
 * Remplace les tirets cadratins par des traits d'union dans tous les textes.
 *
 * Règle de rédaction du projet : pas de typographie exotique. Le tiret
 * cadratin « — » est remplacé par « - » partout, y compris dans les
 * descriptions reprises du site d'origine : c'est une normalisation
 * typographique, le contenu ne change pas.
 *
 * Le script est rejouable et sans effet s'il n'y a plus rien à corriger.
 *
 * Usage : docker exec ludik-ps php /scripts/20-tirets.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$cadratin = "\xE2\x80\x94"; // —

/* table => colonnes de texte à parcourir */
$cibles = [
    'category_lang' => ['name', 'description', 'additional_description', 'meta_title', 'meta_description', 'meta_keywords'],
    'product_lang' => ['name', 'description', 'description_short', 'meta_title', 'meta_description', 'available_now', 'available_later'],
    'cms_lang' => ['meta_title', 'meta_description', 'meta_keywords', 'content', 'head_seo_title'],
    'cms_category_lang' => ['name', 'description', 'meta_title', 'meta_description'],
    'store_lang' => ['name', 'address1', 'address2', 'hours', 'note'],
    'link_block_lang' => ['name'],
    'homeslider_slides_lang' => ['title', 'legend', 'description', 'url'],
    'meta_lang' => ['title', 'description', 'keywords'],
    'configuration_lang' => ['value'],
    'manufacturer_lang' => ['description', 'short_description', 'meta_title', 'meta_description'],
    'tab_lang' => ['name'],
    /* les transporteurs portent le nom du mode de livraison et son délai :
       ces libellés se lisent dans le tunnel de commande */
    'carrier' => ['name'],
    'carrier_lang' => ['delay'],
];

$total = 0;
foreach ($cibles as $table => $colonnes) {
    /* certaines tables n'existent pas selon les modules installés */
    $existe = $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . pSQL($table) . '"');
    if (!$existe) {
        continue;
    }

    $champs = array_column($db->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . pSQL($table)), 'Field');
    $sets = [];
    $ou = [];
    foreach ($colonnes as $colonne) {
        if (!in_array($colonne, $champs, true)) {
            continue;
        }
        $sets[] = '`' . $colonne . '` = REPLACE(`' . $colonne . '`, "' . $cadratin . '", "-")';
        $ou[] = '`' . $colonne . '` LIKE "%' . $cadratin . '%"';
    }
    if (!$sets) {
        continue;
    }

    $avant = (int) $db->getValue(
        'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . pSQL($table) . ' WHERE ' . implode(' OR ', $ou)
    );
    if (!$avant) {
        continue;
    }

    $db->execute(
        'UPDATE ' . _DB_PREFIX_ . pSQL($table) . ' SET ' . implode(', ', $sets)
        . ' WHERE ' . implode(' OR ', $ou)
    );
    echo $table . ' : ' . $avant . " lignes corrigées\n";
    $total += $avant;
}

/* les valeurs de configuration ne sont pas dans une table _lang */
$lignes = $db->executeS(
    'SELECT id_configuration, value FROM ' . _DB_PREFIX_ . 'configuration
      WHERE value LIKE "%' . $cadratin . '%"'
);
foreach ($lignes ?: [] as $ligne) {
    $db->update(
        'configuration',
        ['value' => pSQL(str_replace('—', '-', $ligne['value']), true)],
        'id_configuration = ' . (int) $ligne['id_configuration']
    );
    ++$total;
}
if ($lignes) {
    echo 'configuration : ' . count($lignes) . " lignes corrigées\n";
}

echo $total ? ('total : ' . $total . " lignes\n") : "rien à corriger\n";

Tools::clearSmartyCache();
Tools::clearAllCache();
