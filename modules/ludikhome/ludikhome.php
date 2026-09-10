<?php
/**
 * Blocs éditoriaux de la page d'accueil Ludik.nc :
 *  - une accroche avec les chiffres du catalogue,
 *  - les univers du catalogue,
 *  - la soirée jeux du mardi et les deux boutiques.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ludikhome extends Module
{
    /**
     * Les rayons du catalogue, dans l'ordre de la boutique.
     *
     * « tone » nomme l'accent visuel du rayon : la couleur de son pictogramme
     * et de son nombre de produits. Le vert Ludik reste la couleur du site.
     *
     * « vitrine » liste des fragments de nom de produit, essayés dans l'ordre
     * pour choisir le visuel de la carte. L'ordre n'est pas arbitraire, il
     * suit deux mesures relevées sur la déclinaison affichée :
     *  - le remplissage, part de l'image occupée par le produit : les
     *    packshots portent leur propre marge blanche, très variable, et un
     *    produit photographié de loin paraît minuscule à côté d'un autre
     *    cadré serré (7 Wonders occupe 51 % de son image, Catan 10 %) ;
     *  - la clarté : les cartes sont blanches, un produit sombre y creuse un
     *    trou. C'est ce qui écarte le coffret dresseur d'élite, un boîtier
     *    noir, du rayon des cartes à collectionner.
     * Le relevé complet est dans docs/DONNEES.md.
     */
    public const UNIVERS = [
        ['name' => 'Jeux de société', 'tone' => 'u-societe', 'icon' => 'dices',
         'vitrine' => ['7 Wonders', 'Carcassonne', 'Dixit', 'Catan']],
        ['name' => 'Jeux de cartes à collectionner', 'tone' => 'u-tcg', 'icon' => 'layers',
         'vitrine' => ['Coffret Académie de Combat', 'Pack 3 Boosters Pokémon', 'Starter']],
        ['name' => 'Librairie', 'tone' => 'u-manga', 'icon' => 'book-open',
         'vitrine' => ['Naruto - Tome 1', 'One Piece - Tome 1', 'Astérix']],
        ['name' => 'Jeux de Rôle', 'tone' => 'u-jdr', 'icon' => 'swords',
         'vitrine' => ['Pathfinder', 'Appel de Cthulhu']],
        ['name' => 'Accessoires Jeux & JCC', 'tone' => 'u-creatif', 'icon' => 'package',
         'vitrine' => ['Tapis de jeu', 'Protèges']],
        ['name' => 'Loisirs Créatifs', 'tone' => 'u-bd', 'icon' => 'palette',
         'vitrine' => ['Origami', 'Speedpaint', 'Book Nook']],
    ];

    public function __construct()
    {
        $this->name = 'ludikhome';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Tealforge';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->trans('Blocs page d\'accueil Ludik', [], 'Modules.Ludikhome.Admin');
        $this->description = $this->trans('Accroche, univers, soirée jeux et boutiques sur la page d\'accueil.', [], 'Modules.Ludikhome.Admin');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayFooterBefore');
    }

    /** Rayons, leurs sous-rayons et le nombre de produits, mis en cache une heure. */
    private function catalogCounts(): array
    {
        if (!Configuration::get('LUDIKHOME_COUNTS_TS')
            || (time() - (int) Configuration::get('LUDIKHOME_COUNTS_TS')) > 3600) {
            $db = Db::getInstance();
            $counts = ['total' => (int) $db->getValue(
                'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p
                 JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                 WHERE ps.active = 1'
            )];
            foreach (self::UNIVERS as $i => $u) {
                $cat = $this->findCategory($u['name']);
                $counts[$i] = [
                    'count' => $cat ? $this->countInTree($cat) : 0,
                    'link' => $cat
                        ? $this->context->link->getCategoryLink((int) $cat['id_category'], $cat['link_rewrite'])
                        : '',
                    'children' => $cat ? $this->childNames((int) $cat['id_category']) : [],
                    'nb_children' => $cat ? $this->countChildren((int) $cat['id_category']) : 0,
                    /* un seul visuel par carte, choisi parmi les références
                       emblématiques du rayon : le premier produit venu ne
                       représentait pas ce qu'on y trouve */
                    'covers' => $cat ? $this->treeCovers($cat, 1, $u['vitrine'] ?? []) : [],
                ];
            }
            Configuration::updateValue('LUDIKHOME_COUNTS', json_encode($counts));
            Configuration::updateValue('LUDIKHOME_COUNTS_TS', time());
        }

        $raw = json_decode((string) Configuration::get('LUDIKHOME_COUNTS'), true);

        return is_array($raw) ? $raw : ['total' => 0];
    }

    /** Les premiers sous-rayons, par volume de catalogue. */
    private function childNames(int $idCategory, int $limit = 5): array
    {
        $idLang = (int) $this->context->language->id;
        $rows = Db::getInstance()->executeS(
            'SELECT cl.name, COUNT(cp.id_product) nb
             FROM ' . _DB_PREFIX_ . 'category c
             JOIN ' . _DB_PREFIX_ . 'category_lang cl
               ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . '
             LEFT JOIN ' . _DB_PREFIX_ . 'category c2
               ON c2.nleft >= c.nleft AND c2.nright <= c.nright
             LEFT JOIN ' . _DB_PREFIX_ . 'category_product cp ON cp.id_category = c2.id_category
             WHERE c.id_parent = ' . $idCategory . ' AND c.active = 1
             GROUP BY c.id_category, cl.name
             ORDER BY nb DESC
             LIMIT ' . $limit
        );

        return $rows ? array_column($rows, 'name') : [];
    }

    /**
     * Visuels illustrant la carte d'un rayon.
     *
     * On cherche d'abord une référence emblématique : les fragments de nom
     * passés en « vitrine » sont essayés dans l'ordre, et le premier produit
     * en stock qui correspond gagne. Sans correspondance, on retombe sur un
     * produit du rayon pris au hasard - c'était le comportement précédent, et
     * il donnait parfois une licorne pour les jeux de société.
     *
     * @param string[] $vitrine fragments de nom, du plus au moins souhaité
     */
    private function treeCovers(array $cat, int $limit = 4, array $vitrine = []): array
    {
        foreach ($vitrine as $fragment) {
            $rows = $this->coversQuery($cat, $limit, $fragment);
            if ($rows) {
                return $rows;
            }
        }

        return $this->coversQuery($cat, $limit);
    }

    /** @return string[] liens d'image */
    private function coversQuery(array $cat, int $limit, ?string $fragment = null): array
    {
        $idLang = (int) $this->context->language->id;
        $filtre = $fragment === null
            ? ''
            : ' AND pl.name LIKE "%' . pSQL($fragment) . '%"';
        /* le plus court des noms contenant le fragment : « Naruto - Tome 1 »
           désigne un tome, pas un coffret intégrale. À longueur égale,
           l'identifiant départage, sinon la carte changeait de visuel d'une
           reconstruction de cache à l'autre. */
        $ordre = $fragment === null
            ? 'RAND()'
            : 'CHAR_LENGTH(pl.name) ASC, cp.id_product ASC';

        $rows = Db::getInstance()->executeS(
            'SELECT pl.link_rewrite, i.id_image
             FROM ' . _DB_PREFIX_ . 'category c
             JOIN ' . _DB_PREFIX_ . 'category_product cp ON cp.id_category = c.id_category
             JOIN ' . _DB_PREFIX_ . 'product_shop ps ON ps.id_product = cp.id_product AND ps.active = 1
             JOIN ' . _DB_PREFIX_ . 'product_lang pl ON pl.id_product = cp.id_product AND pl.id_lang = ' . $idLang . '
             JOIN ' . _DB_PREFIX_ . 'image i ON i.id_product = cp.id_product AND i.cover = 1
             JOIN ' . _DB_PREFIX_ . 'stock_available sa ON sa.id_product = cp.id_product AND sa.quantity > 0
             WHERE c.nleft >= ' . (int) $cat['nleft'] . ' AND c.nright <= ' . (int) $cat['nright'] . $filtre . '
             GROUP BY cp.id_product
             ORDER BY ' . $ordre . '
             LIMIT ' . (int) $limit
        );

        $out = [];
        foreach ($rows ?: [] as $r) {
            $out[] = $this->context->link->getImageLink($r['link_rewrite'], (int) $r['id_image'], 'large_default');
        }

        return $out;
    }

    private function countChildren(int $idCategory): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category
             WHERE id_parent = ' . $idCategory . ' AND active = 1'
        );
    }

    private function findCategory(string $name): ?array
    {
        $idLang = (int) $this->context->language->id;
        $row = Db::getInstance()->getRow(
            'SELECT c.id_category, cl.link_rewrite, c.nleft, c.nright
             FROM ' . _DB_PREFIX_ . 'category c
             JOIN ' . _DB_PREFIX_ . 'category_lang cl
               ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . '
             WHERE cl.name = "' . pSQL($name) . '" AND c.active = 1
             ORDER BY c.level_depth ASC'
        );

        return $row ?: null;
    }

    private function countInTree(array $cat): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT cp.id_product)
             FROM ' . _DB_PREFIX_ . 'category_product cp
             JOIN ' . _DB_PREFIX_ . 'category c ON c.id_category = cp.id_category
             JOIN ' . _DB_PREFIX_ . 'product_shop ps ON ps.id_product = cp.id_product AND ps.active = 1
             WHERE c.nleft >= ' . (int) $cat['nleft'] . ' AND c.nright <= ' . (int) $cat['nright']
        );
    }

    /** Trois couvertures pour la vitrine du bandeau d'accroche. */
    private function heroCovers(): array
    {
        $idLang = (int) $this->context->language->id;
        $rows = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name, pl.link_rewrite, i.id_image
             FROM ' . _DB_PREFIX_ . 'product p
             JOIN ' . _DB_PREFIX_ . 'product_shop ps ON ps.id_product = p.id_product AND ps.active = 1
             JOIN ' . _DB_PREFIX_ . 'product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = ' . $idLang . '
             JOIN ' . _DB_PREFIX_ . 'image i ON i.id_product = p.id_product AND i.cover = 1
             JOIN ' . _DB_PREFIX_ . 'stock_available sa ON sa.id_product = p.id_product AND sa.quantity > 0
             ORDER BY p.date_add DESC, p.id_product DESC LIMIT 24'
        );
        if (!$rows) {
            return [];
        }
        $out = [];
        foreach (array_slice($rows, 0, 3) as $r) {
            $out[] = [
                'name' => $r['name'],
                'url' => $this->context->link->getProductLink((int) $r['id_product'], $r['link_rewrite']),
                'img' => $this->context->link->getImageLink($r['link_rewrite'], (int) $r['id_image'], 'default_xl'),
            ];
        }

        return $out;
    }

    /** Tuiles de rayons affichées sous la navigation, comme sur les maquettes. */
    /**
     * Élargit le séparateur de milliers des prix.
     *
     * Le franc pacifique se compose « 4 300 F ». Le CLDR français sépare les
     * milliers par l'espace fine insécable U+202F, qui mesure deux pixels à la
     * taille du corps de texte : le prix se lisait « 4300 F ». On la remplace
     * par l'espace insécable ordinaire U+00A0, plus large et tout aussi
     * insécable.
     *
     * Le motif vient des fichiers de locale de PrestaShop, en lecture seule :
     * ni la devise ni la langue ne permettent de le changer en back-office.
     * Ce hook est le seul point par lequel passe tout le HTML d'une page.
     *
     * Les fragments rechargés en Ajax ne passent pas ici : assets/js/custom.js
     * fait le même remplacement après un filtrage ou une mise à jour du panier.
     */
    public function hookActionOutputHTMLBefore(array $params): void
    {
        if (!isset($params['html'])) {
            return;
        }

        $params['html'] = str_replace("\xE2\x80\xAF", "\xC2\xA0", $params['html']);
    }

    public function hookDisplayHome(): string
    {
        $counts = $this->catalogCounts();
        $univers = [];
        foreach (self::UNIVERS as $i => $u) {
            $c = $counts[$i] ?? null;
            if (!$c || empty($c['count'])) {
                continue;
            }
            $univers[] = [
                'title' => $u['name'],
                'tone' => $u['tone'],
                'icon' => $u['icon'],
                'count' => (int) $c['count'],
                'link' => $c['link'],
                'children' => $c['children'],
                'more' => (int) $c['nb_children'] > count($c['children']),
                'covers' => $c['covers'] ?? [],
            ];
        }

        $this->context->smarty->assign([
            'ludik_total' => (int) ($counts['total'] ?? 0),
            'ludik_univers' => $univers,
            'ludik_new_link' => $this->context->link->getPageLink('new-products'),
            'ludik_best_link' => $this->context->link->getPageLink('best-sales'),
            'ludik_soiree_link' => $this->cmsLink('soirees-jeux'),
            'ludik_covers' => $this->heroCovers(),
        ]);

        return $this->fetch('module:ludikhome/views/templates/hook/home.tpl');
    }

    /**
     * Regroupe les horaires d'ouverture en plages de jours consécutifs.
     *
     * PrestaShop 9 stocke sept entrées, du lundi au dimanche. Les afficher une
     * par ligne donnait six lignes identiques à un caractère près. On regroupe
     * donc les jours qui se suivent et partagent le même horaire :
     *   « Lundi au vendredi 9h30 – 18h30 », « Samedi 9h – 18h30 ».
     * Les jours fermés ne sont pas listés : ils se déduisent des autres.
     *
     * @return array<int, array{jours: string, plage: string}>
     */
    private function horairesGroupes(string $json): array
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $courts = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        $brut = json_decode($json, true);
        if (!is_array($brut)) {
            return [];
        }

        /* normalisation : « 09:30 - 18:30 » devient « 9h30 – 18h30 » */
        $plages = [];
        foreach (array_values($brut) as $i => $plage) {
            $plage = trim(is_array($plage) ? implode(' - ', $plage) : (string) $plage);
            if ($plage === '') {
                $plages[$i] = '';
                continue;
            }
            $plage = preg_replace('/\b0(\d)[:h](\d{2})\b/', '$1h$2', $plage);
            $plage = preg_replace('/\b(\d{1,2})[:h]00\b/', '$1h', $plage);
            $plage = str_replace([':', ' - '], ['h', ' – '], $plage);
            $plages[$i] = $plage;
        }

        $groupes = [];
        $debut = null;
        for ($i = 0; $i <= 7; ++$i) {
            $plage = $plages[$i] ?? '';
            $precedente = $debut === null ? null : $plages[$debut];

            if ($plage !== '' && $debut === null) {
                $debut = $i;
                continue;
            }
            if ($plage === $precedente) {
                continue;
            }

            /* la plage en cours s'arrête à $i - 1 */
            if ($debut !== null) {
                $fin = $i - 1;
                $groupes[] = [
                    'jours' => $debut === $fin
                        ? $jours[$debut]
                        : $courts[$debut] . ' au ' . mb_strtolower($courts[$fin]),
                    'plage' => $precedente,
                ];
            }
            $debut = $plage === '' ? null : $i;
        }

        return $groupes;
    }

    /**
     * Les deux boutiques, telles qu'elles sont enregistrées en back-office.
     *
     * Adresse, téléphone, courriel, horaires et photo viennent de la fiche
     * magasin : le client les modifie depuis « Préférences > Magasins », sans
     * toucher au thème. Les horaires sont stockés en JSON par PrestaShop 9,
     * un tableau de sept entrées, du lundi au dimanche.
     */
    private function boutiques(): array
    {
        $idLang = (int) $this->context->language->id;
        $lignes = Db::getInstance()->executeS(
            'SELECT s.id_store, s.phone, s.email, s.city, s.postcode,
                    sl.name, sl.address1, sl.address2, sl.hours, sl.note
               FROM ' . _DB_PREFIX_ . 'store s
               JOIN ' . _DB_PREFIX_ . 'store_lang sl
                 ON sl.id_store = s.id_store AND sl.id_lang = ' . $idLang . '
              WHERE s.active = 1
              ORDER BY s.id_store'
        );

        $out = [];
        foreach ($lignes ?: [] as $ligne) {
            $horaires = $this->horairesGroupes((string) $ligne['hours']);

            $out[] = [
                'id' => (int) $ligne['id_store'],
                'name' => $ligne['name'],
                'address' => trim($ligne['address1'] . "\n" . $ligne['address2']),
                'city' => trim($ligne['postcode'] . ' ' . $ligne['city']),
                'phone' => $ligne['phone'],
                'email' => $ligne['email'],
                'note' => $ligne['note'],
                'hours' => $horaires,
                /* la photo pleine, pas la vignette 287 x 160 du format
                   « stores_default » : la carte l'affiche en portrait, et la
                   vignette y était étirée donc floue */
                'image' => $this->context->link->getStoreImageLink(
                    Tools::str2url($ligne['name']),
                    (int) $ligne['id_store']
                ),
                'itineraire' => 'https://www.google.com/maps/dir/?api=1&destination='
                    . rawurlencode($ligne['address1'] . ', ' . $ligne['postcode'] . ' ' . $ligne['city']),
            ];
        }

        return $out;
    }

    public function hookDisplayFooterBefore(): string
    {
        $this->context->smarty->assign([
            'ludik_boutiques' => $this->boutiques(),
            'ludik_soiree_link' => $this->cmsLink('soirees-jeux'),
            'ludik_stores_link' => $this->context->link->getPageLink('stores'),
            'ludik_next_tuesday' => $this->nextTuesday(),
            /* la soirée et les boutiques n'apparaissent que sur la page d'accueil,
               le bandeau de réassurance sur toutes les pages */
            'ludik_home' => $this->context->controller->php_self === 'index',
        ]);

        return $this->fetch('module:ludikhome/views/templates/hook/footer_before.tpl');
    }

    private function cmsLink(string $rewrite): string
    {
        $idLang = (int) $this->context->language->id;
        $id = (int) Db::getInstance()->getValue(
            'SELECT id_cms FROM ' . _DB_PREFIX_ . 'cms_lang
             WHERE link_rewrite = "' . pSQL($rewrite) . '" AND id_lang = ' . $idLang
        );

        return $id ? $this->context->link->getCMSLink($id, $rewrite) : '#';
    }

    private function nextTuesday(): string
    {
        $tz = new DateTimeZone('Pacific/Noumea');
        $d = new DateTime('now', $tz);
        $d->modify($d->format('N') == 2 ? 'today' : 'next tuesday');
        $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet',
                 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return 'mardi ' . $d->format('j') . ' ' . $mois[(int) $d->format('n')];
    }
}
