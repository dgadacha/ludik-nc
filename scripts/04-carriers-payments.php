<?php
/**
 * Transporteurs et moyens de paiement Ludik.
 *
 *  - Retrait à Ludik Vallée des Colons (gratuit)
 *  - Retrait à Ludik Michel-Ange (gratuit)
 *  - Retrait à la soirée jeux du mardi (gratuit)
 *  - Colis OPT Nouvelle-Calédonie (au poids)
 *  - Colis OPT international (au poids)
 *
 * Les tranches de poids OPT sont des valeurs de départ, à ajuster avec les tarifs réels.
 * Usage : docker exec ludik-ps php /scripts/04-carriers-payments.php
 */
require_once '/var/www/html/config/config.inc.php';

$langs = array_map(fn($l) => (int) $l['id_lang'], Language::getLanguages(false));
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
$idNc = (int) Country::getByIso('NC');

/* ---------- Zone Nouvelle-Calédonie ---------- */
$zones = Zone::getZones(false);
$idZoneNc = 0;
foreach ($zones as $z) {
    if ($z['name'] === 'Nouvelle-Calédonie') { $idZoneNc = (int) $z['id_zone']; }
}
if (!$idZoneNc) {
    $z = new Zone();
    $z->name = 'Nouvelle-Calédonie';
    $z->active = 1;
    $z->add();
    $idZoneNc = (int) $z->id;
    echo "Zone Nouvelle-Calédonie créée (#$idZoneNc)\n";
}
if ($idNc) {
    $c = new Country($idNc);
    $c->id_zone = $idZoneNc;
    $c->active = 1;
    $c->update();
}

/* zones internationales : tout sauf la zone NC */
$otherZones = [];
foreach (Zone::getZones(false) as $z) {
    if ((int) $z['id_zone'] !== $idZoneNc) { $otherZones[] = (int) $z['id_zone']; }
}

/* ---------- Transporteurs ---------- */
$carriers = [
    [
        'name' => 'Retrait à Ludik Vallée des Colons',
        'delay' => 'Mis de côté dès réception de la commande - 107 rue Auguste Bénébig',
        'free' => true, 'zones' => [$idZoneNc], 'ranges' => [],
        'pickup' => true,
    ],
    [
        'name' => 'Retrait à Ludik Michel-Ange',
        'delay' => 'Mis de côté dès réception de la commande - 1 avenue Michel-Ange',
        'free' => true, 'zones' => [$idZoneNc], 'ranges' => [],
        'pickup' => true,
    ],
    [
        'name' => 'Retrait à la soirée jeux du mardi',
        'delay' => 'Au Fronton Etchekhan (Amicale Basque), le mardi - entrée offerte',
        'free' => true, 'zones' => [$idZoneNc], 'ranges' => [],
        'pickup' => true,
    ],
    [
        'name' => 'Colis OPT - Nouvelle-Calédonie',
        'delay' => 'Expédition sous 24h, tarif au poids (Grande Terre et îles)',
        'free' => false, 'zones' => [$idZoneNc],
        'ranges' => [[0, 1, 500], [1, 3, 900], [3, 5, 1500], [5, 10, 2500], [10, 30, 4500]],
        'pickup' => false,
    ],
    [
        'name' => 'Colis OPT - International',
        'delay' => 'Pacifique et pays francophones, expédition sous 24h, tarif au poids',
        'free' => false, 'zones' => $otherZones,
        'ranges' => [[0, 1, 2200], [1, 3, 4200], [3, 5, 6800], [5, 10, 11500], [10, 30, 21000]],
        'pickup' => false,
    ],
];

$existing = [];
foreach (Carrier::getCarriers($langs[0], false, false, false, null, Carrier::ALL_CARRIERS) as $c) {
    $existing[$c['name']] = (int) $c['id_carrier'];
}

