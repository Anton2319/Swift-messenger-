<?php
/* Smarty version 3.1.30, created on 2019-08-19 05:37:20
  from "/var/www/html/admin/system/modules/software_update/sections/cpanel/html/default.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5d5a3590731dc7_67815180',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '9b0a40abb6d96d7e7bcabec77d66a962510a7002' => 
    array (
      0 => '/var/www/html/admin/system/modules/software_update/sections/cpanel/html/default.html',
      1 => 1559263630,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d5a3590731dc7_67815180 (Smarty_Internal_Template $_smarty_tpl) {
echo smarty_function_lang(array('section'=>"Admin: Settings"),$_smarty_tpl);?>

<?php echo '<script'; ?>
>
FR.currentVersion = '<?php echo $_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion'];?>
';
FR.uploadChunkSize = <?php echo $_smarty_tpl->tpl_vars['app']->value['upload_chunk_size'];?>
;
ScriptMgr.load({ scripts:[
	'?module=fileman&section=utils&sec=Admin%3A%20Software update&lang=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['language']);?>
&v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
&page=translation.js',
	'js/cpanel/software_update.js?v=<?php echo \S::forURL($_smarty_tpl->tpl_vars['app']->value['settings']['currentVersion']);?>
'
]});
<?php echo '</script'; ?>
><?php }
}
