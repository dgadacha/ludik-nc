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
  var comptes = {};

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
        comptes = donnees.comptes || {};
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

  /* « 2 983 F » -> 2983 : les prix de la démo sont des libellés, pas des
     nombres. On ne garde que les chiffres pour pouvoir les additionner. */
  function centimes(prix) {
    var chiffres = String(prix || '').replace(/[^0-9]/g, '');
    return chiffres ? parseInt(chiffres, 10) : 0;
  }

  function formater(montant) {
    return String(montant).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0') + '\u00a0F';
  }

  function majCompteur() {
    var lignes = lirePanier();
    var total = lignes.reduce(function (n, l) { return n + l.qte; }, 0);
    var montant = lignes.reduce(function (n, l) { return n + centimes(l.prix) * l.qte; }, 0);

    Array.prototype.forEach.call(document.querySelectorAll('.cart-products-count'), function (compteur) {
      compteur.textContent = String(total);
    });
    /* le montant du panier vit à côté du libellé, dans l'en-tête */
    Array.prototype.forEach.call(document.querySelectorAll('.blockcart .ludik-head__action-text em'), function (noeud) {
      noeud.textContent = formater(montant);
    });
  }

  /**
   * Ce qu'on sait de l'article ajouté.
   *
   * L'index de la démo ne couvre que les fiches des pages de rayon et des
   * licences : un produit ajouté depuis une rangée de l'accueil n'y figure
   * pas, et le panier affichait « Article » sans prix. La page a pourtant
   * tout sous la main, dans la vignette ou dans la fiche : on la lit d'abord,
   * l'index ne sert plus que de secours.
   */
  function decrire(url, origine) {
    var texte = function (selecteurs) {
      for (var i = 0; i < selecteurs.length; i += 1) {
        var noeud = origine ? origine.querySelector(selecteurs[i]) : null;
        if (!noeud) {
          noeud = document.querySelector(selecteurs[i]);
        }
        if (noeud && noeud.textContent.trim()) {
          return noeud.textContent.trim();
        }
      }
      return '';
    };

    var image = (origine && origine.querySelector('img'))
      || document.querySelector('.js-qv-product-cover, .product__images img');
    var connu = (catalogue || []).filter(function (a) { return a.url === url; })[0] || {};

    /* Le prix de la fiche porte un intitulé « Prix : » réservé aux lecteurs
       d'écran, et le panier affichait « 1 x Prix : 958 F ». On ne garde que le
       montant, repéré sur sa forme : des chiffres, des espaces, puis le franc. */
    var brut = texte(['.product-miniature__price', '.product__price']) || connu.prix || '';
    var montant = brut.match(/(\d[\d\s\u00a0]*)\s*F/);

    return {
      url: url,
      nom: texte(['.product-miniature__title', 'h1']) || connu.nom || 'Article',
      prix: montant ? montant[1].trim() + '\u00a0F' : brut.trim(),
      visuel: (image && (image.getAttribute('src') || '')) || connu.visuel || ''
    };
  }

  function ajouterAuPanier(url, origine) {
    var lignes = lirePanier();
    var existante = lignes.filter(function (l) { return l.url === url; })[0];

    if (existante) {
      existante.qte += 1;
    } else {
      var fiche = decrire(url, origine);
      fiche.qte = 1;
      lignes.push(fiche);
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

  /* ---------------------------------------------------------- compteurs */

  /**
   * Aligne les compteurs sur ce que la démo contient réellement.
   *
   * Les pages sont aspirées telles quelles : leurs compteurs sont ceux de la
   * boutique complète. Une carte qui annonce 3 536 produits et mène à
   * vingt-quatre se contredit toute seule.
   *
   * Les nombres de sous-rayons, eux, restent : l'arborescence est réelle et
   * chaque sous-rayon a bien une page dans la démo.
   */
  function ajusterCompteurs() {
    /* cartes de rayon de l'accueil */
    Array.prototype.forEach.call(document.querySelectorAll('.ludik-univers a[href]'), function (carteRayon) {
      var chemin = carteRayon.getAttribute('href').split('?')[0];
      var nombre = comptes[chemin];
      if (nombre === undefined) {
        return;
      }
      var compteur = carteRayon.querySelector('.u-count');
      if (!compteur) {
        return;
      }
      /* le libellé est le premier noeud de texte, la flèche suit en SVG */
      Array.prototype.forEach.call(compteur.childNodes, function (noeud) {
        if (noeud.nodeType === 3 && noeud.textContent.trim()) {
          noeud.textContent = ' ' + nombre + ' produits ';
        }
      });
    });

    /* Les filtres à facettes ne fonctionnent pas sans serveur, et leurs
       nombres portent sur le catalogue complet. On garde les intitulés, qui
       montrent ce que fait la vraie boutique, et on retire les nombres. */
    Array.prototype.forEach.call(document.querySelectorAll('.search-filters__magnitude'), function (noeud) {
      noeud.remove();
    });

    /* Bas de liste : « Affichage 1-24 de 28 113 article(s) » et la pagination
       sur 1 172 pages. La démo n'a qu'une page par rayon. */
    var nombre = comptes[window.location.pathname.replace(/\.html$/, '')];
    var affiches = document.querySelectorAll('.product-miniature').length;
    var visible = nombre === undefined ? affiches : nombre;
    var bas = document.querySelector('.pagination__number');
    if (bas && visible) {
      bas.textContent = 'Affichage 1-' + visible + ' de ' + visible + ' article(s)';
    }
    var pages = document.querySelector('.pagination__nav');
    if (pages) {
      pages.remove();
    }

    /* en-tête d'une page de rayon */
    var chemin = window.location.pathname.replace(/\.html$/, '');
    var ici = comptes[chemin];
    if (ici !== undefined) {
      var entete = document.querySelector('.category__count');
      if (entete) {
        var fort = entete.querySelector('strong');
        if (fort) {
          fort.textContent = String(ici);
        }
      }
    }
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
        (ligne.visuel ? '<img src="' + ligne.visuel + '" alt="" width="56" height="56">' : '') +
        '<a href="' + ligne.url + '">' + ligne.nom + '</a>' +
        '<span class="demo-panier__qte">' + ligne.qte + '</span>' +
        '<span class="demo-panier__prix">' + (ligne.prix || '') + '</span></li>';
    }).join('');

    zone.innerHTML =
      '<div class="lud-card lud-card--pad">' +
      '<h1 class="page-title">Votre panier</h1>' +
      (lignes.length
        ? '<ul class="demo-panier">' + corps + '</ul>' +
          '<p class="demo-total">Total : <strong>' +
          formater(lignes.reduce(function (n, l) { return n + centimes(l.prix) * l.qte; }, 0)) +
          '</strong></p>'
        : '<p>Votre panier est vide.</p>') +
      '<p class="demo-note">Le tunnel de commande demande le serveur de la boutique. ' +
      'Dans cette démo statique, le panier vit dans votre navigateur.</p>' +
      '</div>' +
      '<style>' +
      '.demo-panier{list-style:none;margin:1.5rem 0 0;padding:0;display:grid;gap:.75rem}' +
      '.demo-panier__ligne{display:flex;align-items:center;gap:1rem;padding:.75rem;' +
      'border-radius:.875rem;background:var(--lud-surface-alt)}' +
      '.demo-panier__ligne img{flex:0 0 auto;width:56px;height:56px;object-fit:contain;' +
      'background:var(--lud-surface);border-radius:.5rem}' +
      '.demo-panier__ligne a{flex:1 1 auto;min-width:0}' +
      '.demo-panier__qte{flex:0 0 auto;color:var(--lud-ink-soft)}' +
      '.demo-panier__qte::before{content:"x "}' +
      '.demo-panier__prix{flex:0 0 auto;font-weight:700;min-width:5.5rem;text-align:right}' +
      '.demo-total{margin:1rem 0 0;text-align:right;font-size:1.05rem}' +
      '.demo-note{margin:1.5rem 0 0;color:var(--lud-ink-soft);font-size:.875rem}' +
      '</style>';
  }

  /* ------------------------------------------------------------ démarrage */

  function demarrer() {
    majCompteur();
    chargerCatalogue().then(ajusterCompteurs);

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
        ajouterAuPanier(cible, vignette);
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
