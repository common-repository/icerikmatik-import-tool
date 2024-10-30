<?php include 'head.php';?>
<div class="wrap">
  <?php foreach($this->getMessages() as $message):?>
  <div id="message" class="updated notice notice-<?=$message['status']?> is-dismissible"><p><?=$message['message']?>.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
  <?php endforeach;?>

  <h1>İçerikmatik Ayarlar</h1>
  <p>Eklenti ayarları</p>

  <form action="<?=menu_page_url('icerikmatik.settings', false)?>" method="post">
    <table class="form-table">
      <tbody>
        <tr>
          <th><label for="api_key">API anahtarı</label></th>
          <td><input type="text" name="api_key" id="api_key" value="<?=$apiKey?>"></td>
        </tr>
      <tbody>
    </table>
    <p class="submit"><button type="submit" class="button button-primary">Kaydet</button></p>
  </form>
</div>
