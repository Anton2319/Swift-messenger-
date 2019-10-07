<?php
/* Smarty version 3.1.30, created on 2019-08-20 17:43:47
  from "/var/www/html/admin/system/modules/cpanel/sections/settings/html/pages/misc.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5c315318be66_58787254',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '79eb4fac45e8fe7db2987a0e5603b73b61cdb5d7' => 
    array (
      0 => '/var/www/html/admin/system/modules/cpanel/sections/settings/html/pages/misc.html',
      1 => 1559263624,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5c315318be66_58787254 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
FR.settings = <?php echo $_smarty_tpl->tpl_vars['app']->value['AllSettings'];?>
;
ScriptMgr.load({ scripts:[
	'?module=fileman&section=utils&sec=Admin%3A%20Setup&lang=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['language']);?>
&v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
&page=translation.js',
	'js/cpanel/forms/settings_misc.js?v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
'
]});
<?php echo '</script'; ?>
><?php }
}
