<?php if(isset($settings)): ?>
<script type="text/javascript">
(function() {
    <?php foreach($settings as $key => $value): ?>
    <?php echo $key; ?> = <?php echo json_encode($value); ?>;
    <?php endforeach; ?>
    var js = document.createElement("script"); js.type = "text/javascript";
    js.async = true; js.src = "//<?php echo $msg['APP_DOMAIN']; ?>/js/sidebar.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(js, s);
})();
</script>
<?php endif; ?>
<?php if(isset($rich_snippet)): ?>
<?php echo $rich_snippet; ?>
<?php endif; ?>
<?php // vim: set ft=php :
