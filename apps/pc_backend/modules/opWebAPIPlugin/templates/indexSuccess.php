<h2><?php echo __('API設定') ?></h2>

<p><?php echo __('API へのアクセスを許可する IP アドレスを入力してください。') ?></p>
<p><?php echo __('※改行区切りで複数の IP アドレスを入力することができます。') ?></p>

<form action="<?php url_for('opWebAPIPlugin/index') ?>" method="post">
<table>
<?php echo $form ?>
<tr>
<td colspan="2"><input type="submit" value="<?php echo __('変更') ?>" /></td>
</tr>
</table>
</form>
