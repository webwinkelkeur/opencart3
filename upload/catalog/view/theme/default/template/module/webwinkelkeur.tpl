<script type="text/javascript">
(function() {
    <?php foreach($settings as $key => $value): ?>
    <?php echo $key; ?> = <?php echo json_encode($value); ?>;
    <?php endforeach; ?>
    var js = document.createElement("script"); js.type = "text/javascript";
    js.async = true; js.src = "//www.webwinkelkeur.nl/js/sidebar.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(js, s);
})();
</script>
<?php // vim: set ft=php :
