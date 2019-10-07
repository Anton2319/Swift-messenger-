<?php
/* Smarty version 3.1.30, created on 2019-08-20 17:44:32
  from "e4f7c461ea6b9d6ff8800c317d6ec665571017cc" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5c318063cbe9_85590038',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5c318063cbe9_85590038 (Smarty_Internal_Template $_smarty_tpl) {
echo \S::safeHTML($_smarty_tpl->tpl_vars['settings']->value['app_title']);?>
 notifications (<?php echo $_smarty_tpl->tpl_vars['info']->value['actions'][0]['info']['userInfo']['name'];?>
: <?php echo $_smarty_tpl->tpl_vars['info']->value['actions'][0]['info']['actionDescription'];?>
)<?php }
}
