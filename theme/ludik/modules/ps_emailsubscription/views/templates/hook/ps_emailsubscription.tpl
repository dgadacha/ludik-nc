{**
 * Inscription à la lettre d'information, en colonne du pied de page.
 *
 * Le gabarit du thème parent en fait une bande pleine largeur sur fond gris,
 * posée avant le pied de page : elle sortait de nulle part. Ici c'est une
 * carte, la quatrième colonne du pied, dans le vert du bloc.
 *}
<div class="ludik-foot__lettre" id="emailsubscription_anchor_{$hookName}">
  <p class="ludik-foot__lettre-titre">Restez informé</p>
  <p class="ludik-foot__lettre-texte">
    Nouveautés, arrivages et soirées jeux, dans votre boîte.
  </p>

  <form action="{$urls.current_url}#emailsubscription_anchor_{$hookName}" method="post">
    {if $msg}
      <p class="ludik-foot__lettre-message{if $nw_error} is-error{/if}" role="alert">{$msg}</p>
    {/if}

    <div class="ludik-foot__lettre-champ">
      <label class="visually-hidden" for="ludik-lettre-{$hookName}">Votre adresse électronique</label>
      <input id="ludik-lettre-{$hookName}"
             type="email" name="email" value="{$value}"
             placeholder="Votre adresse électronique"
             autocomplete="email" required>
      <button type="submit" name="submitNewsletter" aria-label="S'inscrire à la lettre d'information">
        <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
      </button>
    </div>

    {capture name="consentement"}{hook h='displayGDPRConsent' id_module=$id_module}{/capture}
    {if !empty($smarty.capture.consentement)}
      <div class="ludik-foot__lettre-consentement">{$smarty.capture.consentement nofilter}</div>
    {/if}

    {if $conditions}
      <p class="ludik-foot__lettre-mentions">{$conditions}</p>
    {/if}

    {hook h='displayNewsletterRegistration'}

    <input type="hidden" value="{$hookName}" name="blockHookName">
    <input type="hidden" name="action" value="0">
  </form>
</div>
