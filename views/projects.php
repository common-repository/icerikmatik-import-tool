<?php include 'head.php';?>
<div class="wrap">
  <?php include 'messages.php'?>
  <h1>Projeler</h1>
  <table class="wp-list-table widefat fixed striped posts">
    <thead>
      <tr>
        <th>Proje Başlığı</th>
      </tr>
    </thead>
    <tbody>
      <?php if ('success' == $projects->status && sizeof($projects->data) > 0): ?>
        <?php foreach ($projects->data as $project):?>
          <tr>
            <td><a href="<?=menu_page_url('icerikmatik.projects', false) . "&project_id={$project->id}"?>"><?=$project->title?></a></td>
          </tr>
        <?php endforeach;?>
      <?php endif;?>
    </tbody>
  </table>
</div>
