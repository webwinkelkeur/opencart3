<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <?php foreach ($error_warning as $error_message): ?>
  <div class="warning"><?php echo $error_message; ?></div>
  <?php endforeach; ?>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/information.png" alt="" /> WebwinkelKeur</h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button">Opslaan</a><a href="<?php echo $cancel; ?>" class="button">Annuleren</a></div>
    </div>
    <div class="content">
      <form action="" method="post" enctype="multipart/form-data" id="form">
        <table class="form">
          <tr>
            <td><span class="required">*</span> Webwinkel ID:</td>
            <td><input type="text" name="shop_id" value="<?php echo $shop_id; ?>" /></td>
          </tr>
          <tr>
            <td><span class="required">*</span> API key:</td>
            <td><input type="text" name="api_key" value="<?php echo $api_key; ?>" /></td>
          </tr>
          <tr>
            <td>Sidebar weergeven:</td>
            <td>
              <label>
                <input type="radio" name="sidebar" value="1" <?php if($sidebar) echo "checked"; ?> />
                Ja
              </label>
              <label>
                <input type="radio" name="sidebar" value="0" <?php if(!$sidebar) echo "checked"; ?> />
                Nee
              </label>
            </td>
          </tr>
          <tr>
            <td>
              Uitnodiging versturen:<br />
              <span class="help">alleen beschikbaar voor Plus-leden</span>
            </td>
            <td>
              <label>
                <input type="radio" name="invite" value="1" <?php if($invite) echo "checked"; ?> />
                Ja
              </label>
              <label>
                <input type="radio" name="invite" value="0" <?php if(!$invite) echo "checked"; ?> />
                Nee
              </label>
            </td>
          </tr>
          <tr>
            <td>
              Wachttijd voor uitnodiging:<br/>
              <span class="help">de uitnodiging wordt verstuurd nadat het opgegeven aantal dagen is verstreken</span>
            </td>
            <td><input type="text" name="invite_delay" size="2" value="<?php echo $invite_delay; ?>" /></td>
          </tr>
        </table>
      </form>
    </div>
  </div>
  <?php if($invite_errors): ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/warning.png" alt="" /> Fouten opgetreden bij het versturen van uitnodigingen</h1>
    </div>
    <div class="content">
      <table>
        <?php foreach($invite_errors as $invite_error): ?>
        <tr>
          <td style="padding-right:10px;"><?php echo date('d-m-Y H:i', $invite_error['time']); ?></td>
          <td>
            <?php if($invite_error['response']): ?>
            <?php echo htmlentities($invite_error['response'], ENT_QUOTES, 'UTF-8'); ?>
            <?php else: ?>
            De Webwinkelkeur-server kon niet worden bereikt.
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php echo $footer; ?>
<?php // vim: set sw=2 sts=2 et ft=php :
