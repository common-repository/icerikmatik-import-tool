<?php include 'head.php';?>
<div class="wrap">
  <?php include 'messages.php'?>
  <h1>Siparişler</h1>
  <table class="wp-list-table widefat fixed striped posts">
    <thead>
      <tr>
        <th>Siparişler (<?=$project->data->title?>)</th>
      </tr>
    </thead>
    <tbody>
      <?php if ('success' == $orders->status && sizeof($orders->data) > 0): ?>
        <?php foreach ($orders->data as $order):?>
          <tr>
            <td>
              <?php if ((int) $order->completed_posts > 0): ?>
                <a href="<?=menu_page_url('icerikmatik.projects', false) . "&project_id={$project->data->id}&order_id={$order->id}"?>"><?=implode(' / ', [$order->type, $order->category, $order->date])?></a>
              <?php else: ?>
                <?=implode(' / ', [$order->type, $order->category, $order->date])?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach;?>
      <?php endif;?>
    </tbody>
  </table>
</div>
