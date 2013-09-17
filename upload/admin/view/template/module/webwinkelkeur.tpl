<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/information.png" alt="" /> Webwinkelkeur</h1>
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
</div>
<?php echo $footer; ?>
<?php // vim: set sw=2 sts=2 et ft=php :
