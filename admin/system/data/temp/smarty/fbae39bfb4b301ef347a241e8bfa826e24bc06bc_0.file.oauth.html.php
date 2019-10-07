<?php
/* Smarty version 3.1.30, created on 2019-08-19 05:37:17
  from "/var/www/html/admin/system/modules/cpanel/sections/settings/html/pages/oauth.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5a358dd924f4_27326446',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fbae39bfb4b301ef347a241e8bfa826e24bc06bc' => 
    array (
      0 => '/var/www/html/admin/system/modules/cpanel/sections/settings/html/pages/oauth.html',
      1 => 1559263624,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5a358dd924f4_27326446 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
FR.settings = <?php echo $_smarty_tpl->tpl_vars['app']->value['AllSettings'];?>
;
ScriptMgr.load({ scripts:[
	'?module=fileman&section=utils&sec=Admin%3A%20API&lang=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['language']);?>
&v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
&page=translation.js',
	'js/cpanel/forms/settings_oauth.js?v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
'
]});
<?php echo '</script'; ?>
><?php }
}
