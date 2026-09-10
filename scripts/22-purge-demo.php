<?php
/**
 * Retire les données de démonstration de PrestaShop restées après la reprise.
 *
 * L'installation livre cinq commandes fictives au nom de « John DOE », portant
 * des t-shirts Hummingbird en euros. Le catalogue de démonstration a été vidé
 * par 03-reset-catalog.php, mais pas ces commandes : elles polluaient la liste
 * des commandes du back-office et surtout les statistiques de vente, d'où deux
 * « meilleures ventes » sans rapport avec la boutique.
 *
 * Le script ne touche qu'aux commandes du client de démonstration, repéré par
 * son adresse (john.doe@prestashop.com ou nom John DOE), et refuse d'agir sans
 * confirmation explicite.
 *
 * Usage : docker exec ludik-ps php /scripts/22-purge-demo.php --yes
 */
require_once '/var/www/html/config/config.inc.php';

/**
 * Retire les lignes de commande orphelines et recalcule les statistiques.
 *
 * Order::delete() laisse des lignes de détail sans commande, dont
 * fillProductSales() se ressert : les « meilleures ventes » restaient
 * alimentées par des commandes disparues.
 */
function nettoyerStatistiques(): void
{
    $db = Db::getInstance();

    $orphelines = (int) $db->getValue(
        'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'order_detail od
          LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = od.id_order
          WHERE o.id_order IS NULL'
    );
    if ($orphelines) {
        $db->execute(
            'DELETE od FROM ' . _DB_PREFIX_ . 'order_detail od
              LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = od.id_order
              WHERE o.id_order IS NULL'
        );
        echo 'lignes de commande orphelines supprimées : ' . $orphelines . PHP_EOL;
    }

    $db->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'product_sale');
    ProductSale::fillProductSales();
    echo 'statistiques de vente : '
        . (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_sale')
        . " lignes\n";

    Tools::clearSmartyCache();
    Tools::clearAllCache();
}

$db = Db::getInstance();

$idsClients = array_column(
    $db->executeS(
        'SELECT id_customer FROM ' . _DB_PREFIX_ . 'customer
          WHERE email = "john.doe@prestashop.com"
             OR (firstname = "John" AND lastname = "DOE")'
    ) ?: [],
    'id_customer'
);

$commandes = $idsClients
    ? array_column(
        $db->executeS(
            'SELECT id_order, reference FROM ' . _DB_PREFIX_ . 'orders
              WHERE id_customer IN (' . implode(',', array_map('intval', $idsClients)) . ')'
        ) ?: [],
        'reference',
        'id_order'
    )
    : [];

if (!$commandes) {
    echo "aucune commande de démonstration\n";
    nettoyerStatistiques();

    return;
}

echo 'commandes de démonstration trouvées : ' . implode(', ', $commandes) . PHP_EOL;

if (!in_array('--yes', $argv, true)) {
    echo "relancer avec --yes pour les supprimer\n";

    return;
}

foreach (array_keys($commandes) as $idOrder) {
    $commande = new Order((int) $idOrder);
    if (Validate::isLoadedObject($commande)) {
        $commande->delete();
        echo 'commande supprimée : ' . $commandes[$idOrder] . PHP_EOL;
    }
}
nettoyerStatistiques();
echo "caches purgés\n";
