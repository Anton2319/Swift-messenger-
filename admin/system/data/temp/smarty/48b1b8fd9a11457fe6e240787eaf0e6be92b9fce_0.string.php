<?php
/* Smarty version 3.1.30, created on 2019-08-19 05:38:06
  from "48b1b8fd9a11457fe6e240787eaf0e6be92b9fce" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5a35be972870_91716889',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5a35be972870_91716889 (Smarty_Internal_Template $_smarty_tpl) {
?>

Hi <?php echo \S::forHTML($_smarty_tpl->tpl_vars['info']->value['name']);?>
,<br>
<br>
Your user account for "<a href="<?php echo $_smarty_tpl->tpl_vars['app']->value['url']['root'];?>
"><?php echo $_smarty_tpl->tpl_vars['app']->value['url']['root'];?>
</a>" has just been created.<br>
You can login with the following information:<br>
<br>
Username: <strong><?php echo $_smarty_tpl->tpl_vars['info']->value['username'];?>
</strong><br>
Password: <strong><?php echo $_smarty_tpl->tpl_vars['info']->value['password'];?>
</strong><br>
<br>
Best regards,<br>
<br>
<a href="<?php echo $_smarty_tpl->tpl_vars['app']->value['url']['root'];?>
"><?php echo $_smarty_tpl->tpl_vars['app']->value['url']['root'];?>
</a>
<?php }
}
