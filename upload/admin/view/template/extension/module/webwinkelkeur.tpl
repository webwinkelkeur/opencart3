<?= $header; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form" class="btn btn-primary"
                data-toggle="tooltip" title="<?= $button_save; ?>" >
          <i class="fa fa-save"></i>
        </button>
        <a href="<?= $cancel; ?>" class="btn btn-default"
           data-toggle="tooltip" title="<?= $button_cancel; ?>">
          <i class="fa fa-reply"></i>
        </a>
      </div>
      <h1><?= $msg['WEBWINKELKEUR']; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) : ?>
        <li><a href="<?= $breadcrumb['href']; ?>"><?= $breadcrumb['text']; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <form action="" method="post" enctype="multipart/form-data" class="form-horizontal" id="form" name="webwinkelkeur">
      <?php if(count($stores) > 1): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><i class="fa fa-pencil"></i><?= $msg['SELECT_SHOP']; ?></h3>
        </div>
        <div class="panel-body">
          <div class="form-group">
            <label class="col-sm-2 control-label"><?= $msg['ACTIVE_SHOP']; ?></label>
            <div class="col-sm-10">
              <input type="hidden" id="redirStore" name="selectStore">
              <select class="form-control" name="store_id" onchange="switchStore();">
                <?php foreach($stores as $store): ?>
                <option value="<?= $store['store_id'] ?>"
                        <?php if($store['store_id'] == $view_stores[0]['settings']['store_id']) echo "selected"; ?>>
                  <?= $store['name'] ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php foreach ($view_stores as $store): ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><i class="fa fa-pencil"></i><?= $msg['SETTINGS']; ?></h3>
        </div>
        <div class="panel-body">
          <div class="form-group required">
            <label class="col-sm-2 control-label"><?= $msg['SHOP_ID']; ?></label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="store[shop_id]" value="<?= $store['settings']['shop_id']; ?>">
              <?php if ($error_shopid): ?>
              <div class="text-danger"><?= $error_shopid; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label"><?= $msg['API_KEY']; ?></label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="store[api_key]" value="<?= $store['settings']['api_key']; ?>">
              <?php if ($error_apikey): ?>
              <div class="text-danger"><?= $error_apikey; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">
            <span data-toggle="tooltip" title="<?= $msg['JAVASCRIPT_TITLE']; ?>">
              <?= $msg['JAVASCRIPT']; ?>
            </span>
            </label>
            <div class="col-sm-10">
              <label class="radio-inline">
                <input type="radio" value="1" name="store[javascript]"
                       <?php if ($store['settings']['javascript']): ?>checked<?php endif; ?>>
                <?= $msg['YES']; ?>
              </label>
              <label class="radio-inline">
                <input type="radio" value="0" name="store[javascript]"
                       <?php if (!$store['settings']['javascript']): ?>checked<?php endif; ?>>
                <?= $msg['NO']; ?>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">
              <?= $msg['INVITE']; ?>
            </label>
            <div class="col-sm-10">
              <select class="form-control" name="store[invite]">
                <option value="1" <?php if ($store['settings']['invite'] == 1): ?>selected<?php endif; ?>><?= $msg['INVITE_1']; ?></option>
                <option value="2" <?php if ($store['settings']['invite'] == 2): ?>selected<?php endif; ?>><?= $msg['INVITE_2']; ?></option>
                <option value="0" <?php if ($store['settings']['invite'] == 0): ?>selected<?php endif; ?>><?= $msg['INVITE_0']; ?></option>
              </select>
            </div>
            <div class="col-sm-10 pull-right">
              <label class="checkbox-inline">
                <input name="store[limit_order_data]" value="1" type="checkbox"
                       <?php if ($store['settings']['limit_order_data']): ?>checked<?php endif; ?>
                       class="form-control checkbox-inline">
                <span data-toggle="tooltip" title="<?= $msg['LIMIT_ORDER_DATA_DETAILS']; ?>">
                  <?= $msg['LIMIT_ORDER_DATA_LABEL']; ?>
                </span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">
            <span data-toggle="tooltip" title="<?= $msg['INVITE_DELAY_TITLE']; ?>">
              <?= $msg['INVITE_DELAY']; ?>
            </span>
            </label>
            <div class="col-sm-10">
              <input type="text" size="2" class="form-control"
                     value="<?= $store['settings']['invite_delay']; ?>"
                     name="store[invite_delay]">
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">
            <span data-toggle="tooltip" title="<?= $msg['ORDER_STATUS_TITLE']; ?>" >
              <?= $msg['ORDER_STATUS']; ?>
            </span>
            </label>
            <div class="col-sm-10">
              <div class="well well-sm" style="height: 150px; overflow: auto;">
                <?php foreach ($order_statuses as $order_status): ?>
                <div class="checkbox webwinkelkeur-order-statuses">
                  <label>
                    <input type="checkbox" name="store[order_statuses][]"
                           value="<?= $order_status['order_status_id']; ?>"
                           <?php if (in_array($order_status['order_status_id'], $store['settings']['order_statuses'])) echo 'checked'; ?>>
                    <?= $order_status['name']; ?>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">
            <span data-toggle="tooltip" title="<?= $msg['RICH_SNIPPET_TITLE']; ?>">
              <?= $msg['RICH_SNIPPET']; ?>
            </span>
            </label>
            <div class="col-sm-10">
              <label class="radio-inline">
                <input type="radio" value="1" name="store[rich_snippet]"
                       <?php if ($store['settings']['rich_snippet']): ?>checked<?php endif; ?>>
                <?= $msg['YES']; ?>
              </label>
              <label class="radio-inline">
                <input type="radio" value="0" name="store[rich_snippet]"
                       <?php if (!$store['settings']['rich_snippet']): ?>checked<?php endif; ?>>
                <?= $msg['NO']; ?>
              </label>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </form>
    <?php if ($invite_errors): ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-exclamation-triangle"></i><?= $msg['INVITE_ERRORS']; ?></h3>
      </div>
      <div class="panel-body">
        <table>
          <?php foreach ($invite_errors as $invite_error): ?>
          <tr>
            <td style="padding-right:10px;">
              <?= $invite_error['time'] instanceof DateTimeInterface ?
                  $invite_error['time']->format("Y-m-d H:i:s") : ''; ?>
            </td>
            <td>
              <?php if ($invite_error['message']): ?>
              <?= $invite_error['message']; ?>
              <?php else: ?>
              <?= $msg['INVITE_GENERIC_ERROR']; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
    <?php endif; ?>
    <p class="text-center">
      WebwinkelKeur <?= $version; ?>
    </p>
  </div>
</div>
<script>
jQuery(function($) {
    var $container = $('.webwinkelkeur-order-statuses');
    $container.find('label:has(input:checked)').css('font-weight', 'bold');
    $container.find('input').change(function() {
        this.parentNode.style.fontWeight = this.checked ? 'bold' : 'normal';
    });
});
function switchStore() {
  $('#redirStore').val(true);
  $('#form').submit();
}
</script>
<?= $footer; ?>
<?php /* vim: set sw=2 sts=2 et : */
