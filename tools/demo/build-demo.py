#!/usr/bin/env python3
"""
Fabrique une démo statique de la boutique, déployable sur un hébergeur de
fichiers (Vercel, Netlify, Pages).

La boutique locale est aspirée page par page : l'accueil, les six rayons, les
produits que ces rayons affichent, les pages éditoriales et les magasins. Les
visuels, la feuille de style et les scripts suivent. Le résultat est un dossier
`dist/` de fichiers statiques, avec un `vercel.json` qui sert `/4-jeux-de-societe`
depuis `4-jeux-de-societe.html`.

Ce qui reste vivant dans la démo, grâce à `demo.js` : la navigation, le tiroir
des rayons, le diaporama, les rangées défilantes, les suggestions de recherche,
la page de résultats et l'ajout au panier avec son compteur et son message.

Ce qui ne peut pas l'être : les filtres à facettes, le tunnel de commande, le
compte client et le formulaire de contact. Ils demandent PHP et une base.

Usage :
    python3 tools/demo/build-demo.py                 # boutique sur localhost:8801
    python3 tools/demo/build-demo.py --produits 60   # moins de fiches
"""

import argparse
import http.cookiejar
import json
import os
import re
import shutil
import sys
import urllib.error
import urllib.parse
import urllib.request

import lxml.html


def par_classe(nom):
    """Prédicat XPath « a cette classe ». lxml sait lire du CSS, mais seulement
    avec le module cssselect ; XPath suffit et ne demande rien à installer."""
    return '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]' % nom

RACINE = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
SORTIE = os.path.join(RACINE, 'dist')

# Les six rayons, en secours seulement : les adresses réelles se lisent sur les
# cartes de l'accueil. Deux d'entre elles ne portent pas le nom qu'on devine
# (« /5-cartes-a-collectionner », pas « /5-jeux-de-cartes-a-collectionner »),
# et une adresse devinée renvoie la bonne page mais crée un fichier que les
# liens du site ne visent pas.
RAYONS = [
    '/4-jeux-de-societe',
    '/5-jeux-de-cartes-a-collectionner',
    '/6-librairie',
    '/3-jeux-de-role',
    '/7-accessoires-jeux-jcc',
    '/8-loisirs-creatifs',
]

# Pages sans produits, reprises telles quelles.
PAGES = [
    '/magasins',
    '/plan-site',
    '/nous-contacter',
    '/nouveaux-produits',
    '/meilleures-ventes',
    '/recherche',
    '/connexion',
    '/inscription',
]

# Le panier s'aspire garni : la page vide ne contient pas le balisage d'une
# ligne, et demo.js n'aurait rien à recopier pour afficher le panier du
# visiteur. Deux fiches de la démo suffisent, leurs visuels sont déjà là.
PANIER_GARNI = 2

# Licences que l'on cherche en premier dans une démo. La boutique locale les
# trouve elle-même : on relit ses pages de résultats pour savoir quelles fiches
# ajouter, sans quoi une recherche sur « pokemon » ne renverrait rien.
RECHERCHES = ['pokemon', 'naruto', 'one piece', 'magic', 'yu-gi-oh', 'lego',
              'dixit', 'catan', 'asterix', 'cthulhu', 'warhammer', 'origami']
PAR_RECHERCHE = 10

EXTENSIONS_BINAIRES = ('.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.ico',
                       '.woff', '.woff2', '.ttf', '.eot')


