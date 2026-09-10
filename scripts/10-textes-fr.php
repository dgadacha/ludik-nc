<?php
/**
 * Traductions manquantes du thème, contenu des blocs de réassurance,
 * et réglages d'affichage propres à Ludik.
 * Usage : docker exec ludik-ps php /scripts/10-textes-fr.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$idLang = (int) Configuration::get('PS_LANG_DEFAULT');
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');

/* ---------- 1. chaînes du thème restées en anglais ---------- */
$trad = [
    ['Shop.Theme.Catalog', 'Price: ', 'Prix : '],
    ['Shop.Theme.Catalog', 'Search products...', 'Rechercher un jeu, une BD, un manga…'],
    ['Shop.Theme.Catalog', 'Product details', 'Fiche produit'],
    ['Shop.Theme.Catalog', 'Data sheet', 'Caractéristiques'],
    ['Modules.Contactinfo.Shop', 'Call us: [1]%phone%[/1]', 'Une question ? [1]%phone%[/1]'],
    ['Shop.Theme.Global', 'Relevance', 'Pertinence'],
    ['Shop.Theme.Actions', 'Continue shopping', 'Continuer mes achats'],
    ['Shop.Theme.Menu', 'Tout voir', 'Tout voir'],
];
/* deux entrées par chaîne : le noyau interroge la base avec theme IS NULL,
   les templates du thème avec le nom du thème actif. */
$added = 0;
foreach ($trad as [$domain, $key, $value]) {
    foreach ([null, 'ludik'] as $theme) {
        $where = 'id_lang = ' . $idLang . " AND domain = '" . pSQL($domain) . "'"
            . " AND `key` = '" . pSQL($key) . "' AND "
            . ($theme === null ? 'theme IS NULL' : "theme = '" . pSQL($theme) . "'");
        $exists = (int) $db->getValue('SELECT id_translation FROM ' . _DB_PREFIX_ . 'translation WHERE ' . $where);
        if ($exists) {
            $db->update('translation', ['translation' => pSQL($value)], 'id_translation = ' . $exists);
        } else {
            $row = ['id_lang' => $idLang, 'domain' => pSQL($domain),
                    'key' => pSQL($key), 'translation' => pSQL($value)];
            if ($theme !== null) { $row['theme'] = $theme; }
            $db->insert('translation', $row);
        }
        ++$added;
    }
}
echo "traductions enregistrées : $added\n";

/* ---------- 2. blocs de réassurance ---------- */
/* libellés repris de la page « Commande et Livraison » du site actuel */
$blocs = [
    ['icon' => 'parcel.svg', 'title' => 'Retrait en boutique',
     'desc' => 'Votre article sera mis de côté dès réception de la commande.'],
    ['icon' => 'carrier.svg', 'title' => 'Livraison OPT',
     'desc' => 'Sur tout le territoire et dans les îles. Expédition sous 24h.'],
    ['icon' => 'globe.svg', 'title' => 'Livraison internationale',
     'desc' => 'Dans tout le Pacifique et les pays francophones. Expédition sous 24h.'],
    ['icon' => 'gift.svg', 'title' => 'Retrait lors des soirées Ludik',
     'desc' => 'Chaque mardi au Fronton Etchekhan. Votre entrée à la soirée vous sera offerte.'],
];
$db->execute('DELETE FROM ' . _DB_PREFIX_ . 'psreassurance_lang');
$db->execute('DELETE FROM ' . _DB_PREFIX_ . 'psreassurance');
$pos = 1;
foreach ($blocs as $b) {
    $db->insert('psreassurance', [
        'icon' => '//modules/blockreassurance/views/img/reassurance/pack2/' . $b['icon'],
        'custom_icon' => null, 'status' => 1, 'position' => $pos++,
        'type_link' => 0, 'id_cms' => 0, 'date_add' => date('Y-m-d H:i:s'),
    ]);
    $id = (int) $db->Insert_ID();
    foreach (Language::getLanguages(false) as $l) {
        $db->insert('psreassurance_lang', [
            'id_psreassurance' => $id, 'id_lang' => (int) $l['id_lang'], 'id_shop' => $idShop,
            'title' => pSQL($b['title']), 'description' => pSQL($b['desc']), 'link' => '',
        ], false, true, Db::INSERT_IGNORE);
    }
    echo "réassurance : {$b['title']}\n";
}
Configuration::updateValue('RRI_NB_BLOCKS', count($blocs));
Configuration::updateValue('BLOCKREASSURANCE_NBBLOCKS', count($blocs));

/* ---------- 3. réglages d'affichage ---------- */
Configuration::updateValue('PS_DISPLAY_PRODUCT_CONDITION', 0);   // pas de pastille « Neuf »
Configuration::updateValue('PS_DISPLAY_TAXES_LABEL', 0);         // pas de mention de taxe
Configuration::updateValue('PS_TAX_DISPLAY', 0);
Configuration::updateValue('PS_ATTRIBUTE_ANCHOR_SEPARATOR', '-');
Configuration::updateValue('PS_SHOW_ALL_MODULES', 0);
Configuration::updateValue('PS_PRODUCT_SHORT_DESC_LIMIT', 0);
Configuration::updateValue('PS_LAYERED_FILTER_PRICE_USETAX', 0);
Configuration::updateValue('PS_LAYERED_FILTER_CATEGORY_DEPTH', 2);
Configuration::updateValue('PS_LAYERED_SHOW_QTIES', 1);
Configuration::updateValue('PS_HOME_CATEGORY_IMAGES', 0);
Configuration::updateValue('PS_CUSTOMER_SERVICE_SIGNATURE', "L'équipe Ludik\ncontact@ludik.nc - 46 55 46");

/* une seule devise : le franc pacifique */
foreach (['USD', 'EUR'] as $iso) {
    $id = (int) Currency::getIdByIsoCode($iso);
    if ($id) {
        $c = new Currency($id);
        $c->active = 0;
        $c->update();
        echo "devise $iso désactivée\n";
    }
}

/* langue anglaise retirée : la boutique est francophone */
$idEn = (int) Language::getIdByIso('en');
if ($idEn && $idEn !== $idLang) {
    $en = new Language($idEn);
    $en->active = false;
    $en->update();
    echo "langue anglaise désactivée\n";
}

echo "Textes et réglages appliqués.\n";
