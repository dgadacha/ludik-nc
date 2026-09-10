<?php
/**
 * Pages éditoriales du site : reprise des contenus de l'ancien site + page « soirées jeux ».
 * Usage : docker exec ludik-ps php /scripts/05-cms-pages.php
 */
require_once '/var/www/html/config/config.inc.php';

$langs = array_map(fn($l) => (int) $l['id_lang'], Language::getLanguages(false));
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
$imported = json_decode(file_get_contents('/import/cms.json'), true);

/* page rédigée pour l'occasion : les soirées jeux du mardi */
$imported['soirees-jeux'] = [
    'title' => 'Les soirées Ludik',
    'meta' => "Chaque mardi, Ludik propose une soirée jeux de société ouverte à tous au Fronton Etchekhan (Amicale Basque, Motor Pool) à Nouméa.",
    /* Contenu constitué uniquement d'éléments publiés sur le site actuel :
       page « A propos », page « Commande et Livraison » et bandeau de l'accueil. */
    'content' => <<<'HTML'
<h2>Une soirée ouverte à tous, chaque mardi</h2>
<p>Pour découvrir les jeux, Ludik propose chaque mardi une soirée ouverte à tous, au <strong>Fronton Etchekhan</strong> (Amicale Basque, Motor Pool) à Nouméa.</p>
<ul>
<li><strong>Quand</strong> : chaque mardi, de 18h45 à 22h.</li>
<li><strong>Où</strong> : Fronton Etchekhan - Amicale Basque, Motor Pool, Nouméa.</li>
</ul>
<h2>Retirer une commande à la soirée</h2>
<p>Parmi les modes de livraison proposés lors de la commande figure le <strong>retrait lors des soirées Ludik</strong>, chaque mardi au Fronton Etchekhan. Votre entrée à la soirée vous est alors offerte.</p>
<h2>Nous suivre</h2>
<p>Les dates et les informations pratiques sont annoncées sur <a href="https://www.facebook.com/ludik.ncal" target="_blank" rel="noopener">notre page Facebook</a>.</p>
HTML,
];

/* nettoyage des pages de démonstration */
foreach (CMS::listCms((int) Configuration::get('PS_LANG_DEFAULT')) as $c) {
    $slugs = array_keys($imported);
    $meta = new CMS((int) $c['id_cms']);
    $rw = is_array($meta->link_rewrite) ? reset($meta->link_rewrite) : $meta->link_rewrite;
    if (!in_array($rw, $slugs, true) && !in_array($c['meta_title'], ['Livraison', 'Mentions légales', 'Conditions générales de vente', 'À propos', 'Paiement sécurisé'], true)) {
        // pages « Colonne vide » et autres pages de démo
    }
}

$order = ['a-propos', 'soirees-jeux', 'livraison', 'paiement', 'comment-choisir-un-jeu',
          'conditions-generales-de-vente', 'mentions-legales'];

$existing = [];
foreach (Db::getInstance()->executeS('SELECT c.id_cms, cl.link_rewrite FROM ' . _DB_PREFIX_ . 'cms c JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON c.id_cms = cl.id_cms') as $r) {
    $existing[$r['link_rewrite']] = (int) $r['id_cms'];
}

$pos = 0;
foreach ($order as $slug) {
    $p = $imported[$slug];
    $cms = isset($existing[$slug]) ? new CMS((int) $existing[$slug]) : new CMS();
    $cms->id_cms_category = 1;
    $cms->active = 1;
    $cms->indexation = 1;
    $cms->position = $pos++;
    foreach ($langs as $l) {
        $cms->meta_title[$l] = $p['title'];
        $cms->head_seo_title[$l] = $p['title'] . ' - Ludik.nc';
        $cms->meta_description[$l] = $p['meta'];
        $cms->meta_keywords[$l] = '';
        $cms->link_rewrite[$l] = $slug;
        $cms->content[$l] = $p['content'];
    }
    if ($cms->id) { $cms->update(); echo "mise à jour : $slug\n"; }
    else { $cms->add(); echo "création : $slug (#{$cms->id})\n"; }
}

/* désactiver les pages de démonstration restantes */
$keep = "'" . implode("','", $order) . "'";
$demo = Db::getInstance()->executeS(
    'SELECT c.id_cms, cl.link_rewrite, cl.meta_title FROM ' . _DB_PREFIX_ . 'cms c
     JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT') . '
     WHERE cl.link_rewrite NOT IN (' . $keep . ')'
);
foreach ($demo as $d) {
    $c = new CMS((int) $d['id_cms']);
    $c->delete();
    echo "page de démonstration supprimée : {$d['meta_title']}\n";
}

echo "Pages éditoriales en place.\n";
