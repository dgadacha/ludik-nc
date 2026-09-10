{**
 * Hero d'accueil : grandes campagnes visuelles, administrables.
 *
 * L'image ne porte aucun texte incrusté. Tout le texte vient des champs de la
 * diapositive, donc du back-office :
 *   Titre       -> « Rayon | Grand titre » ; la barre verticale sépare la
 *                  surtitre du titre. Elle est facultative : sans elle, tout
 *                  le champ sert de titre. Ce détour existe parce que le
 *                  module n'offre que quatre champs de texte.
 *   Légende     -> l'accroche sous le titre
 *   Description -> le libellé du bouton
 *   Lien        -> la destination du bouton et de la campagne entière
 *
 * Le lien enveloppe la campagne pour que toute la surface soit cliquable ; le
 * bouton est un span, un lien dans un lien n'étant pas valide.
 *
 * La bande du bas ne dépend pas de la diapositive : ce sont les quatre repères
 * de la boutique, repris de la page « Commande et Livraison ».
 *}
{if $homeslider.slides}
  <section class="ludik-hero">
    <div
      id="ps_imageslider"
      class="ludik-hero__carousel carousel slide"
      {if $homeslider.speed > 0}data-bs-ride="carousel"{/if}
      {if $homeslider.pause !== "hover"}data-bs-pause="false"{/if}
      {if $homeslider.wrap === "false"}data-bs-wrap="false"{/if}
    >
      <div class="carousel-inner ludik-hero__inner" role="list">
        {foreach from=$homeslider.slides item=slide name='homeslider'}
          {* découpage « Rayon | Titre » avec regex_replace, seul outil de
             manipulation de chaîne disponible : Smarty refuse les fonctions
             PHP comme modificateurs, et un explode faisait échouer le rendu
             du bloc entier, sans message d'erreur. *}
          {$ludik_avant = $slide.title|regex_replace:'/\|.*$/':''|trim}
          {$ludik_apres = $slide.title|regex_replace:'/^[^|]*\|/':''|trim}
          {$ludik_kicker = ($ludik_avant != $slide.title) ? $ludik_avant : ''}
          {$ludik_titre = ($ludik_avant != $slide.title) ? $ludik_apres : $slide.title}

          <div class="carousel-item ludik-hero__item{if $slide@first} active{/if}" role="listitem"
               {if $homeslider.speed > 0}data-bs-interval="{$homeslider.speed}"{/if}>
            {if !empty($slide.url)}<a class="ludik-hero__link" href="{$slide.url}">{/if}

              <img class="ludik-hero__image"
                   src="{$slide.image_url}"
                   alt="{if !empty($slide.legend)}{$slide.legend|escape:'html':'UTF-8'}{else}{$ludik_titre|escape:'html':'UTF-8'}{/if}"
                   width="{$slide.sizes[0]}" height="{$slide.sizes[1]}"
                   {if $slide@first}fetchpriority="high"{else}loading="lazy"{/if}>

              <div class="ludik-hero__contenu">
                {if $ludik_kicker}
                  <p class="ludik-hero__kicker">{$ludik_kicker}</p>
                {/if}
                {if $ludik_titre}
                  <p class="ludik-hero__titre">{$ludik_titre}</p>
                {/if}
                {if $slide.legend}
                  <p class="ludik-hero__accroche">{$slide.legend}</p>
                {/if}
                {if $slide.description}
                  <span class="ludik-hero__cta lud-btn lud-btn--lg">
                    {$slide.description|strip_tags}
                    <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
                  </span>
                {/if}
              </div>

            {if !empty($slide.url)}</a>{/if}
          </div>
        {/foreach}
      </div>

      <p class="ludik-hero__script" aria-hidden="true">Plus qu'une passion,<br>un univers&nbsp;!</p>

      {if $homeslider.slides|count > 1}
        <button class="ludik-hero__fleche ludik-hero__fleche--prev" type="button"
                data-bs-target="#ps_imageslider" data-bs-slide="prev" aria-label="Campagne précédente">
          <svg class="lud-i" aria-hidden="true"><use href="#i-chevron-left"></use></svg>
        </button>

        <button class="ludik-hero__fleche ludik-hero__fleche--next" type="button"
                data-bs-target="#ps_imageslider" data-bs-slide="next" aria-label="Campagne suivante">
          <svg class="lud-i" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
        </button>

        <div class="carousel-indicators ludik-hero__pagination">
          {foreach from=$homeslider.slides item=slide name='homeslider'}
            <button type="button"
                    data-bs-target="#ps_imageslider"
                    data-bs-slide-to="{$slide@index}"
                    aria-label="Aller à la campagne {$slide@iteration}"
                    class="{if $slide@first}active{/if}"
                    {if $slide@first}aria-current="true"{/if}></button>
          {/foreach}
        </div>
      {/if}

      <div class="ludik-hero__reperes">
        <span><svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-layers"></use></svg>Des milliers de références</span>
        <span><svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-users"></use></svg>Conseils de passionnés</span>
        <span><svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-store"></use></svg>En boutique et en ligne</span>
        <span><svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-truck"></use></svg>Livraison en Nouvelle-Calédonie</span>
      </div>
    </div>
  </section>
{/if}