class Aspirateur:
    def __init__(self, base, limite_produits):
        self.base = base.rstrip('/')
        self.limite_produits = limite_produits
        self.vus = set()
        self.rayons = list(RAYONS)
        self.pages_html = {}      # chemin d'URL -> HTML, pages enregistrées
        self.pages_source = {}    # pages lues pour l'index seulement
        self.fichiers = 0
        self.octets = 0

    # ------------------------------------------------------------------ réseau

    def recuperer(self, chemin, accepter_404=False):
        """Retourne (contenu, type MIME) ou (None, None)."""
        url = self.base + chemin
        requete = urllib.request.Request(url, headers={
            'User-Agent': 'ludik-demo-builder',
            'Accept-Language': 'fr-FR,fr;q=0.9',
        })
        try:
            with urllib.request.urlopen(requete, timeout=30) as reponse:
                return reponse.read(), reponse.headers.get_content_type()
        except urllib.error.HTTPError as erreur:
            if erreur.code == 404 and accepter_404:
                return erreur.read(), erreur.headers.get_content_type()
            print('  %s : HTTP %s' % (chemin, erreur.code))
        except Exception as erreur:  # noqa: BLE001
            print('  %s : %s' % (chemin, erreur))
        return None, None

    # ------------------------------------------------------------------ écriture

    def chemin_fichier(self, chemin_url):
        """Traduit un chemin d'URL en chemin de fichier dans dist/."""
        chemin = urllib.parse.urlparse(chemin_url).path
        if chemin in ('', '/'):
            return os.path.join(SORTIE, 'index.html')
        chemin = chemin.lstrip('/')
        base = os.path.basename(chemin)
        if '.' not in base:
            chemin += '.html'
        return os.path.join(SORTIE, chemin)

    def ecrire(self, chemin_url, contenu):
        destination = self.chemin_fichier(chemin_url)
        os.makedirs(os.path.dirname(destination), exist_ok=True)
        with open(destination, 'wb') as fichier:
            fichier.write(contenu)
        self.fichiers += 1
        self.octets += len(contenu)

    # ------------------------------------------------------------------ analyse

    def liens_produits(self, html):
        """Les fiches produit citées par une page de rayon."""
        arbre = lxml.html.fromstring(html)
        trouves = []
        for lien in arbre.xpath(par_classe('product-miniature') + '//a[@href]'):
            href = lien.get('href', '')
            if '/localhost' not in href and not href.startswith(self.base):
                continue
            chemin = urllib.parse.urlparse(href).path
            if chemin.endswith('.html') and chemin not in trouves:
                trouves.append(chemin)
        return trouves

    def ressources(self, html):
        """Feuilles, scripts et visuels cités par une page."""
        arbre = lxml.html.fromstring(html)
        sortie = []

        def ajouter(valeur):
            if not valeur:
                return
            for morceau in valeur.split(','):
                url = morceau.strip().split(' ')[0]
                if not url or url.startswith(('data:', 'mailto:', 'tel:', '#')):
                    continue
                if url.endswith('/demo.js'):
                    continue  # le script de la démo, pas un fichier de la boutique
                # « 1440w » et compagnie : un descripteur de srcset n'est pas une URL
                if '.' not in url.rsplit('/', 1)[-1]:
                    continue
                absolu = urllib.parse.urljoin(self.base + '/', url)
                if absolu.startswith(self.base):
                    sortie.append(urllib.parse.urlparse(absolu).path)

        for noeud in arbre.xpath('//link[@rel="stylesheet"]'):
            ajouter(noeud.get('href'))
        for noeud in arbre.xpath('//script[@src]'):
            ajouter(noeud.get('src'))
        for noeud in arbre.xpath('//img'):
            ajouter(noeud.get('src'))
            ajouter(noeud.get('srcset'))
        for noeud in arbre.xpath('//source'):
            ajouter(noeud.get('srcset'))
            ajouter(noeud.get('src'))
        for noeud in arbre.xpath('//link[@rel="icon" or @rel="shortcut icon" or @rel="apple-touch-icon"]'):
            ajouter(noeud.get('href'))
        return sortie

    def ressources_css(self, chemin_css, texte):
        """Les url() d'une feuille de style, résolues sur son propre chemin."""
        sortie = []
        for brut in re.findall(r'url\(\s*["\']?([^"\')]+)["\']?\s*\)', texte):
            if brut.startswith(('data:', 'http://', 'https://', '//')):
                continue
            absolu = urllib.parse.urljoin(self.base + chemin_css, brut)
            if absolu.startswith(self.base):
                sortie.append(urllib.parse.urlparse(absolu).path)
        return sortie

    # ------------------------------------------------------------------ reprise

    def nettoyer_html(self, html):
        """Rend les liens relatifs à la racine et coupe ce qui ne peut pas vivre."""
        texte = html.decode('utf-8', 'replace')

        # Les URL absolues de la boutique locale deviennent relatives à la
        # racine. Quatre écritures cohabitent dans une page PrestaShop, et
        # l'oubli d'une seule renvoyait le visiteur sur localhost :
        #  - la forme normale, dans les attributs href et src ;
        #  - la forme sans protocole, dans les gabarits du thème parent ;
        #  - la forme échappée, dans le JSON de configuration inséré en ligne,
        #    d'où l'adresse du panier que lit le script d'ajout ;
        #  - la forme encodée, dans les paramètres « back » des liens de
        #    connexion.
        hote = self.base.split('//', 1)[1]
        for ecriture in (
            self.base,
            '//' + hote,
            self.base.replace('/', '\\/'),
            '\\/\\/' + hote,
            urllib.parse.quote(self.base, safe=''),
            urllib.parse.quote('//' + hote, safe=''),
        ):
            texte = texte.replace(ecriture, '')

        # La rangée « dans le même rayon » d'une fiche produit cite dix
        # produits tirés dans tout le rayon : 35 Ko et dix vignettes par page,
        # pour des fiches qui ne sont pas toutes dans la démo. On garde la
        # section, son titre et sa rangée, et on vide ses cartes : demo.js la
        # remplit avec des produits de la démo, dont les liens mènent quelque
        # part. La section sert aussi de gabarit aux articles consultés, que
        # PrestaShop ne rend qu'avec une session de navigation.
        def vider_rangee(trouve):
            # le gabarit ouvre la balise sur plusieurs lignes : « <article »
            # puis « class="product-miniature ... » à la ligne suivante
            return re.sub(r'<article\s+class="product-miniature.*?</article>', '',
                          trouve.group(0), flags=re.DOTALL)

        texte = re.sub(
            r'<section class="ps-categoryproducts".*?</section>', vider_rangee, texte,
            flags=re.DOTALL
        )

        # le script de la démo, juste avant la fermeture du corps
        texte = texte.replace('</body>', '  <script src="/demo.js"></script>\n</body>')
        return texte.encode('utf-8')

    # ------------------------------------------------------------------ marche

    def aspirer_page(self, chemin):
        if chemin in self.vus:
            return None
        self.vus.add(chemin)
        contenu, mime = self.recuperer(chemin)
        if contenu is None:
            return None
        if mime != 'text/html':
            self.ecrire(chemin, contenu)
            return None
        self.pages_html[chemin] = contenu
        return contenu

    def aspirer_ressource(self, chemin):
        if chemin in self.vus:
            return
        self.vus.add(chemin)
        contenu, mime = self.recuperer(chemin)
        if contenu is None:
            return
        self.ecrire(chemin, contenu)
        if chemin.endswith('.css') or mime == 'text/css':
            texte = contenu.decode('utf-8', 'replace')
            for suivant in self.ressources_css(chemin, texte):
                self.aspirer_ressource(suivant)

    def construire(self):
        if os.path.isdir(SORTIE):
            shutil.rmtree(SORTIE)
        os.makedirs(SORTIE)

        print('Accueil')
        accueil = self.aspirer_page('/')
        self.rayons = list(RAYONS)
        if accueil:
            arbre = lxml.html.fromstring(accueil)
            trouves = []
            for lien in arbre.xpath(par_classe('ludik-univers') + '//a[@href]'):
                chemin = urllib.parse.urlparse(
                    (lien.get('href') or '').replace(self.base, '')).path
                if chemin and chemin not in trouves:
                    trouves.append(chemin)
            if len(trouves) >= 4:
                self.rayons = trouves
        print('  %s rayons : %s' % (len(self.rayons), ', '.join(self.rayons)))

        print('Pages de rayon')
        produits = []
        for rayon in self.rayons:
            html = self.aspirer_page(rayon)
            if html:
                trouves = self.liens_produits(html)
                print('  %-40s %s fiches citées' % (rayon, len(trouves)))
                produits.extend(trouves)


        print('Fiches par licence')
        for terme in RECHERCHES:
            chemin = '/recherche?controller=search&s=' + urllib.parse.quote(terme)
            contenu, mime = self.recuperer(chemin)
            if contenu is None or mime != 'text/html':
                continue
            self.pages_source[terme] = contenu
            trouves = self.liens_produits(contenu)[:PAR_RECHERCHE]
            print('  %-12s %s fiches' % (terme, len(trouves)))
            produits.extend(trouves)

        # on garde l'ordre, sans doublon : les rayons d'abord, puis les licences
        vus_produits = []
        for chemin in produits:
            if chemin not in vus_produits:
                vus_produits.append(chemin)
        produits = vus_produits

        print('Pages sans produits')
        for page in PAGES:
            self.aspirer_page(page)

        self.aspirer_panier(produits[:PANIER_GARNI])

        # Les pages éditoriales : plutôt que de les lister ici, on relève les
        # liens /content/ de l'accueil et du pied de page. Si le client en
        # ajoute une, elle suivra sans toucher au script.
        editoriales = []
        for html in list(self.pages_html.values()):
            arbre = lxml.html.fromstring(html)
            for lien in arbre.xpath('//a[@href]/@href'):
                chemin = urllib.parse.urlparse(lien.replace(self.base, '')).path
                if chemin.startswith('/content/') and chemin not in editoriales:
                    editoriales.append(chemin)
        print('Pages éditoriales : %s' % len(editoriales))
        for chemin in editoriales:
            self.aspirer_page(chemin)

        # Toute page de la démo qui cite une fiche doit y mener : les rangées
        # de l'accueil, « Nouveautés » et « Meilleures ventes » en citent, et
        # leurs liens tombaient sur la page de repli faute d'avoir été
        # aspirés. On repasse donc sur tout ce qui est déjà en mémoire.
        cites = []
        for html in self.pages_html.values():
            for chemin in self.liens_produits(html):
                if chemin not in produits and chemin not in cites:
                    cites.append(chemin)
        if cites:
            print('  %s fiches citées par les pages déjà aspirées' % len(cites))
            produits.extend(cites)

        produits = produits[:self.limite_produits]
        print('Fiches produit : %s' % len(produits))
        for index, chemin in enumerate(produits, 1):
            if index % 20 == 0:
                print('  %s / %s' % (index, len(produits)))
            self.aspirer_page(chemin)

        print('Rayons cités hors démo')
        self.rayons_cites = {}
        motif = re.compile(r'^/\d+-[a-z0-9-]+$')

        def relever(arbre, parent, racine='//a[@href]'):
            for lien in arbre.xpath(racine):
                chemin = urllib.parse.urlparse(
                    (lien.get('href') or '').replace(self.base, '')).path
                if not motif.match(chemin) or chemin in self.pages_html:
                    continue
                libelle = lien.text_content().strip()
                if not libelle:
                    continue
                fiche = self.rayons_cites.setdefault(chemin, {'nom': libelle})
                if parent:
                    fiche['parent'] = parent

        # La colonne de gauche d'une page de rayon ne liste que ses propres
        # sous-rayons : c'est ce qui permet de savoir de quel rayon dépend
        # « Stratégie & Réflexion », et donc de garnir sa page avec des jeux de
        # société plutôt qu'avec des pots de peinture.
        for chemin_rayon in self.rayons:
            html = self.pages_html.get(chemin_rayon)
            if not html:
                continue
            arbre = lxml.html.fromstring(html)
            titres = arbre.xpath('//h1')
            nom_rayon = titres[0].text_content().strip() if titres else ''
            colonne = arbre.xpath('//*[@id="left-column"]')
            if colonne and nom_rayon:
                relever(colonne[0], nom_rayon, './/a[@href]')

        # le reste du catalogue, cité par le tiroir des rayons et le plan du site
        for html in self.pages_html.values():
            relever(lxml.html.fromstring(html), None)

        avec_parent = sum(1 for f in self.rayons_cites.values() if 'parent' in f)
        print('  %s rayons, dont %s rattachés à un rayon de la démo'
              % (len(self.rayons_cites), avec_parent))

        print('Nettoyage des pages')
        for chemin in list(self.pages_html):
            self.pages_html[chemin] = self.nettoyer_html(self.pages_html[chemin])

        print('Ressources')
        ressources = []
        for html in self.pages_html.values():
            ressources.extend(self.ressources(html))
        uniques = []
        for chemin in ressources:
            if chemin not in uniques:
                uniques.append(chemin)
        print('  %s fichiers cités' % len(uniques))
        for index, chemin in enumerate(uniques, 1):
            if index % 100 == 0:
                print('  %s / %s' % (index, len(uniques)))
            self.aspirer_ressource(chemin)

        print('Écriture des pages')
        for chemin, html in self.pages_html.items():
            self.ecrire(chemin, html)

        return produits

    # ------------------------------------------------------------------ panier

    def aspirer_panier(self, fiches):
        """Aspire la page panier avec des lignes dedans.

        PrestaShop ne rend le balisage d'une ligne que si le panier en contient
        une. On ouvre donc une session à cookies, on y ajoute deux articles,
        puis on enregistre la page : demo.js recopiera cette ligne autant de
        fois qu'il y a d'articles dans le panier du visiteur, et effacera les
        lignes du modèle.
        """
        print('Panier garni')
        jar = http.cookiejar.CookieJar()
        session = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
        session.addheaders = [('User-Agent', 'ludik-demo-builder')]

        try:
            accueil = session.open(self.base + '/', timeout=30).read().decode('utf-8', 'replace')
        except Exception as erreur:  # noqa: BLE001
            print('  session impossible : %s' % erreur)
            return

        jeton = re.search(r'"static_token":"([^"]+)"', accueil)
        if not jeton:
            print('  jeton introuvable, panier laissé vide')
            return
        jeton = jeton.group(1)

        ajoutes = 0
        for chemin in fiches:
            identifiant = re.match(r'^/(\d+)-', chemin)
            if not identifiant:
                continue
            adresse = (
                '%s/panier?add=1&id_product=%s&id_product_attribute=0&qty=1&token=%s'
                % (self.base, identifiant.group(1), jeton)
            )
            try:
                session.open(adresse, timeout=30).read()
                ajoutes += 1
            except Exception as erreur:  # noqa: BLE001
                print('  ajout %s : %s' % (identifiant.group(1), erreur))

        try:
            page = session.open(self.base + '/panier?action=show', timeout=30).read()
        except Exception as erreur:  # noqa: BLE001
            print('  page panier : %s' % erreur)
            return

        lignes = page.decode('utf-8', 'replace').count('js-cart-item')
        print('  %s article(s) ajouté(s), %s ligne(s) dans la page' % (ajoutes, lignes))
        if lignes:
            self.vus.add('/panier')
            self.pages_html['/panier'] = page

    # ------------------------------------------------------------------ index

    def index_produits(self, produits):
        """Le catalogue de la démo, relevé sur les vignettes des pages de rayon.

        Les vignettes portent déjà le nom, le prix mis en forme en francs et le
        visuel dans la bonne déclinaison. Les relire évite de reformater les
        prix à la main et garantit que la page de résultats de la démo affiche
        exactement ce qu'affiche une page de rayon.
        """
        gardes = set(produits)
        articles = []
        vus = set()

        self.comptes = {}
        sources = [(self.pages_html.get(chemin), None) for chemin in self.rayons]
        sources += [(html, '') for html in self.pages_source.values()]

        for html, etiquette in sources:
            if not html:
                continue
            arbre = lxml.html.fromstring(html)
            if etiquette is None:
                titres = arbre.xpath('//h1')
                rayon = titres[0].text_content().strip() if titres else ''
            else:
                rayon = etiquette
            avant = len(articles)

            for vignette in arbre.xpath(par_classe('product-miniature')):
                liens = vignette.xpath('.//a[@href]/@href')
                url = ''
                for lien in liens:
                    chemin = urllib.parse.urlparse(lien.replace(self.base, '')).path
                    if chemin.endswith('.html'):
                        url = chemin
                        break
                if not url or url in vus or url not in gardes:
                    continue
                vus.add(url)

                def premier(chemin_xpath, defaut=''):
                    trouves = vignette.xpath(chemin_xpath)
                    if not trouves:
                        return defaut
                    valeur = trouves[0]
                    if isinstance(valeur, str):
                        return valeur.strip()
                    return valeur.text_content().strip()

                visuel = premier('.//img/@src')
                articles.append({
                    'nom': premier('.' + par_classe('product-miniature__title')),
                    'url': url,
                    'prix': premier('.' + par_classe('product-miniature__price')),
                    'visuel': urllib.parse.urlparse(visuel.replace(self.base, '')).path,
                    'rayon': rayon,
                })

            # Les compteurs de la démo : une carte de rayon qui annonce 3 536
            # produits et n'en montre que vingt-quatre se contredit toute
            # seule. demo.js les remplace par ce que la démo contient.
            if etiquette is None:
                for chemin_rayon in self.rayons:
                    if self.pages_html.get(chemin_rayon) is html:
                        self.comptes[chemin_rayon] = len(articles) - avant

        # Les fiches que ni un rayon ni une licence ne citait, celles des
        # rangées de l'accueil par exemple, se lisent sur leur propre page.
        # L'index couvre ainsi toutes les fiches de la démo : la recherche les
        # trouve, et le panier y repêche une couverture manquante.
        connus = {article['url'] for article in articles}
        for chemin in produits:
            if chemin in connus:
                continue
            html = self.pages_html.get(chemin)
            if not html:
                continue
            arbre = lxml.html.fromstring(html)

            titres = arbre.xpath('//h1')
            if not titres:
                continue

            prix = ''
            blocs = arbre.xpath(par_classe('product__price'))
            if blocs:
                trouve = re.search(r'(\d[\d\s\u00a0]*)\s*F', blocs[0].text_content())
                if trouve:
                    prix = trouve.group(1).strip() + '\u00a0F'

            visuel = ''
            for noeud in arbre.xpath('//img[@src]'):
                source = noeud.get('src') or ''
                if 'product_main' in source or 'default_xl' in source or '-large_default/' in source:
                    visuel = urllib.parse.urlparse(source.replace(self.base, '')).path
                    break

            articles.append({
                'nom': titres[0].text_content().strip(),
                'url': chemin,
                'prix': prix,
                'visuel': visuel,
                'rayon': '',
            })

        return articles


