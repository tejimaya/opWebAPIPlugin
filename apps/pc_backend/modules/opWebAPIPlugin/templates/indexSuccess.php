<h2><?php echo __('API設定') ?></h2>

<form action="<?php url_for('opWebAPIPlugin/index') ?>" method="post">
<table>
<?php echo $form ?>
<tr>
<td colspan="2"><input type="submit" value="<?php echo __('変更') ?>" /></td>
</tr>
</table>
</form>
