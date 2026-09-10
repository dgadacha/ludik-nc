<?php
/**
 * Place la recherche, le compte, le panier et le menu sur les emplacements
 * attendus par l'en-tête Ludik (deux rangées).
 * Usage : docker exec ludik-ps php /scripts/11-header-hooks.php
 */
require_once '/var/www/html/config/config.inc.php';

$db = Db::getInstance();

function moveModule(string $name, array $hooks): void
{
    $m = Module::getInstanceByName($name);
    if (!$m || !$m->id) { echo "module absent : $name\n"; return; }
    $db = Db::getInstance();
    $db->execute('DELETE FROM ' . _DB_PREFIX_ . 'hook_module WHERE id_module = ' . (int) $m->id);
    foreach ($hooks as $pos => $hookName) {
        $idHook = (int) Hook::getIdByName($hookName);
        if (!$idHook) { echo "hook inconnu : $hookName\n"; continue; }
        $db->insert('hook_module', [
            'id_module' => (int) $m->id,
            'id_shop' => (int) Configuration::get('PS_SHOP_DEFAULT'),
            'id_hook' => $idHook,
            'position' => $pos + 1,
        ], false, true, Db::INSERT_IGNORE);
    }
    echo "$name → " . implode(', ', $hooks) . "\n";
}

moveModule('ps_searchbar', ['displaySearch']);
moveModule('ps_mainmenu', ['displayNavFullWidth']);
moveModule('ps_customersignin', ['displayNav2']);
moveModule('ps_shoppingcart', ['displayNav2']);
moveModule('ps_contactinfo', ['displayFooter']);
moveModule('blockreassurance', ['displayFooterBefore', 'displayProductAdditionalInfo']);

/* ordre dans displayNav2 : compte puis panier */
$idNav2 = (int) Hook::getIdByName('displayNav2');
foreach (['ps_customersignin' => 1, 'ps_shoppingcart' => 2] as $name => $pos) {
    $m = Module::getInstanceByName($name);
    if ($m && $m->id) {
        $db->execute('UPDATE ' . _DB_PREFIX_ . 'hook_module SET position = ' . $pos
            . ' WHERE id_hook = ' . $idNav2 . ' AND id_module = ' . (int) $m->id);
    }
}

/* le module maison fournit les tuiles de rayons sous la navigation */
$lud = Module::getInstanceByName('ludikhome');
if ($lud && $lud->id) {
    foreach (['displayNavFullWidth' => 2, 'displayHome' => 1, 'displayFooterBefore' => 2] as $hookName => $pos) {
        $idHook = (int) Hook::getIdByName($hookName);
        if (!$idHook) { continue; }
        $db->insert('hook_module', [
            'id_module' => (int) $lud->id, 'id_shop' => (int) Configuration::get('PS_SHOP_DEFAULT'),
            'id_hook' => $idHook, 'position' => $pos,
        ], false, true, Db::INSERT_IGNORE);
    }
    echo "ludikhome → displayHome, displayNavFullWidth, displayFooterBefore\n";
}

echo "Emplacements de l'en-tête en place.\n";
