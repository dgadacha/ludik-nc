<?php
/**
 * Compose la page d'accueil : blocs maison en tête, blocs de démonstration retirés.
 * Usage : docker exec ludik-ps php /scripts/07-home-blocks.php
 */
require_once '/var/www/html/config/config.inc.php';

/* modules de démonstration retirés de la page d'accueil.
   ps_imageslider est volontairement conservé : c'est le diaporama de
   bannières, configuré par 12-slider.php. */
foreach (['ps_banner', 'ps_customtext'] as $name) {
    if (Module::isInstalled($name)) {
        $m = Module::getInstanceByName($name);
        if ($m && $m->id) {
            Db::getInstance()->execute(
                'DELETE FROM ' . _DB_PREFIX_ . 'hook_module WHERE id_module = ' . (int) $m->id
            );
            echo "retiré des hooks : $name\n";
        }
    }
}

/* notre module en premier sur la page d'accueil */
$idHome = (int) Hook::getIdByName('displayHome');
$lud = Module::getInstanceByName('ludikhome');
if ($lud && $lud->id && $idHome) {
    Db::getInstance()->execute(
        'UPDATE ' . _DB_PREFIX_ . 'hook_module SET position = position + 1
         WHERE id_hook = ' . $idHome . ' AND id_module <> ' . (int) $lud->id
    );
    Db::getInstance()->execute(
        'UPDATE ' . _DB_PREFIX_ . 'hook_module SET position = 1
         WHERE id_hook = ' . $idHome . ' AND id_module = ' . (int) $lud->id
    );
    echo "ludikhome placé en tête de la page d'accueil\n";
}

/* nombre de produits affichés dans les blocs de la page d'accueil */
Configuration::updateValue('HOME_FEATURED_NBR', 8);
Configuration::updateValue('NEW_PRODUCTS_NBR', 8);
Configuration::updateValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', 8);
Configuration::updateValue('BLOCKSPECIALS_SPECIALS_NBR', 8);
Configuration::updateValue('PS_NB_DAYS_NEW_PRODUCT', 60);
Configuration::updateValue('CATEGORYPRODUCTS_DISPLAY_PRODUCTS', 8);
Configuration::updateValue('PRODUCTS_VIEWED_NBR', 8);

/* modules utiles activés */
foreach (['ps_newproducts', 'ps_bestsellers', 'ps_featuredproducts', 'ps_facetedsearch',
          'ps_searchbar', 'ps_mainmenu', 'blockreassurance', 'ps_contactinfo',
          'ps_shoppingcart', 'ps_customersignin', 'ps_linklist', 'ps_emailsubscription',
          'ps_socialfollow', 'ps_categoryproducts', 'ps_crossselling', 'gsitemap'] as $name) {
    if (!Module::isInstalled($name)) {
        Module::install($name) && print("installé : $name\n");
    } elseif (!Module::isEnabled($name)) {
        Module::enableByName($name) && print("activé : $name\n");
    }
}

/* liens du pied de page */
Configuration::updateValue('LUDIKHOME_COUNTS_TS', 0);

/* réseaux sociaux */
Configuration::updateValue('PS_SF_FACEBOOK', 'https://www.facebook.com/ludik.ncal');
Configuration::updateValue('PS_SF_TWITTER', '');
Configuration::updateValue('PS_SF_INSTAGRAM', '');
Configuration::updateValue('PS_SF_YOUTUBE', '');
Configuration::updateValue('PS_SF_LINKEDIN', '');
Configuration::updateValue('PS_SF_PINTEREST', '');
Configuration::updateValue('PS_SF_RSS', '');

echo "Page d'accueil composée.\n";
