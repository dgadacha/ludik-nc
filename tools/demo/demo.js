/**
 * Ce qui reste vivant dans la démo statique.
 *
 * La boutique aspirée n'a plus de PHP derrière elle : la recherche, l'ajout au
 * panier et les filtres passaient tous par le serveur. Ce fichier rebranche
 * côté navigateur ce qui se démontre en cliquant, à partir du catalogue de la
 * démo (demo-index.json) :
 *
 *   - les suggestions sous la barre de recherche ;
 *   - la page de résultats ;
 *   - l'ajout au panier, avec le compteur et le message de confirmation ;
 *   - la page panier.
 *
 * Ce qui demande une base de données répond par un message clair plutôt que
 * par rien du tout : filtres à facettes, tri, pagination, tunnel de commande.
 */
(function () {
  'use strict';

  var CLE_PANIER = 'ludik-demo-panier';
  var catalogue = null;
  var modeleCarte = '';
  var rayons = {};

  /* ------------------------------------------------------------ catalogue */

  function chargerCatalogue() {
    if (catalogue) {
      return Promise.resolve(catalogue);
    }
    return fetch('/demo-index.json', { headers: { Accept: 'application/json' } })
      .then(function (reponse) { return reponse.json(); })
      .then(function (donnees) {
        catalogue = donnees.articles || [];
        modeleCarte = donnees.modele || '';
        rayons = donnees.rayons || {};
        return catalogue;
      })
      .catch(function () { catalogue = []; return catalogue; });
  }

  /* « pokemon » doit trouver « Pokémon » : la recherche de la boutique ignore
     les accents, celle de la démo aussi. */
  function aplatir(texte) {
    return (texte || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  }

  function chercher(mot) {
    var terme = aplatir(mot).trim();
    if (!terme) {
      return [];
    }
    var mots = terme.split(/\s+/);
    return (catalogue || []).filter(function (article) {
      var cible = aplatir(article.nom + ' ' + (article.rayon || ''));
      return mots.every(function (m) { return cible.indexOf(m) !== -1; });
    });
  }

  /* ------------------------------------------------------------- panier */

  function lirePanier() {
    try {
      return JSON.parse(window.localStorage.getItem(CLE_PANIER)) || [];
    } catch (erreur) {
      return [];
    }
  }

  function ecrirePanier(lignes) {
    try {
      window.localStorage.setItem(CLE_PANIER, JSON.stringify(lignes));
    } catch (erreur) {
      /* navigation privée : le panier ne survit pas au rechargement, tant pis */
    }
  }

  function quantiteTotale() {
    return lirePanier().reduce(function (total, ligne) { return total + ligne.qte; }, 0);
  }

  function majCompteur() {
    var total = quantiteTotale();
    var compteurs = document.querySelectorAll('.cart-products-count');
    Array.prototype.forEach.call(compteurs, function (compteur) {
      compteur.textContent = String(total);
    });
  }

  function ajouterAuPanier(url) {
    var article = (catalogue || []).filter(function (a) { return a.url === url; })[0];
    var lignes = lirePanier();
    var existante = lignes.filter(function (l) { return l.url === url; })[0];

    if (existante) {
      existante.qte += 1;
    } else {
      lignes.push({
        url: url,
        nom: article ? article.nom : 'Article',
        prix: article ? article.prix : '',
        visuel: article ? article.visuel : '',
        qte: 1
      });
    }

    ecrirePanier(lignes);
    majCompteur();

    /* le thème affiche lui-même le message de confirmation et fait pulser le
       compteur : on lui envoie l'événement qu'il attend */
    if (window.prestashop && typeof window.prestashop.emit === 'function') {
      window.prestashop.emit('updateCart', { reason: { linkAction: 'add-to-cart' }, resp: {} });
    }
  }

  /* ------------------------------------------------- message d'indisponible */

  function signaler(texte) {
    if (window.prestashop && typeof window.prestashop.emit === 'function') {
      window.prestashop.emit('handleError', { resp: { errors: [texte] } });
      return;
    }
    window.alert(texte);
  }

  var INDISPONIBLE = 'Cette fonction demande le serveur de la boutique : elle n\'est pas active dans la démo statique.';

  /* ---------------------------------------------------- interception réseau */

  var fetchOrigine = window.fetch ? window.fetch.bind(window) : null;

  function reponseJson(donnees) {
    return Promise.resolve(new Response(JSON.stringify(donnees), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    }));
  }

  if (fetchOrigine) {
    window.fetch = function (entree, options) {
      var url = typeof entree === 'string' ? entree : (entree && entree.url) || '';
      var chemin = url.split('?')[0];

      /* suggestions de recherche : le thème lit reponse.products */
      if (chemin.indexOf('/recherche') === 0 || chemin.indexOf('recherche') !== -1) {
        var mot = '';
        if (options && options.body && typeof options.body.get === 'function') {
          mot = options.body.get('s') || '';
        }
        if (mot) {
          return chargerCatalogue().then(function () {
            return reponseJson({
              products: chercher(mot).slice(0, 10).map(function (article, index) {
                return {
                  id_product: index + 1,
                  name: article.nom,
                  canonical_url: article.url,
                  cover: article.visuel
                    ? { small: { url: article.visuel }, legend: article.nom }
                    : null
                };
              })
            });
          });
        }
      }

      /* le module favoris interroge son API en GraphQL : sans serveur il
         reçoit la page 404 et se plaint dans la console à chaque page */
      if (url.indexOf('/module/blockwishlist') !== -1) {
        return reponseJson({ data: { lists: [] } });
      }

      /* facettes, tri, pagination */
      if (url.indexOf('from-xhr') !== -1 || url.indexOf('ajax=1') !== -1 || url.indexOf('q=') !== -1) {
        signaler(INDISPONIBLE);
        return reponseJson({});
      }

      return fetchOrigine(entree, options);
    };
  }

  /* Le module favoris passe par XMLHttpRequest pour son API GraphQL : sans
     serveur il reçoit la page 404 et remplit la console d'erreurs de parsing.
     On lui répond une liste vide. */
  var ouvrirOrigine = window.XMLHttpRequest && window.XMLHttpRequest.prototype.open;
  if (ouvrirOrigine) {
    window.XMLHttpRequest.prototype.open = function (methode, url) {
      if (typeof url === 'string' && url.indexOf('/module/blockwishlist') !== -1) {
        arguments[1] = '/demo-vide.json';
      }
      return ouvrirOrigine.apply(this, arguments);
    };
  }

  /* -------------------------------------------------- page de résultats */

  function carte(article) {
    if (!modeleCarte) {
      return '<a class="lud-card lud-card--pad" href="' + article.url + '">' +
        '<strong>' + article.nom + '</strong><span>' + article.prix + '</span></a>';
    }
    var bac = document.createElement('div');
    bac.innerHTML = modeleCarte;
    var element = bac.firstElementChild;

    Array.prototype.forEach.call(element.querySelectorAll('a[href]'), function (lien) {
      lien.setAttribute('href', article.url);
    });
    var image = element.querySelector('img');
    if (image) {
      image.setAttribute('src', article.visuel || '');
      image.setAttribute('alt', article.nom);
      image.removeAttribute('srcset');
    }
    var titre = element.querySelector('.product-miniature__title');
    if (titre) {
      titre.textContent = article.nom;
    }
    var prix = element.querySelector('.product-miniature__price');
    if (prix) {
      prix.textContent = article.prix;
    }
    /* le coeur demande un compte client : il n'a rien à faire ici */
    Array.prototype.forEach.call(
      element.querySelectorAll('.product-miniature__favori'),
      function (noeud) { noeud.remove(); }
    );
    return element.outerHTML;
  }

  function remplirGrille(articles, titre, sousTitre) {
    /* la page de repli et la page de résultats partagent le même gabarit :
       un en-tête, puis une grille que le serveur laisse vide */
    var liste = document.querySelector('#js-product-list');
    var grille = liste ? liste.querySelector('.products') : null;
    if (liste && !grille) {
      liste.innerHTML = '<div class="products"></div>';
      grille = liste.querySelector('.products');
    }
    if (!grille) {
      return;
    }
    grille.innerHTML = articles.map(carte).join('');

    var h1 = document.querySelector('.category__header h1, h1');
    if (h1 && titre) {
      h1.textContent = titre;
    }
    var compte = document.querySelector('.category__count');
    if (compte && sousTitre) {
      compte.textContent = sousTitre;
    }
  }

  /* ------------------------------------------------------- page de repli */

  function rendreRayon() {
    var chemin = window.location.pathname.replace(/\.html$/, '');

    chargerCatalogue().then(function () {
      var fiche = rayons[chemin] || {};
      var libelle = fiche.nom || '';
      if (!libelle) {
        /* pas de libellé relevé : on reprend le nom de l'adresse,
           « /122-le-webtoon » donne « Le webtoon » */
        libelle = chemin.replace(/^\/\d*-?/, '').replace(/-/g, ' ').trim();
        libelle = libelle ? libelle.charAt(0).toUpperCase() + libelle.slice(1) : 'Rayon';
      }

      /* Un sous-rayon de la démo est rattaché à son rayon : on n'y montre que
         des produits de ce rayon, sinon « Stratégie & Réflexion » se garnissait
         de pots de peinture. Ailleurs, tout le catalogue de la démo. */
      var fonds = catalogue;
      if (fiche.parent) {
        var memes = catalogue.filter(function (a) { return a.rayon === fiche.parent; });
        if (memes.length) {
          fonds = memes;
        }
      }

      /* une sélection stable pour une adresse donnée : deux rayons de la démo
         ne montrent pas la même chose, et un rayon montre toujours la même */
      var graine = 0;
      for (var i = 0; i < chemin.length; i += 1) {
        graine = (graine * 31 + chemin.charCodeAt(i)) % 100000;
      }
      var depart = fonds.length ? graine % fonds.length : 0;
      var choix = fonds.slice(depart).concat(fonds.slice(0, depart)).slice(0, 24);

      document.title = libelle + ' - Ludik.nc';
      remplirGrille(choix, libelle, choix.length + ' produits');

      var fil = document.querySelector('.breadcrumb li:last-child span, .breadcrumb li:last-child');
      if (fil) {
        fil.textContent = libelle;
      }
    });
  }

  function rendreResultats() {
    var parametres = new URLSearchParams(window.location.search);
    var mot = parametres.get('s') || parametres.get('search_query') || '';
    chargerCatalogue().then(function () {
      var resultats = chercher(mot);
      var titre = resultats.length
        ? resultats.length + (resultats.length > 1 ? ' résultats' : ' résultat') + ' pour « ' + mot + ' »'
        : 'Aucun résultat pour « ' + mot + ' »';
      remplirGrille(resultats, titre, 'Indiquez un titre, une licence, un auteur ou une référence.');
    });
  }

  /* -------------------------------------------------------- page panier */

  function rendrePanier() {
    /* la grille du panier vit dans la colonne centrale : c'est elle qu'on
       remplace, sinon le panier de la démo s'ajoute au panier vide du serveur */
    var grille = document.querySelector('.cart-grid');
    var zone = grille ? grille.parentElement : document.querySelector('#center-column');
    if (!zone) {
      return;
    }
    var lignes = lirePanier();
    var corps = lignes.map(function (ligne) {
      return '<li class="demo-panier__ligne">' +
        (ligne.visuel ? '<img src="' + ligne.visuel + '" alt="" width="64" height="64">' : '') +
        '<a href="' + ligne.url + '">' + ligne.nom + '</a>' +
        '<span>' + ligne.qte + ' x ' + ligne.prix + '</span></li>';
    }).join('');

    zone.innerHTML =
      '<div class="lud-card lud-card--pad">' +
      '<h1 class="page-title">Votre panier</h1>' +
      (lignes.length
        ? '<ul class="demo-panier">' + corps + '</ul>'
        : '<p>Votre panier est vide.</p>') +
      '<p class="demo-note">Le tunnel de commande demande le serveur de la boutique. ' +
      'Dans cette démo statique, le panier vit dans votre navigateur.</p>' +
      '</div>' +
      '<style>' +
      '.demo-panier{list-style:none;margin:1.5rem 0 0;padding:0;display:grid;gap:.75rem}' +
      '.demo-panier__ligne{display:flex;align-items:center;gap:1rem;padding:.75rem;' +
      'border-radius:.875rem;background:var(--lud-surface-alt)}' +
      '.demo-panier__ligne span{margin-left:auto;font-weight:700}' +
      '.demo-note{margin:1.5rem 0 0;color:var(--lud-ink-soft);font-size:.875rem}' +
      '</style>';
  }

  /* ------------------------------------------------------------ démarrage */

  function demarrer() {
    majCompteur();
    chargerCatalogue();

    var chemin = window.location.pathname.replace(/\.html$/, '');

    /* seule la page de repli porte cette balise */
    if (document.querySelector('meta[name="demo-rayon"]')) {
      rendreRayon();
    }
    if (chemin === '/recherche') {
      rendreResultats();
    }
    if (chemin === '/panier') {
      rendrePanier();
    }

    /* Ajout au panier : capturé au clic, avant que le thème ne tente sa
       requête. Le formulaire de la vignette porte l'identifiant du produit,
       la fiche produit aussi : on remonte au formulaire pour retrouver la
       fiche visée. */
    document.addEventListener('click', function (evenement) {
      var bouton = evenement.target.closest('[data-button-action="add-to-cart"]');
      if (!bouton) {
        return;
      }
      evenement.preventDefault();
      evenement.stopPropagation();

      var formulaire = bouton.closest('form');
      var vignette = bouton.closest('.product-miniature');
      var lien = vignette ? vignette.querySelector('a[href$=".html"]') : null;
      var cible = lien ? lien.getAttribute('href') : window.location.pathname;

      chargerCatalogue().then(function () {
        ajouterAuPanier(cible.replace(/\.html$/, '.html'));
      });

      if (formulaire) {
        formulaire.setAttribute('onsubmit', 'return false');
      }
    }, true);

    /* les commandes qui ne peuvent pas répondre le disent, plutôt que de ne
       rien faire sous le doigt */
    document.addEventListener('click', function (evenement) {
      var cible = evenement.target.closest(
        '.js-search-link, .js-pager-link, .js-search-filters-clear-all, ' +
        '[data-ps-action="add-voucher"], .checkout .btn-primary'
      );
      if (cible) {
        evenement.preventDefault();
        signaler(INDISPONIBLE);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer);
  } else {
    demarrer();
  }
})();
