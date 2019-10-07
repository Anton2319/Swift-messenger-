<?php
/* Smarty version 3.1.30, created on 2019-08-20 17:44:32
  from "d67c09f17aab7d66f82675c57c4974c0ad01da83" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5c31806065b2_34118434',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5c31806065b2_34118434 (Smarty_Internal_Template $_smarty_tpl) {
?>
<div style="font-family:tahoma,arial,verdana,sans-serif;font-size:13px;">
		Hi <?php echo $_smarty_tpl->tpl_vars['info']->value['userInfo']['name'];?>
,<br>
		<br>

		<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['info']->value['actions'], 'action');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['action']->value) {
?>
			<?php echo $_smarty_tpl->tpl_vars['action']->value['message'];?>

		<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl);
?>


		<br>
		Best regards,<br>
		<br>
		<a href="<?php echo $_smarty_tpl->tpl_vars['config']->value['url']['root'];?>
"><?php echo $_smarty_tpl->tpl_vars['config']->value['url']['root'];?>
</a>
</div><?php }
}
