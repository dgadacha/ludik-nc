<?php
/**
 * Menu principal : les six univers + les pages utiles, sur deux niveaux.
 * Usage : docker exec ludik-ps php /scripts/09-menu.php
 */
require_once '/var/www/html/config/config.inc.php';

$idLang = (int) Configuration::get('PS_LANG_DEFAULT');

/* univers dans l'ordre souhaité */
$ordre = ['Jeux de société', 'Cartes à collectionner', 'Accessoires de jeu',
          'Jeux de rôle', 'Librairie', 'Loisirs créatifs'];
$items = [];
foreach ($ordre as $nom) {
    $id = (int) Db::getInstance()->getValue(
        'SELECT c.id_category FROM ' . _DB_PREFIX_ . 'category c
         JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . '
         WHERE cl.name = \'' . pSQL($nom) . '\' AND c.level_depth = 2'
    );
    if ($id) {
        $items[] = 'CAT' . $id;
        /* position dans l'arbre = ordre d'affichage */
    }
}

/* liens libres du menu : libellés courts, l'espace du bandeau est compté */
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
$db = Db::getInstance();
$db->execute('DELETE FROM ' . _DB_PREFIX_ . 'linksmenutop_lang');
$db->execute('DELETE FROM ' . _DB_PREFIX_ . 'linksmenutop');

$idSoiree = (int) $db->getValue(
    'SELECT id_cms FROM ' . _DB_PREFIX_ . 'cms_lang WHERE link_rewrite = \'soirees-jeux\' AND id_lang = ' . $idLang
);
$liens = [
    ['label' => 'Nouveautés', 'link' => Context::getContext()->link->getPageLink('new-products')],
    ['label' => 'Soirées jeux', 'link' => $idSoiree
        ? Context::getContext()->link->getCMSLink($idSoiree, 'soirees-jeux')
        : Context::getContext()->link->getPageLink('index')],
];
foreach ($liens as $l) {
    $db->insert('linksmenutop', ['id_shop' => $idShop, 'new_window' => 0]);
    $id = (int) $db->Insert_ID();
    foreach (Language::getLanguages(false) as $lang) {
        $db->insert('linksmenutop_lang', [
            'id_linksmenutop' => $id, 'id_lang' => (int) $lang['id_lang'], 'id_shop' => $idShop,
            'label' => pSQL($l['label']), 'link' => pSQL($l['link']),
        ], false, true, Db::INSERT_IGNORE);
    }
    /* ces liens vivent dans le bandeau du haut, pas dans le menu principal */
}

Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', implode(',', $items));
Configuration::updateValue('MOD_BLOCKTOPMENU_SEARCH', 0);
Configuration::updateValue('MOD_BLOCKTOPMENU_MAX_DEPTH', 2);
echo 'menu : ' . implode(', ', $items) . PHP_EOL;

/* ordre des univers dans l'arbre de catégories */
$pos = 0;
foreach ($ordre as $nom) {
    $id = (int) Db::getInstance()->getValue(
        'SELECT c.id_category FROM ' . _DB_PREFIX_ . 'category c
         JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . '
         WHERE cl.name = \'' . pSQL($nom) . '\' AND c.level_depth = 2'
    );
    if ($id) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'category SET position = ' . $pos . ' WHERE id_category = ' . $id);
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'category_shop SET position = ' . $pos . ' WHERE id_category = ' . $id);
        ++$pos;
    }
}
Category::regenerateEntireNtree();

/* liens du pied de page (module ps_linklist) : on remplace les blocs de démonstration */
if (Module::isInstalled('ps_linklist')) {
    $db = Db::getInstance();
    $db->execute('DELETE FROM ' . _DB_PREFIX_ . 'link_block_lang');
    $db->execute('DELETE FROM ' . _DB_PREFIX_ . 'link_block_shop');
    $db->execute('DELETE FROM ' . _DB_PREFIX_ . 'link_block');
    $idHook = (int) Hook::getIdByName('displayFooter');

    $blocks = [
        ['title' => 'La boutique', 'cms' => ['a-propos', 'soirees-jeux'], 'pages' => ['stores', 'contact']],
        ['title' => 'Commander', 'cms' => ['livraison', 'paiement', 'comment-choisir-un-jeu'], 'pages' => []],
        ['title' => 'Informations', 'cms' => ['conditions-generales-de-vente', 'mentions-legales'], 'pages' => ['sitemap']],
    ];
    $pos = 0;
    foreach ($blocks as $b) {
        $cmsIds = [];
        foreach ($b['cms'] as $slug) {
            $id = (int) $db->getValue('SELECT id_cms FROM ' . _DB_PREFIX_ . 'cms_lang WHERE link_rewrite = \'' . pSQL($slug) . '\' AND id_lang = ' . $idLang);
            if ($id) { $cmsIds[] = $id; }
        }
        $db->insert('link_block', [
            'id_hook' => $idHook,
            'position' => $pos++,
            'content' => json_encode(['cms' => $cmsIds, 'product' => [], 'static' => $b['pages']]),
        ]);
        $id = (int) $db->Insert_ID();
        foreach (Language::getLanguages(false) as $l) {
            $db->insert('link_block_lang', [
                'id_link_block' => $id,
                'id_lang' => (int) $l['id_lang'],
                'name' => pSQL($b['title']),
                'custom_content' => null,
            ], false, true, Db::INSERT_IGNORE);
        }
        $db->insert('link_block_shop', ['id_link_block' => $id, 'id_shop' => (int) Configuration::get('PS_SHOP_DEFAULT')], false, true, Db::INSERT_IGNORE);
        echo "bloc de liens : {$b['title']}\n";
    }
}

echo "Menu et pied de page configurés.\n";
