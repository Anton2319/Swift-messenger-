<?php
/* Smarty version 3.1.30, created on 2019-08-20 17:44:24
  from "/var/www/html/admin/system/modules/custom_actions/sections/cpanel/html/edit.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5c3178debd37_74726796',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '90c9a291cccfdd38e962489d7643d8f190af75c4' => 
    array (
      0 => '/var/www/html/admin/system/modules/custom_actions/sections/cpanel/html/edit.html',
      1 => 1559263624,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5c3178debd37_74726796 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
FR.pluginInfo = <?php echo $_smarty_tpl->tpl_vars['app']->value['pluginInfo'];?>
;
ScriptMgr.load({ scripts:[
	'?module=fileman&section=utils&sec=Admin%3A%20Plugins&lang=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['language']);?>
&v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
&page=translation.js',
	'js/cpanel/forms/edit_plugin_settings.js?v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
'
]});
<?php echo '</script'; ?>
><?php }
}
