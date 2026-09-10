<?php
/**
 * Retrait de la page « Promotions ».
 *
 * Le catalogue importé ne porte aucune remise : ps_specific_price est vide, et
 * la page « Prix réduits » de PrestaShop n'affichait que « Aucun produit pour
 * le moment ». Elle a donc été retirée du site, avec la pastille jaune de la
 * barre de navigation et le lien de la colonne « Boutique » du pied de page.
 *
 * Ce script s'occupe de la partie base de données : il supprime la fiche
 * « SEO & URL » de la page, ce qui retire l'URL /promotions. Le contrôleur
 * prices-drop appartient au coeur de PrestaShop et ne peut pas être supprimé :
 * index.php?controller=prices-drop répond toujours, mais plus aucun lien du
 * site n'y mène et l'adresse lisible ne résout plus.
 *
 * Pour la rétablir : back-office « Configurer > Trafic & SEO », ajouter une
 * page avec l'identifiant prices-drop et l'URL simplifiée « promotions ».
 *
 * Usage : docker exec ludik-ps php /scripts/25-retirer-promotions.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();

/* garde-fou : si une remise apparaît un jour au catalogue, la page reprend du
   sens et le retrait mérite d'être rediscuté avant d'être rejoué */
$remises = (int) $db->getValue(
    'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price sp
     JOIN ' . _DB_PREFIX_ . 'product_shop ps ON ps.id_product = sp.id_product AND ps.active = 1'
);
if ($remises > 0 && !in_array('--yes', $argv, true)) {
    echo "$remises produit(s) actif(s) en promotion : la page a retrouvé un contenu.\n";
    echo "relancer avec --yes pour la retirer quand même\n";

    return;
}

$idMeta = (int) $db->getValue(
    'SELECT id_meta FROM ' . _DB_PREFIX_ . 'meta WHERE page = "prices-drop"'
);

if ($idMeta) {
    $meta = new Meta($idMeta);
    $meta->delete();
    echo "fiche SEO de la page prices-drop supprimée : /promotions ne résout plus\n";
} else {
    echo "aucune fiche SEO pour prices-drop : rien à faire\n";
}

/* le lien vivait aussi dans la colonne « Boutique » du pied de page, gérée par
   ps_linklist. 21-pied-de-page.php ne le pose plus ; ici on nettoie une base
   déjà en place. */
$blocs = $db->executeS('SELECT id_link_block, content FROM ' . _DB_PREFIX_ . 'link_block');
foreach ($blocs ?: [] as $bloc) {
    $contenu = json_decode($bloc['content'], true);
    if (!is_array($contenu) || empty($contenu['static'])) {
        continue;
    }

    $restant = array_values(array_diff($contenu['static'], ['prices-drop']));
    if (count($restant) === count($contenu['static'])) {
        continue;
    }

    $contenu['static'] = $restant;
    $db->update(
        'link_block',
        ['content' => pSQL(json_encode($contenu))],
        'id_link_block = ' . (int) $bloc['id_link_block']
    );
    echo 'lien retiré du bloc de pied de page ' . (int) $bloc['id_link_block'] . PHP_EOL;
}

Tools::clearAllCache();
echo "caches purgés\n";
