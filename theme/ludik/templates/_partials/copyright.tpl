{**
 * Bas du pied de page : mention de propriété à gauche, mention du logiciel à
 * droite. Le gabarit du thème parent n'affichait que la seconde, centrée.
 *}
{block name='copyright'}
  <div class="copyright ludik-copyright">
    <p class="ludik-copyright__gauche">
      © {'Y'|date} Ludik.nc - Tous droits réservés.
    </p>

    <p class="ludik-copyright__droite">
      <a href="https://www.prestashop-project.org/" target="_blank" rel="noopener noreferrer nofollow">
        Propulsé par PrestaShop
      </a>
    </p>
  </div>
{/block}
