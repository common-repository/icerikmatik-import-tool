<?php include 'head.php';?>
<div class="wrap">
  <?php include 'messages.php'?>
  <form action="<?=menu_page_url('icerikmatik.projects', false) . "&project_id={$project->data->id}&order_id={$order->data->id}&import=1"?>" method="post" id="import-form">
    <h1>İçerikler > İçeri Aktar</h1>

    <div class="tablenav top">
      <div class="alignleft actions bulkactions">
        <label for="category-select" class="screen-reader-text"><?=__('Categories')?></label>
        <select name="category" id="category-select">
          <option value=""><?=__('Categories')?></option>
          <?php foreach (get_categories(['hide_empty' => 0]) as $category): ?>
            <option value="<?=$category->term_id?>"><?=$category->name?></option>
          <?php endforeach; ?>
        </select>

        <label for="status-select" class="screen-reader-text"><?=__('Status')?></label>
        <select name="status" id="status-select">
          <option value=""><?=__('Status')?></option>
          <option value="draft"><?=__('Draft')?></option>
          <option value="publish"><?=__('Publish')?></option>
        </select>

        <label for="bulk-action-selector-top" class="screen-reader-text"><?=__('Select bulk action')?></label>
        <select name="action" id="bulk-action-selector-top">
          <option value=""><?=__('Bulk Actions')?></option>
          <option value="import"><?=__('Import')?></option>
        </select>
        <input type="submit" id="doaction" class="button action" value="<?=__('Apply')?>">
      </div>
      <br class="clear">
    </div>

    <table class="wp-list-table widefat fixed striped posts">
      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <input id="cb-select-all-1" type="checkbox">
          </td>
          <th><?=__('Title')?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ((int) $order->data->completed_posts > 0 && 'success' == $postTitles->status && sizeof($postTitles->data) > 0): ?>
          <?php foreach ($postTitles->data as $postTitle): ?>
            <tr>
              <th scope="row" class="check-column">
                <?php if (true !== $postTitle->exists): ?>
                  <input id="cb-select-<?=$postTitle->id?>" type="checkbox" name="posts[]" value="<?=$postTitle->id?>">
                <?php endif; ?>
              </th>
              <td><label for="cb-select-<?=$postTitle->id?>"><?=$postTitle->title?></label></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </form>
</div>

<script type="text/javascript">
  var importForm = jQuery('#import-form'),
      loading = '<?=dirname(plugin_dir_url(__FILE__)) . '/assets/loading.gif';?>';
      sendImportRequest = function(data, posts) {
          if (0 === posts.length)
              return;

          data.posts = [posts.shift()];

          var th = jQuery('#cb-select-' + data.posts[0]).parent(),
              currentHtml = th.html();
          th.html('<img style="margin-left:8px;" id="cb-select-' + data.posts[0] + '" src="' + loading + '">');

          jQuery.ajax({
              url: importForm.attr('action'),
              type: 'post',
              dataType: 'json',
              data: data,
              success: function(response) {

                  if ('success' != response.status) {
                      alert(response.message);
                      th.html(currentHtml);
                      return;
                  }

                  jQuery('#cb-select-' + data.posts[0]).remove();
                  sendImportRequest(data, posts);
              }
          });
      };

  importForm.submit(function(e) {
      e.preventDefault();
      var arrForm = jQuery(this).serializeArray(),
          data = {
              action: '',
              category: '',
              status: '',
          },
          posts = [],
          i;

      for (i = 0; i < arrForm.length; i++) {
          if ('posts[]' == arrForm[i].name)
              posts.push(arrForm[i].value);
          else
              data[arrForm[i].name] = arrForm[i].value;
      }

      sendImportRequest(data, posts); 
  });
</script>
