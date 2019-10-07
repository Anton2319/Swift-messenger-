<?php
/* Smarty version 3.1.30, created on 2019-09-24 20:07:12
  from "/var/www/html/admin/system/modules/custom_actions/sections/cpanel/html/defaults_edit.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d8a777077ea01_09277343',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'bc1cbfc812356043737a2c4282a9c2dcd9ffda26' => 
    array (
      0 => '/var/www/html/admin/system/modules/custom_actions/sections/cpanel/html/defaults_edit.html',
      1 => 1559263624,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d8a777077ea01_09277343 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
FR.entry = <?php echo $_smarty_tpl->tpl_vars['app']->value['entry'];?>
;
FR.types = <?php echo $_smarty_tpl->tpl_vars['app']->value['types'];?>
;
FR.viewers = <?php echo $_smarty_tpl->tpl_vars['app']->value['viewers'];?>
;
ScriptMgr.load({ scripts:[
	'?module=fileman&section=utils&sec=Admin%3A%20Plugins&lang=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['language']);?>
&v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
&page=translation.js',
	'js/cpanel/forms/edit_default_viewer.js?v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
'
]});
<?php echo '</script'; ?>
><?php }
}
