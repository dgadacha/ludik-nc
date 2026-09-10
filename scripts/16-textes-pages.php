<?php
/**
 * Intitulés des pages et derniers textes restés en anglais.
 * Usage : docker exec ludik-ps php /scripts/16-textes-pages.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$idLang = (int) Configuration::get('PS_LANG_DEFAULT');

/* ---------- intitulés de pages ---------- */
$titres = [
    'authentication' => ['Connexion', 'Connectez-vous à votre compte Ludik.nc'],
    'identity' => ['Mes informations', 'Vos informations personnelles'],
    'new-products' => ['Nouveautés', 'Les dernières arrivées en boutique, jeux comme librairie'],
    'best-sales' => ['Les plus vendus', 'Les références qui ressortent le plus en boutique'],
    'stores' => ['Nos boutiques', 'Ludik Vallée des Colons et Ludik Michel-Ange, à Nouméa'],
    'contact' => ['Nous écrire', 'Une question sur un jeu, une commande ou une série'],
    'pagenotfound' => ['Page introuvable', 'Cette page n\'existe pas ou plus'],
    'sitemap' => ['Plan du site', 'Toutes les pages et tous les rayons de la boutique'],
    'my-account' => ['Mon compte', 'Vos commandes, vos adresses et vos informations'],
    'registration' => ['Créer un compte', 'Ouvrez un compte pour suivre vos commandes'],
];
foreach ($titres as $page => [$titre, $description]) {
    $id = (int) $db->getValue('SELECT id_meta FROM ' . _DB_PREFIX_ . 'meta WHERE page = "' . pSQL($page) . '"');
    if (!$id) {
        continue;
    }
    $db->update('meta_lang', [
        'title' => pSQL($titre),
        'description' => pSQL($description),
    ], 'id_meta = ' . $id . ' AND id_lang = ' . $idLang);
    echo "page $page : $titre\n";
}

/* ---------- message de confidentialité du tunnel, en français ---------- */
$texte = "Les informations que vous transmettez servent au traitement de votre commande et à la "
    . "relation client. Vous pouvez les consulter, les modifier ou les supprimer depuis votre "
    . "compte, ou en écrivant à contact@ludik.nc.";
$n = 0;
foreach (['PS_DATA_PRIVACY', 'PSGDPR_CONSENT_MESSAGE'] as $cle) {
    $id = (int) $db->getValue('SELECT id_configuration FROM ' . _DB_PREFIX_ . 'configuration WHERE name = "' . $cle . '"');
    if ($id) {
        $db->update('configuration_lang', ['value' => pSQL($texte)], 'id_configuration = ' . $id);
        ++$n;
    }
}
echo "message de confidentialité en français : $n entrée(s)\n";

echo "Intitulés et textes appliqués.\n";