foreach ($carriers as $def) {
    if (isset($existing[$def['name']])) {
        echo "déjà présent : {$def['name']}\n";
        continue;
    }
    $c = new Carrier();
    $c->name = $def['name'];
    $c->active = 1;
    $c->is_free = $def['free'];
    $c->shipping_handling = false;
    $c->shipping_external = false;
    $c->range_behavior = 1;                       // hors plage : tarif le plus élevé
    $c->shipping_method = Carrier::SHIPPING_METHOD_WEIGHT;
    $c->need_range = $def['free'] ? 0 : 1;
    $c->grade = 0;
    $c->max_width = 0; $c->max_height = 0; $c->max_depth = 0;
    $c->max_weight = 0; $c->grade = 0;
    foreach ($langs as $l) { $c->delay[$l] = $def['delay']; }
    $c->add();

    foreach ($def['zones'] as $z) { $c->addZone((int) $z); }

    if ($def['free']) {
        // une plage à 0 F pour que le transporteur soit proposé
        $r = new RangeWeight();
        $r->id_carrier = (int) $c->id;
        $r->delimiter1 = 0; $r->delimiter2 = 1000;
        $r->add();
        foreach ($def['zones'] as $z) {
            Db::getInstance()->insert('delivery', [
                'id_carrier' => (int) $c->id, 'id_range_price' => null,
                'id_range_weight' => (int) $r->id, 'id_zone' => (int) $z, 'price' => 0,
            ], false, true, Db::INSERT_IGNORE);
        }
    } else {
        foreach ($def['ranges'] as [$from, $to, $price]) {
            $r = new RangeWeight();
            $r->id_carrier = (int) $c->id;
            $r->delimiter1 = $from; $r->delimiter2 = $to;
            $r->add();
            foreach ($def['zones'] as $z) {
                Db::getInstance()->insert('delivery', [
                    'id_carrier' => (int) $c->id, 'id_range_price' => null,
                    'id_range_weight' => (int) $r->id, 'id_zone' => (int) $z, 'price' => (float) $price,
                ], false, true, Db::INSERT_IGNORE);
            }
        }
    }
    echo "transporteur créé : {$def['name']} (#{$c->id})\n";
}

/* transporteurs de démonstration désactivés */
foreach (Carrier::getCarriers($langs[0], false, false, false, null, Carrier::ALL_CARRIERS) as $c) {
    if (in_array($c['name'], ['My carrier', 'My cheap carrier', 'My light carrier', 'Mon transporteur'], true)) {
        $cc = new Carrier((int) $c['id_carrier']);
        $cc->active = 0;
        $cc->deleted = 1;
        $cc->update();
        echo "transporteur de démonstration retiré : {$c['name']}\n";
    }
}

/* poids par défaut des articles pour que les tranches OPT fonctionnent */
Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'product SET weight = 0.45 WHERE weight = 0');
Configuration::updateValue('PS_SHIPPING_FREE_PRICE', 0);
Configuration::updateValue('PS_SHIPPING_HANDLING', 0);
Configuration::updateValue('PS_SHIPPING_METHOD', 0);

/* ---------- Moyens de paiement ---------- */
foreach (['ps_checkpayment', 'ps_wirepayment'] as $m) {
    if (!Module::isInstalled($m)) {
        if (Module::install($m)) { echo "module installé : $m\n"; }
    } elseif (!Module::isEnabled($m)) {
        Module::enableByName($m);
        echo "module activé : $m\n";
    } else {
        echo "module déjà actif : $m\n";
    }
}

Configuration::updateValue('CHEQUE_NAME', 'LUDIK SARL');
Configuration::updateValue('CHEQUE_ADDRESS', "107 rue Auguste Bénébig\nVallée des Colons\n98800 Nouméa");
Configuration::updateValue('BANK_WIRE_OWNER', 'LUDIK SARL');
Configuration::updateValue('BANK_WIRE_DETAILS', "Coordonnées bancaires communiquées avec la confirmation de commande.\nMerci d'indiquer la référence de commande dans le libellé du virement.");
Configuration::updateValue('BANK_WIRE_ADDRESS', "107 rue Auguste Bénébig\nVallée des Colons\n98800 Nouméa");

echo "Transporteurs et paiements configurés.\n";
