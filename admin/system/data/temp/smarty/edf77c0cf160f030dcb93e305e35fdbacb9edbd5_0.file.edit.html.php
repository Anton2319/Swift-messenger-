<?php
/* Smarty version 3.1.30, created on 2019-08-19 03:54:46
  from "/var/www/html/admin/system/modules/users/sections/cpanel/html/edit.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5a1d86dadb79_26211337',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'edf77c0cf160f030dcb93e305e35fdbacb9edbd5' => 
    array (
      0 => '/var/www/html/admin/system/modules/users/sections/cpanel/html/edit.html',
      1 => 1559263630,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5a1d86dadb79_26211337 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
FR.roles = <?php echo $_smarty_tpl->tpl_vars['app']->value['roles'];?>
;
FR.userInfo = <?php echo $_smarty_tpl->tpl_vars['app']->value['userInfo'];?>
;
FR.settings = {
	disable_file_history: <?php if ($_smarty_tpl->tpl_vars['app']->value['settings']['disable_file_history']) {?>true<?php } else { ?>false<?php }?>
};
FR.FileRunInstallPath = '<?php echo \S::forHTML($_smarty_tpl->tpl_vars['app']->value['config']['path']['root']);?>
';
FR.adminHomeFolderPath = '<?php echo \S::forHTML($_smarty_tpl->tpl_vars['app']->value['user']['perms']['homefolder']);?>
';
FR.currentUserPerms = {
	admin_homefolder_template: <?php if ($_smarty_tpl->tpl_vars['app']->value['user']['perms']['admin_homefolder_template']) {?>true<?php } else { ?>false<?php }?>
}
ScriptMgr.load({ scripts:[
	'?module=fileman&section=utils&sec=Admin%3A%20Users&calendar=1&lang=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['language']);?>
&v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
&page=translation.js',
	'js/cpanel/forms/edit_user.js?v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
'
]});
<?php echo '</script'; ?>
><?php }
}
