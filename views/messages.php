<?php foreach($this->getMessages() as $message):?>
  <div id="message" class="updated notice notice-<?=$message['status']?> is-dismissible"><p><?=$message['message']?>.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
<?php endforeach;?>
