<h3 class="floated"><?php simpledir_i18n('PLUGIN_TITLE', true); ?></h3>
<div class="edit-nav">
  <a href="https://github.com/lokothodida/gs-simpledir/" target="_blank"><?php simpledir_i18n('DOCUMENTATION', true); ?></a>
  <div class="clear"></div>
</div>

<form name="settings" method="post">
  <label><?php simpledir_i18n('LABEL_FULLPATH', true); ?> (e.g. <code>/home/user/data/uploads/</code>): </label>
  <p><input class="text" name="dirpath" type="text" size="90" value="<?php echo $simpledir_conf['dirpath']; ?>"></p>

  <label><?php simpledir_i18n('LABEL_BASEURL', true); ?> (e.g. <code>/data/uploads/</code>):</label>
  <p><input class="text" name="urlpath" type="text" size="90" value="<?php echo $simpledir_conf['urlpath']; ?>"></p>

  <label><?php simpledir_i18n('LABEL_IGNORE', true); ?> (e.g. <code>php,txt</code>):</label>
  <p><input class="text" name="ignore" type="text" size="90" value="<?php echo implode(',', $simpledir_conf['ignore']); ?>"></p>

  <input name='submit_settings' class='submit' type='submit' value='<?php i18n('BTN_SAVESETTINGS'); ?>'><br />
</form>