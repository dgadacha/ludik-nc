/* Recharge la feuille du thème en contournant le cache du navigateur.
   L'URL de custom.css ne porte pas de version : après une retouche, le
   navigateur sert l'ancienne feuille. À coller dans la console, ou à exécuter
   par l'outil de prévisualisation, pendant le travail sur le thème. */
document.querySelectorAll('link[rel="stylesheet"]').forEach((l) => {
  if (l.href.includes('custom.css') || l.href.includes('/assets/cache/')) {
    l.href = l.href.split('?')[0] + '?v=' + Date.now();
  }
});
