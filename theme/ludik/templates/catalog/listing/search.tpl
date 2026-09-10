{**
 * Résultats de recherche.
 *
 * Le titre annonce le volume et le terme cherché plutôt qu'un « Résultats de
 * la recherche » qui n'apprend rien : « 141 résultats pour « Naruto » ».
 * En dessous, des raccourcis vers les rayons où la recherche a le plus de
 * sens ; ils rejouent la même requête restreinte à un rayon.
 *
 * La grille, les filtres et le tri sont ceux des pages de rayon : même
 * composant de vignette, même colonne de filtres, aucune variante.
 *}
{extends file='catalog/listing/product-list.tpl'}

{block name='product_list_header'}
  {$ludik_nb = $listing.pagination.total_items|default:0}

  <div id="js-product-list-header">
    <div class="category__header">
      <div class="category__header-main">
        {if empty($search_string)}
          {include file='components/page-title-section.tpl' title='Rechercher un article'}
          <p class="category__count">Indiquez un titre, une licence, un auteur ou une référence.</p>

        {elseif $ludik_nb > 0}
          {include file='components/page-title-section.tpl'
                   title="{$ludik_nb|number_format:0:',':' '} résultat{if $ludik_nb > 1}s{/if} pour « {$search_string|escape:'html':'UTF-8'} »"}
          <p class="category__count">Jeux, BD, mangas et accessoires correspondant à votre recherche.</p>

        {else}
          {include file='components/page-title-section.tpl'
                   title="Aucun résultat pour « {$search_string|escape:'html':'UTF-8'} »"}
          <p class="category__count">Vérifiez l'orthographe, ou cherchez avec un mot de moins.</p>
        {/if}

        {if !empty($search_string) && $ludik_nb > 0}
          <div class="lud-chips" role="list" aria-label="Restreindre la recherche à un rayon">
            {foreach from=[
                ['id' => 4, 'label' => 'Jeux de société'],
                ['id' => 5, 'label' => 'Cartes à collectionner'],
                ['id' => 6, 'label' => 'BD, mangas, romans'],
                ['id' => 3, 'label' => 'Jeux de rôle'],
                ['id' => 8, 'label' => 'Loisirs créatifs']
              ] item=ludik_rayon}
              <a class="lud-chip" role="listitem"
                 href="{url entity='category' id=$ludik_rayon.id params=['s' => $search_string]}">
                {$ludik_rayon.label}
              </a>
            {/foreach}
          </div>
        {/if}
      </div>
    </div>
  </div>
{/block}