def _modele_rangee(self):
    """Une rangée de produits vidée, prélevée sur une fiche.

    Le module « Vous aimerez aussi » ne s'affiche pas sur toutes les fiches :
    un produit seul dans son sous-rayon n'a personne à côté de lui. Les
    articles consultés, eux, doivent pouvoir apparaître partout. On garde donc
    une rangée en réserve, que demo.js insère là où il n'y en a pas à cloner.
    """
    for chemin, html in self.pages_html.items():
        if not chemin.endswith('.html'):
            continue
        texte = html.decode('utf-8', 'replace') if isinstance(html, bytes) else html
        debut = texte.find('<section class="ps-categoryproducts"')
        if debut == -1:
            continue
        fin = texte.find('</section>', debut)
        if fin == -1:
            continue
        return texte[debut:fin + len('</section>')]
    return ''


Aspirateur.modele_rangee = _modele_rangee


def _modele_carte(self):
    """Une vignette produit prélevée sur une page de rayon.

    La page de résultats de la démo est rendue par le navigateur : plutôt que
    de réécrire à la main le balisage de la vignette, au risque qu'il s'écarte
    de celui du thème, on en prélève une et on remplace son contenu.
    """
    for chemin in self.rayons:
        html = self.pages_html.get(chemin)
        if not html:
            continue
        arbre = lxml.html.fromstring(html)
        vignettes = arbre.xpath(par_classe('product-miniature'))
        if vignettes:
            brut = lxml.html.tostring(vignettes[0], encoding='unicode')
            return brut.replace(self.base, '')
    return ''


