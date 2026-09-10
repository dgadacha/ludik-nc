<?php
/**
 * Diaporama de la page d'accueil : configure le module natif ps_imageslider
 * (« Diaporama page d'accueil ») et recrée les campagnes de départ.
 *
 * Tout est administrable depuis « Modules > Diaporama page d'accueil » :
 * l'image, le titre, l'accroche, le libellé du bouton et le lien. Les
 * campagnes créées ici sont des exemples réels, à ajuster par le client.
 * Le libellé du bouton est stocké dans le champ « Description » de la
 * diapositive : le module n'en propose pas d'autre.
 *
 * Le module doit être installé au préalable, de préférence par la console :
 *   docker exec ludik-ps php bin/console prestashop:module install ps_imageslider
 *
 * Le script est rejouable : il supprime les diapositives existantes, recopie
 * les visuels depuis /import/banners puis recrée les trois diapositives.
 *
 * Usage : docker exec ludik-ps php /scripts/12-slider.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();
$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
$langues = Language::getLanguages(false);

$module = Module::getInstanceByName('ps_imageslider');
if (!$module || !$module->id) {
    exit("module ps_imageslider absent : l'installer avec bin/console prestashop:module install ps_imageslider\n");
}

/* le script tourne en ligne de commande : on cale le contexte sur la boutique */
$contexte = Context::getContext();
if (empty($contexte->shop) || !$contexte->shop->id) {
    $contexte->shop = new Shop($idShop);
}
$contexte->language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

/* ------------------------------------------------------------------ */
/* 1. Accroche du module : displayHeader pour les assets, displayHome */
/*    pour l'affichage, en position 2 derrière ludikhome.             */
/* ------------------------------------------------------------------ */
foreach (['displayHeader', 'displayHome'] as $nomHook) {
    $idHook = (int) Hook::getIdByName($nomHook);
    if (!$idHook) {
        echo "hook inconnu : $nomHook\n";
        continue;
    }
    $existe = (int) $db->getValue(
        'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'hook_module
         WHERE id_module = ' . (int) $module->id . '
           AND id_hook = ' . $idHook . '
           AND id_shop = ' . $idShop
    );
    if (!$existe) {
        $module->registerHook($nomHook);
        echo "hook enregistré : $nomHook\n";
    }
}

/* renumérotation de displayHome : le diaporama prend 1, ludikhome 2,
   les autres blocs suivent dans leur ordre actuel */
$idHome = (int) Hook::getIdByName('displayHome');
if ($idHome) {
    /* le hero est la première chose que l'on doit voir : il passe devant
       les repères et les rayons produits par ludikhome */
    $ordreImpose = ['ps_imageslider', 'ludikhome'];
    $blocs = $db->executeS(
        'SELECT hm.id_module, m.name
           FROM ' . _DB_PREFIX_ . 'hook_module hm
           JOIN ' . _DB_PREFIX_ . 'module m ON m.id_module = hm.id_module
          WHERE hm.id_hook = ' . $idHome . ' AND hm.id_shop = ' . $idShop . '
          ORDER BY hm.position'
    );
    $ordonnes = [];
    foreach ($ordreImpose as $nom) {
        foreach ($blocs as $bloc) {
            if ($bloc['name'] === $nom) {
                $ordonnes[] = (int) $bloc['id_module'];
            }
        }
    }
    foreach ($blocs as $bloc) {
        if (!in_array((int) $bloc['id_module'], $ordonnes, true)) {
            $ordonnes[] = (int) $bloc['id_module'];
        }
    }
    $noms = [];
    foreach ($blocs as $bloc) {
        $noms[(int) $bloc['id_module']] = $bloc['name'];
    }
    $resume = [];
    foreach ($ordonnes as $rang => $idModule) {
        $db->update(
            'hook_module',
            ['position' => $rang + 1],
            'id_hook = ' . $idHome . ' AND id_shop = ' . $idShop . ' AND id_module = ' . $idModule
        );
        $resume[] = ($rang + 1) . '. ' . ($noms[$idModule] ?? $idModule);
    }
    echo 'ordre du hook displayHome : ' . implode(' | ', $resume) . "\n";
}

/* ------------------------------------------------------------------ */
/* 2. Configuration du module : dimensions attendues pour les visuels */
/* ------------------------------------------------------------------ */
Configuration::updateValue('HOMESLIDER_WIDTH', 2880);
Configuration::updateValue('HOMESLIDER_HEIGHT', 960);
echo "dimensions des visuels : 2880 x 960 px (1440 x 480 en densité double)\n";

/* ------------------------------------------------------------------ */
/* 3. Suppression des diapositives existantes (script rejouable)      */
/* ------------------------------------------------------------------ */
$anciennes = $db->executeS(
    'SELECT hs.id_homeslider_slides
       FROM ' . _DB_PREFIX_ . 'homeslider hs
      WHERE hs.id_shop = ' . $idShop
);
foreach ($anciennes as $ligne) {
    $diapo = new Ps_HomeSlide((int) $ligne['id_homeslider_slides']);
    if (Validate::isLoadedObject($diapo)) {
        $diapo->delete();
    }
}
echo 'diapositives supprimées : ' . count($anciennes) . "\n";

