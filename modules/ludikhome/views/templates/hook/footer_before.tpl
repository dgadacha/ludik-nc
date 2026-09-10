{**
 * Bas de page commun : la soirée du mardi, les avantages de la boutique, les
 * deux magasins. Seuls les avantages s'affichent sur toutes les pages.
 *
 * Les textes de la soirée et des avantages sont repris du site actuel :
 * bandeau défilant de l'accueil, bandeau d'en-tête et page « Commande et
 * Livraison ». Les boutiques, elles, sont lues dans les fiches magasin du
 * back-office : adresse, téléphone, courriel, horaires et photo se modifient
 * depuis « Préférences > Magasins », sans toucher au thème.
 *}

{if $ludik_home}
  {* Bannière événementielle : la date d'abord, l'illustration en fond est la
     mosaïque de couvertures composée pour le diaporama. *}
  <div class="container-md">
    <section class="ludik-event" aria-labelledby="ludik-event-titre">
      <div class="ludik-event__fond" aria-hidden="true"></div>

      <div class="ludik-event__corps">
        <span class="ludik-event__kicker">Chaque mardi, ouvert à tous</span>
        <h2 class="ludik-event__titre" id="ludik-event-titre">Les soirées jeux de société</h2>
        <p class="ludik-event__texte">
          Pour découvrir les jeux, Ludik propose chaque mardi une soirée au Fronton
          Etchekhan, à l'Amicale Basque. Si vous y retirez une commande, votre
          entrée vous est offerte.
        </p>
        <a href="{$ludik_soiree_link}" class="lud-btn lud-btn--lg">
          Voir la prochaine soirée
          <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
        </a>
      </div>

      <div class="ludik-event__date">
        <span class="ludik-event__date-label">Prochaine soirée</span>
        <span class="ludik-event__date-jour">{$ludik_next_tuesday}</span>
        <span class="ludik-event__date-heure">
          <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-clock"></use></svg>
          18h45 à 22h
        </span>
        <span class="ludik-event__date-lieu">
          <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-map-pin"></use></svg>
          Fronton Etchekhan - Amicale Basque, Motor Pool, Nouméa
        </span>
      </div>
    </section>
  </div>
{/if}

{* Avantages : sur toutes les pages, juste avant le pied de page. *}
<div class="container-md">
  <section class="ludik-trust" aria-label="Les avantages de la boutique">
    <div class="ludik-trust__item">
      <svg class="lud-i" aria-hidden="true"><use href="#i-store"></use></svg>
      <span><strong>Retrait en boutique</strong>Mis de côté dès réception de la commande</span>
    </div>
    <div class="ludik-trust__item">
      <svg class="lud-i" aria-hidden="true"><use href="#i-truck"></use></svg>
      <span><strong>Livraison OPT sous 24 h</strong>Tout le territoire, les îles et le Pacifique</span>
    </div>
    <div class="ludik-trust__item">
      <svg class="lud-i" aria-hidden="true"><use href="#i-dices"></use></svg>
      <span><strong>Soirée jeux du mardi</strong>Entrée offerte pour un retrait de commande</span>
    </div>
    <div class="ludik-trust__item">
      <svg class="lud-i" aria-hidden="true"><use href="#i-credit-card"></use></svg>
      <span><strong>Plusieurs moyens de paiement</strong>Carte, PayPal, chèque, espèces, virement</span>
    </div>
  </section>
</div>

{if $ludik_home}
  <div class="container-md">
    <section class="ludik-section">
      <div class="ludik-section-head">
        <div>
          <h2>Nos deux boutiques</h2>
          <p>Retrait en boutique : votre article est mis de côté dès réception de la commande.</p>
        </div>
        <a href="{$ludik_stores_link}">Voir sur la carte</a>
      </div>

      <div class="ludik-shops">
        {foreach from=$ludik_boutiques item=b}
          <article class="ludik-shop">
            <div class="ludik-shop__photo">
              <img src="{$b.image}" alt="{$b.name|escape:'html':'UTF-8'}" loading="lazy" width="287" height="160">
            </div>

            <div class="ludik-shop__corps">
              <h3 class="ludik-shop__nom">{$b.name}</h3>

              <ul class="ludik-shop__infos">
                <li>
                  <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-map-pin"></use></svg>
                  <span>{$b.address|escape:'html':'UTF-8'|nl2br nofilter}<br>{$b.city}</span>
                </li>
                {if $b.phone}
                  <li>
                    <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-phone"></use></svg>
                    <a href="tel:+687{$b.phone|replace:' ':''}">{$b.phone}</a>
                  </li>
                {/if}
                {if $b.email}
                  <li>
                    <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-mail"></use></svg>
                    <a href="mailto:{$b.email}">{$b.email}</a>
                  </li>
                {/if}
              </ul>

              {* Les horaires et les boutons sont calés en bas de carte : les
                 deux boutiques n'ont pas les mêmes coordonnées renseignées, et
                 sans cela les lignes ne se répondaient pas d'une carte à
                 l'autre. *}
              {if $b.hours}
                <div class="ludik-shop__horaires">
                  <p class="ludik-shop__horaires-titre">
                    <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-clock"></use></svg>
                    Horaires
                  </p>
                  {foreach from=$b.hours item=h}
                    <span class="ludik-shop__horaire">
                      <span class="ludik-shop__jours">{$h.jours}</span>
                      <span class="ludik-shop__plage">{$h.plage}</span>
                    </span>
                  {/foreach}
                </div>
              {/if}

              <div class="ludik-shop__actions">
                <a class="lud-btn lud-btn--ghost lud-btn--sm" href="{$b.itineraire}"
                   target="_blank" rel="noopener">
                  <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-navigation"></use></svg>
                  Itinéraire
                </a>
                <a class="lud-btn lud-btn--ghost lud-btn--sm" href="{$urls.pages.contact}">
                  <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-message-square-text"></use></svg>
                  Nous contacter
                </a>
              </div>
            </div>
          </article>
        {/foreach}
      </div>
      </div>
    </section>
  </div>
{/if}
