<?php
/**
 * Colonnes de liens du pied de page.
 *
 * Le pied comptait cinq blocs : coordonnées, trois listes de liens et espace
 * client. C'était dense, et les adresses figurent déjà dans les cartes
 * boutique juste au-dessus. On garde trois colonnes, chacune avec un objet
 * clair, et l'espace client rejoint la colonne « Aide » :
 *
 *   Boutique      les rayons et les sélections
 *   Informations  les pages qui engagent la boutique
 *   Aide          ce qu'on cherche quand on a un problème
 *
 * Les liens statiques utilisent les identifiants de page de PrestaShop, les
 * liens éditoriaux les identifiants des pages CMS importées du site d'origine.
 *
 * Usage : docker exec ludik-ps php /scripts/22-pied-de-page.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
$langues = Language::getLanguages(false);

/* id_cms des pages reprises du site : 1 livraison, 2 mentions légales,
   4 qui sommes-nous, 6 soirées, 7 paiement, 8 comment choisir, 9 CGV */
$colonnes = [
    'Boutique' => [
        'cms' => [],
        'static' => ['new-products', 'best-sales', 'sitemap'],
    ],
    'Informations' => [
        'cms' => [4, 6, 9, 2],
        'static' => [],
    ],
    'Aide' => [
        'cms' => [1, 7, 8],
        'static' => ['contact', 'stores'],
    ],
];

/* on repart des blocs existants : trois suffisent, les autres sont supprimés */
$existants = $db->executeS(
    'SELECT b.id_link_block FROM ' . _DB_PREFIX_ . 'link_block b ORDER BY b.position'
);
$ids = array_column($existants ?: [], 'id_link_block');

$position = 0;
foreach ($colonnes as $titre => $contenu) {
    $id = array_shift($ids);
    $json = json_encode([
        'cms' => array_values($contenu['cms']),
        'product' => [],
        'static' => array_values($contenu['static']),
    ]);

    if ($id) {
        $db->update('link_block', ['content' => pSQL($json), 'position' => $position], 'id_link_block = ' . (int) $id);
        foreach ($langues as $langue) {
            $db->update(
                'link_block_lang',
                ['name' => pSQL($titre)],
                'id_link_block = ' . (int) $id . ' AND id_lang = ' . (int) $langue['id_lang']
            );
        }
        echo 'colonne ' . ($position + 1) . ' : ' . $titre . "\n";
    }
    ++$position;
}

/* les blocs en trop sont retirés */
foreach ($ids as $id) {
    $db->delete('link_block_lang', 'id_link_block = ' . (int) $id);
    $db->delete('link_block_shop', 'id_link_block = ' . (int) $id);
    $db->delete('link_block', 'id_link_block = ' . (int) $id);
    echo 'bloc supprimé : ' . $id . "\n";
}

/* Les coordonnées et l'espace client sortent du pied : les adresses sont dans
   les cartes boutique, et les liens de compte sont dans la colonne « Aide »
   et dans l'en-tête. */
$idFooter = (int) Hook::getIdByName('displayFooter');
foreach (['ps_contactinfo', 'ps_customeraccountlinks'] as $nom) {
    $module = Module::getInstanceByName($nom);
    if (!$module || !$module->id) {
        continue;
    }
    $db->delete(
        'hook_module',
        'id_module = ' . (int) $module->id . ' AND id_hook = ' . $idFooter . ' AND id_shop = ' . $idShop
    );
    echo $nom . " : décroché du pied de page\n";
}

/* l'inscription à la lettre d'information prend la quatrième colonne */
$module = Module::getInstanceByName('ps_emailsubscription');
if ($module && $module->id) {
    $existe = (int) $db->getValue(
        'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'hook_module
          WHERE id_module = ' . (int) $module->id . ' AND id_hook = ' . $idFooter . '
            AND id_shop = ' . $idShop
    );
    if (!$existe) {
        $module->registerHook('displayFooter');
        echo "ps_emailsubscription : accroché au pied de page\n";
    }
    $db->update(
        'hook_module',
        ['position' => 10],
        'id_module = ' . (int) $module->id . ' AND id_hook = ' . $idFooter . ' AND id_shop = ' . $idShop
    );
}

Tools::clearSmartyCache();
Tools::clearAllCache();
echo "caches purgés\n";