Aspirateur.modele_carte = _modele_carte


def main():
    analyse = argparse.ArgumentParser(description=__doc__)
    analyse.add_argument('--base', default='http://localhost:8801')
    analyse.add_argument('--produits', type=int, default=320)
    arguments = analyse.parse_args()

    aspirateur = Aspirateur(arguments.base, arguments.produits)
    produits = aspirateur.construire()

    index = aspirateur.index_produits(produits)
    donnees = {
        'articles': index,
        'modele': aspirateur.modele_carte(),
        'rangee': aspirateur.modele_rangee(),
        'rayons': aspirateur.rayons_cites,
        'comptes': aspirateur.comptes,
    }
    with open(os.path.join(SORTIE, 'demo-index.json'), 'w', encoding='utf-8') as fichier:
        json.dump(donnees, fichier, ensure_ascii=False, separators=(',', ':'))
    print('Index de recherche : %s articles' % len(index))

    modele = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'demo.js')
    shutil.copyfile(modele, os.path.join(SORTIE, 'demo.js'))

    # le navigateur demande /favicon.ico de lui-même, sans que la page le cite
    favicon = os.path.join(RACINE, 'import', 'brand', 'favicon.ico')
    if os.path.exists(favicon):
        shutil.copyfile(favicon, os.path.join(SORTIE, 'favicon.ico'))

    # réponse vide pour les appels du module favoris, détournés par demo.js
    with open(os.path.join(SORTIE, 'demo-vide.json'), 'w', encoding='utf-8') as fichier:
        json.dump({'data': {'lists': []}}, fichier)

    # Page de repli : le catalogue compte 3 965 rayons, la démo six. Les autres
    # adresses tombent ici, et demo.js y affiche le nom du rayon demandé avec
    # les produits de la démo. La page de recherche sert de gabarit : pleine
    # largeur, un titre, une grille de produits, pas de colonne de filtres qui
    # parlerait d'un autre rayon.
    gabarit = os.path.join(SORTIE, 'recherche.html')
    if os.path.exists(gabarit):
        with open(gabarit, encoding='utf-8') as fichier:
            texte = fichier.read()
        texte = texte.replace('<head>', '<head>\n  <meta name="demo-rayon" content="1">', 1)
        with open(os.path.join(SORTIE, 'rayon.html'), 'w', encoding='utf-8') as fichier:
            fichier.write(texte)
        print('rayon.html écrite')

    with open(os.path.join(SORTIE, 'vercel.json'), 'w', encoding='utf-8') as fichier:
        json.dump({
            'cleanUrls': True,
            'trailingSlash': False,
            # Les fichiers statiques passent avant les réécritures : seules les
            # adresses sans page atterrissent sur le gabarit de rayon. La
            # destination s'écrit sans extension : avec cleanUrls, Vercel
            # expose « /rayon » et « /rayon.html » n'est plus qu'une
            # redirection, donc une destination qui ne mène nulle part.
            'rewrites': [{'source': '/(.*)', 'destination': '/rayon'}],
        }, fichier, indent=2)

    total = 0
    for dossier, _, fichiers in os.walk(SORTIE):
        for nom in fichiers:
            total += os.path.getsize(os.path.join(dossier, nom))
    print('\ndist/ : %s fichiers, %.1f Mo' % (aspirateur.fichiers + 3, total / 1024 / 1024))
    print('Déploiement : vercel deploy --prod dist')


if __name__ == '__main__':
    sys.exit(main())
