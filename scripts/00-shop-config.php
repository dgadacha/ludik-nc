<?php
/**
 * Configuration de la boutique Ludik.nc :
 * langue FR, pays Nouvelle-Calédonie, devise Franc Pacifique, coordonnées, magasins.
 * Usage : docker exec ludik-ps php /scripts/00-shop-config.php
 */
require_once '/var/www/html/config/config.inc.php';

function say($m) { echo $m . PHP_EOL; }

$idLang = (int) Language::getIdByIso('fr');
if (!$idLang) { exit("Langue fr absente\n"); }

/* ---------- Devise : Franc Pacifique (XPF), sans décimale ---------- */
$idXpf = (int) Currency::getIdByIsoCode('XPF');
if (!$idXpf) {
    $c = new Currency();
    $c->name = 'Franc Pacifique';
    $c->iso_code = 'XPF';
    $c->numeric_iso_code = '953';
    $c->active = 1;
    $c->conversion_rate = 1;
    $c->precision = 0;
    $c->add();
    $idXpf = (int) $c->id;
    say("Devise XPF créée (#$idXpf)");
} else {
    say("Devise XPF déjà présente (#$idXpf)");
}
$xpf = new Currency($idXpf);
$xpf->active = 1;
$xpf->conversion_rate = 1;
$xpf->precision = 0;
$xpf->name = 'Franc Pacifique';
$xpf->symbol = 'F';
$xpf->update();

Configuration::updateValue('PS_CURRENCY_DEFAULT', $idXpf);
Configuration::updateValue('PS_PRICE_DISPLAY_PRECISION', 0);
Configuration::updateValue('PS_PRICE_ROUND_MODE', 2);

/* désactiver l'euro par défaut pour ne pas polluer le sélecteur */
$idEur = (int) Currency::getIdByIsoCode('EUR');
if ($idEur && $idEur !== $idXpf) {
    $eur = new Currency($idEur);
    $eur->active = 0;
    $eur->update();
    say('Devise EUR désactivée');
}

/* ---------- Pays : Nouvelle-Calédonie ---------- */
$idNc = (int) Country::getByIso('NC');
if ($idNc) {
    $nc = new Country($idNc);
    $nc->active = 1;
    $nc->contains_states = 0;
    $nc->need_zip_code = 1;
    $nc->zip_code_format = 'NNNNN';
    $nc->update();
    Configuration::updateValue('PS_COUNTRY_DEFAULT', $idNc);
    say("Pays par défaut : Nouvelle-Calédonie (#$idNc)");
}

/* ---------- Pas de TVA en Nouvelle-Calédonie ---------- */
Configuration::updateValue('PS_TAX', 0);
Configuration::updateValue('PS_TAX_DISPLAY', 0);
Configuration::updateValue('PS_USE_ECOTAX', 0);
say('Taxes désactivées (régime NC)');

/* ---------- Identité de la boutique ---------- */
Configuration::updateValue('PS_SHOP_NAME', 'Ludik.nc');
Configuration::updateValue('PS_SHOP_EMAIL', 'contact@ludik.nc');
Configuration::updateValue('PS_SHOP_DETAILS', "LUDIK SARL\nSARL au capital de 1 000 000 F CFP\nRCS Nouméa 2012 B 1 130 665");
Configuration::updateValue('PS_SHOP_ADDR1', '107 rue Auguste Bénébig');
Configuration::updateValue('PS_SHOP_ADDR2', 'Vallée des Colons');
Configuration::updateValue('PS_SHOP_CODE', '98800');
Configuration::updateValue('PS_SHOP_CITY', 'Nouméa');
Configuration::updateValue('PS_SHOP_COUNTRY_ID', $idNc ?: 8);
Configuration::updateValue('PS_SHOP_PHONE', '+687 46 55 46');
Configuration::updateValue('PS_SHOP_FAX', '');
Configuration::updateValue('PS_TIMEZONE', 'Pacific/Noumea');
Configuration::updateValue('PS_WEIGHT_UNIT', 'kg');
Configuration::updateValue('PS_DIMENSION_UNIT', 'cm');