/* ------------------------------------------------------------------ */
/* 4. Copie des visuels dans le dossier images du module              */
/* ------------------------------------------------------------------ */
$dossierSource = '/import/banners';
$dossierCible = _PS_MODULE_DIR_ . 'ps_imageslider/images';
$fichiers = [
    'ludik-cartes-a-collectionner.jpg',
    'ludik-jeux-de-societe.jpg',
    'ludik-librairie.jpg',
    'ludik-jeux-de-role.jpg',
    'ludik-nouveautes.jpg',
    'ludik-soirees-jeux.jpg',
];
foreach ($fichiers as $fichier) {
    $source = $dossierSource . '/' . $fichier;
    if (!is_file($source)) {
        exit("visuel manquant : $source (lancer d'abord scripts/12-banners.sh)\n");
    }
    $cible = $dossierCible . '/' . $fichier;
    copy($source, $cible);
    @chmod($cible, 0644);
    @chown($cible, 'www-data');
    @chgrp($cible, 'www-data');
}
echo 'visuels copiés dans ' . $dossierCible . "\n";

/* ------------------------------------------------------------------ */
/* 5. Création des trois diapositives                                 */
/* ------------------------------------------------------------------ */
$lien = $contexte->link;
$base = $lien->getBaseLink();
$relatif = static fn (string $url): string => '/' . ltrim(str_replace($base, '', $url), '/');

$diapositives = [
    [
        'titre' => 'Cartes à collectionner | Plongez dans l\'univers des TCG',
        'legende' => 'Pokémon, Magic, One Piece, Altered, Star Wars Unlimited',
        'bouton' => 'Découvrir le rayon',
        'url' => $relatif($lien->getCategoryLink(5)),
        'image' => 'ludik-cartes-a-collectionner.jpg',
    ],
    [
        'titre' => 'Jeux de société | Des jeux pour toutes vos tables',
        'legende' => 'Des grands classiques aux sorties de la semaine, pour deux ou pour dix',
        'bouton' => 'Voir les jeux',
        'url' => $relatif($lien->getCategoryLink(4)),
        'image' => 'ludik-jeux-de-societe.jpg',
    ],
    [
        'titre' => 'Librairie | Vos séries, du tome 1 à l\'intégrale',
        'legende' => 'Séries suivies, nouveautés et intégrales à la Vallée des Colons',
        'bouton' => 'Entrer en librairie',
        'url' => $relatif($lien->getCategoryLink(6)),
        'image' => 'ludik-librairie.jpg',
    ],
    [
        'titre' => 'Jeux de rôle | De quoi lancer votre prochaine campagne',
        'legende' => 'Livres de base, écrans, suppléments et dés pour vos tables',
        'bouton' => 'Explorer le rayon',
        'url' => $relatif($lien->getCategoryLink(3)),
        'image' => 'ludik-jeux-de-role.jpg',
    ],
    [
        'titre' => 'Nouveautés | Les arrivages de la semaine',
        'legende' => 'BD, mangas et jeux, chaque semaine en rayon',
        'bouton' => 'Voir les nouveautés',
        'url' => $relatif($lien->getPageLink('new-products')),
        'image' => 'ludik-nouveautes.jpg',
    ],
    [
        'titre' => 'Événement | La soirée jeux du mardi',
        'legende' => 'Fronton Etchekhan, Amicale Basque, 18h45 à 22h',
        'bouton' => 'En savoir plus',
        'url' => $relatif($lien->getCMSLink(6)),
        'image' => 'ludik-soirees-jeux.jpg',
    ],
];

$position = 1;
foreach ($diapositives as $donnees) {
    $diapo = new Ps_HomeSlide();
    $diapo->position = $position;
    $diapo->active = 1;
    foreach ($langues as $langue) {
        $idLang = (int) $langue['id_lang'];
        $diapo->title[$idLang] = $donnees['titre'];
        $diapo->legend[$idLang] = $donnees['legende'];
        $diapo->description[$idLang] = $donnees['bouton'];
        $diapo->url[$idLang] = $donnees['url'];
        $diapo->image[$idLang] = $donnees['image'];
    }
    if (!$diapo->add()) {
        echo 'échec de la création : ' . $donnees['titre'] . "\n";
        continue;
    }
    echo 'diapositive ' . $position . ' : ' . $donnees['titre'] . ' → ' . $donnees['url'] . "\n";
    ++$position;
}

/* ------------------------------------------------------------------ */
/* 6. Purge des caches                                                */
/* ------------------------------------------------------------------ */
$module->clearCache();
Tools::clearSmartyCache();
Tools::clearAllCache();
echo "caches purgés\n";
