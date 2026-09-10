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
  var modeleRangee = '';
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
        modeleRangee = donnees.rangee || '';
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

  /**
   * Répare une ligne écrite par une version antérieure de la démo.
   *
   * Le panier vit dans le navigateur du visiteur : il survit aux
   * redéploiements et garde les défauts du jour où il a été rempli, comme
   * l'intitulé « Prix : » dans le montant ou l'absence de couverture. On le
   * répare à la lecture, plutôt que de le vider : personne ne perd son panier
   * parce qu'on a corrigé la démo.
   */
  function reparer(ligne) {
    var montant = String(ligne.prix || '').match(/(\d[\d\s\u00a0]*)\s*F/);
    if (montant) {
      ligne.prix = montant[1].trim() + '\u00a0F';
    }
    if (!ligne.visuel) {
      var connu = (catalogue || []).filter(function (a) { return a.url === ligne.url; })[0];
      if (connu && connu.visuel) {
        ligne.visuel = connu.visuel;
      }
    }
    return ligne;
  }

  function lirePanier() {
    try {
      return (JSON.parse(window.localStorage.getItem(CLE_PANIER)) || []).map(reparer);
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

  /**
   * La page panier du thème, garnie avec le panier du visiteur.
   *
   * PrestaShop ne rend le balisage d'une ligne que si le panier en contient
   * une : le script de fabrication aspire donc une page panier garnie de deux
   * articles. On y prend la première ligne comme gabarit, on la recopie autant
   * de fois qu'il y a d'articles, et on met le récapitulatif à jour. Le
   * sélecteur de quantité et le lien « Retirer » sont rebranchés sur le
   * navigateur.
   */
  function majRecap(lignes) {
    var articles = lignes.reduce(function (n, l) { return n + l.qte; }, 0);
    var montant = lignes.reduce(function (n, l) { return n + centimes(l.prix) * l.qte; }, 0);

    var sous = document.querySelector('#cart-subtotal-products');
    if (sous) {
      var intitule = sous.querySelector('.js-subtotal');
      if (intitule) {
        intitule.textContent = articles + (articles > 1 ? ' articles' : ' article');
      }
      var valeur = sous.querySelector('.cart-summary__value');
      if (valeur) {
        valeur.textContent = formater(montant);
      }
    }

    var total = document.querySelector('.cart-summary__total .cart-summary__value');
    if (total) {
      total.textContent = formater(montant);
    }
  }

  var panierBranche = false;

  function brancherPanier(liste) {
    if (panierBranche) {
      return;
    }
    panierBranche = true;

    liste.addEventListener('click', function (evenement) {
      var item = evenement.target.closest('.js-cart-item');
      if (!item || item.dataset.demoIndex === undefined) {
        return;
      }
      var index = parseInt(item.dataset.demoIndex, 10);
      var lignes = lirePanier();
      if (!lignes[index]) {
        return;
      }

      if (evenement.target.closest('.js-remove-from-cart')) {
        lignes.splice(index, 1);
      } else if (evenement.target.closest('.js-increment-button')) {
        lignes[index].qte += 1;
      } else if (evenement.target.closest('.js-decrement-button')) {
        lignes[index].qte = Math.max(1, lignes[index].qte - 1);
      } else {
        return;
      }

      evenement.preventDefault();
      evenement.stopPropagation();
      ecrirePanier(lignes);
      majCompteur();
      rendrePanier();
    }, true);
  }

  function rendrePanier() {
    var liste = document.querySelector('.js-cart-list');
    if (!liste) {
      return;
    }
    var gabarit = liste.querySelector('.js-cart-item');
    if (!gabarit) {
      return;
    }
    if (!liste.dataset.demoGabarit) {
      liste.dataset.demoGabarit = gabarit.outerHTML;
    }

    var lignes = lirePanier();
    /* les réparations de lecture sont réécrites, pour ne les faire qu'une fois */
    ecrirePanier(lignes);

    liste.innerHTML = '';
    lignes.forEach(function (ligne, index) {
      var bac = document.createElement('div');
      bac.innerHTML = liste.dataset.demoGabarit;
      var item = bac.firstElementChild;
      item.dataset.demoIndex = String(index);

      Array.prototype.forEach.call(item.querySelectorAll('a.product-line__title'), function (lien) {
        lien.setAttribute('href', ligne.url);
      });
      var image = item.querySelector('img');
      if (image) {
        image.removeAttribute('srcset');
        image.setAttribute('src', ligne.visuel || '');
        image.setAttribute('alt', ligne.nom);
        image.setAttribute('title', ligne.nom);
      }
      var titre = item.querySelector('.product-line__content-left a.product-line__title');
      if (titre) {
        titre.textContent = ligne.nom;
      }
      var unitaire = item.querySelector('.product-line__item-price');
      if (unitaire) {
        unitaire.textContent = ligne.prix;
      }
      var champ = item.querySelector('.js-cart-line-product-quantity');
      if (champ) {
        champ.value = String(ligne.qte);
        /* sans serveur à interroger, le champ ne doit pas tenter sa requête */
        champ.removeAttribute('data-update-url');
        champ.setAttribute('readonly', 'readonly');
      }
      var ligneTotal = item.querySelector('.product-line__price');
      if (ligneTotal) {
        ligneTotal.textContent = formater(centimes(ligne.prix) * ligne.qte);
      }
      liste.appendChild(item);
    });

    if (!lignes.length) {
      liste.innerHTML = '<p class="demo-vide">Il n\'y a plus d\'articles dans votre panier.</p>';
    }

    majRecap(lignes);
    brancherPanier(liste);

    /* le mot sur la démo, une seule fois, sous la liste */
    if (!document.querySelector('.demo-note')) {
      var note = document.createElement('p');
      note.className = 'demo-note';
      note.textContent = 'Le tunnel de commande demande le serveur de la boutique. '
        + 'Dans cette démo, le panier vit dans votre navigateur.';
      liste.parentNode.appendChild(note);

      var style = document.createElement('style');
      style.textContent = '.demo-note{margin:1.5rem 0 0;color:var(--lud-ink-soft);font-size:.875rem}'
        + '.demo-vide{margin:1.5rem 0;color:var(--lud-ink-soft)}';
      document.head.appendChild(style);
    }
  }

  /* --------------------------------- rangées de la fiche produit */

  var CLE_VUS = 'ludik-demo-vus';

  function lireVus() {
    try {
      return JSON.parse(window.localStorage.getItem(CLE_VUS)) || [];
    } catch (erreur) {
      return [];
    }
  }

  function memoriserVue(url) {
    var vus = lireVus().filter(function (u) { return u !== url; });
    vus.unshift(url);
    try {
      window.localStorage.setItem(CLE_VUS, JSON.stringify(vus.slice(0, 12)));
    } catch (erreur) {
      /* navigation privée : l'historique ne survit pas, tant pis */
    }
  }

  /**
   * Remplit « Vous aimerez aussi » et ajoute « Récemment consultés ».
   *
   * Le script de fabrication garde la section du thème mais vide ses cartes :
   * elles citaient dix produits tirés dans tout le rayon, dont la plupart ne
   * sont pas dans la démo. On la remplit avec des produits du même rayon qui,
   * eux, ont bien une fiche ici. La deuxième rangée n'existe pas dans les
   * pages aspirées, PrestaShop ne la rendant qu'avec une session de
   * navigation : on clone la première et on la garnit de l'historique du
   * visiteur.
   */
  function rendreRangees(chemin) {
    var section = document.querySelector('.ps-categoryproducts');
    var courant = catalogue.filter(function (a) { return a.url === chemin; })[0];

    /* « Vous aimerez aussi » n'existe que si le thème l'a rendu : un produit
       seul dans son sous-rayon n'a personne à côté de lui, ici comme sur la
       boutique. On ne l'invente pas. */
    if (section) {
      var grille = section.querySelector('.products');
      if (grille) {
        var autres = catalogue.filter(function (a) { return a.url !== chemin; });
        var memes = autres;
        if (courant && courant.rayon) {
          var duRayon = autres.filter(function (a) { return a.rayon === courant.rayon; });
          if (duRayon.length >= 4) {
            memes = duRayon;
          }
        }
        grille.innerHTML = memes.slice(0, 5).map(carte).join('');
      }
    }

    var vus = lireVus()
      .filter(function (u) { return u !== chemin; })
      .map(function (u) { return catalogue.filter(function (a) { return a.url === u; })[0]; })
      .filter(Boolean);

    if (vus.length) {
      var clone = null;
      if (section) {
        clone = section.cloneNode(true);
      } else if (modeleRangee) {
        /* pas de rangée sur cette fiche : on prend celle mise en réserve */
        var bac = document.createElement('div');
        bac.innerHTML = modeleRangee;
        clone = bac.firstElementChild;
      }
      if (clone) {
        var titre = clone.querySelector('h2');
        if (titre) {
          titre.textContent = 'Récemment consultés';
        }
        var sous = clone.querySelector('.ludik-section-head p');
        if (sous) {
          sous.textContent = 'Les fiches que vous venez d\'ouvrir.';
        }
        var dedans = clone.querySelector('.products');
        if (dedans) {
          dedans.innerHTML = vus.slice(0, 5).map(carte).join('');
        }
        if (section) {
          section.parentNode.insertBefore(clone, section.nextSibling);
        } else {
          var colonne = document.querySelector('#center-column');
          if (colonne) {
            colonne.appendChild(clone);
          }
        }
      }
    }

    memoriserVue(chemin);
  }

  /* ------------------------------------------------------------ démarrage */

  function demarrer() {
    majCompteur();
    chargerCatalogue().then(function () {
      ajusterCompteurs();
      majCompteur();
    });

    var chemin = window.location.pathname.replace(/\.html$/, '');

    /* seule la page de repli porte cette balise */
    if (document.querySelector('meta[name="demo-rayon"]')) {
      rendreRayon();
    }
    if (chemin === '/recherche') {
      rendreResultats();
    }
    if (chemin === '/panier') {
      /* le catalogue sert à repêcher les couvertures manquantes */
      chargerCatalogue().then(rendrePanier);
    }
    if (/\.html$/.test(window.location.pathname)) {
      chargerCatalogue().then(function () { rendreRangees(window.location.pathname); });
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
        '[data-ps-action="add-voucher"], .cart-summary__actions a, .checkout .btn-primary'
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