/* ---------- Confort catalogue ---------- */
Configuration::updateValue('PS_PRODUCTS_PER_PAGE', 24);
Configuration::updateValue('PS_STOCK_MANAGEMENT', 1);
Configuration::updateValue('PS_ORDER_OUT_OF_STOCK', 0);   // pas de commande hors stock par défaut
Configuration::updateValue('PS_DISPLAY_PRODUCT_WEIGHT', 0);
Configuration::updateValue('PS_SEARCH_FUZZY', 1);
Configuration::updateValue('PS_CATALOG_MODE', 0);
Configuration::updateValue('PS_REWRITING_SETTINGS', 1);   // URL simplifiées
Configuration::updateValue('PS_CANONICAL_REDIRECT', 1);
Configuration::updateValue('PS_LOGO_UPDATED', time());
Configuration::updateValue('PS_ATTACHMENT_MAXIMUM_SIZE', 8);
Configuration::updateValue('PS_ALLOW_MOBILE_DEVICE', 1);

/* Nom de la boutique côté objet Shop */
$shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
$shop->name = 'Ludik.nc';
$shop->update();

/* ---------- Magasins physiques ---------- */
$stores = [
    [
        'name' => 'Ludik Vallée des Colons',
        'address1' => '107 rue Auguste Bénébig',
        'address2' => 'Vallée des Colons',
        'city' => 'Nouméa',
        'postcode' => '98800',
        'phone' => '46 55 46',
        'email' => 'librairie@ludik.nc',
        'lat' => -22.269810,
        'lng' => 166.454376,
        'note' => "Librairie spécialisée BD, mangas et jeunesse.\nPôle jeux de société pour toute la famille.",
        'hours' => ['08:30 - 18:30','08:30 - 18:30','08:30 - 18:30','08:30 - 18:30','08:30 - 18:30','09:00 - 18:30','Fermé'],
    ],
    [
        'name' => 'Ludik Michel-Ange',
        'address1' => '1 avenue Michel-Ange',
        'address2' => '',
        'city' => 'Nouméa',
        'postcode' => '98800',
        'phone' => '',
        'email' => 'contact@ludik.nc',
        'lat' => -22.257500,
        'lng' => 166.462800,
        'note' => "Tout l'univers du jeu de société, puzzles, jeux de rôle et jeux de cartes à collectionner.",
        'hours' => ['08:30 - 18:30','08:30 - 18:30','08:30 - 18:30','08:30 - 18:30','08:30 - 18:30','09:00 - 18:30','Fermé'],
    ],
];

$days = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'store_lang');
Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'store_shop');
Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'store');
foreach ($stores as $s) {
    $st = new Store();
    $st->id_country = $idNc ?: 8;
    $st->city = $s['city'];
    $st->postcode = $s['postcode'];
    $st->latitude = $s['lat'];
    $st->longitude = $s['lng'];
    $st->phone = $s['phone'];
    $st->email = $s['email'];
    $st->active = 1;
    $hoursJson = [];
    foreach ($days as $i => $d) {
        $hoursJson[] = $s['hours'][$i] === 'Fermé' ? [] : [$s['hours'][$i]];
    }
    $hoursJson = json_encode($hoursJson);
    foreach (Language::getLanguages(false) as $l) {
        $st->name[(int) $l['id_lang']] = $s['name'];
        $st->address1[(int) $l['id_lang']] = $s['address1'];
        $st->address2[(int) $l['id_lang']] = $s['address2'];
        $st->hours[(int) $l['id_lang']] = $hoursJson;
        $st->note[(int) $l['id_lang']] = $s['note'];
    }
    $st->add();
    say('Magasin créé : ' . $s['name'] . ' (#' . $st->id . ')');
}

say('Configuration terminée.');
