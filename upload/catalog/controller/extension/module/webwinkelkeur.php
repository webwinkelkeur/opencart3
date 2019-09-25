<?php

class ControllerExtensionModuleWebwinkelkeur extends Controller {

    private $settings;
    private $msg;

    public function __construct($registry) {
        parent::__construct($registry);

        $this->load->model('extension/module/webwinkelkeur');

        $this->settings = $this->model_extension_module_webwinkelkeur->getSettings();
        $this->msg = @include DIR_SYSTEM . 'library/webwinkelkeur-messages.php';
    }

    public function index($dummy) {
        $modules = $this->model_extension_module_webwinkelkeur->getModulesByCode('webwinkelkeur');

        if (empty($dummy) && count($modules))
            return;

        if (empty($this->settings['shop_id']))
            return;

        $data = array();

        if (empty($this->settings['rich_snippet'])) {
            return '';
        }

        $html = @$this->getRichSnippet();

        if (!$html) {
            return '';
        }

        return $html;
    }

    public function cron() {
        ignore_user_abort(true);

        $this->model_extension_module_webwinkelkeur->markCronRun();

        try {
            $this->model_extension_module_webwinkelkeur->sendInvites();
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo htmlentities($e->getMessage());
        }

        echo "ok";
    }

    private function getRichSnippet() {
        $tmp_dir = @sys_get_temp_dir();
        if(!is_writable($tmp_dir))
            $tmp_dir = '/tmp';
        if(!is_writable($tmp_dir))
            return;

        $url = sprintf('http://%s/shop_rich_snippet.php?id=%s',
            $this->msg['APP_DOMAIN'], (int) $this->settings['shop_id']);

        $cache_file = $tmp_dir . DIRECTORY_SEPARATOR . 'WEBWINKELKEUR_'
            . md5(__FILE__) . '_' . md5($url);

        $fp = @fopen($cache_file, 'rb');
        if($fp)
            $stat = @fstat($fp);

        if($fp && $stat && $stat['mtime'] > time() - 7200
            && ($json = stream_get_contents($fp))
        ) {
            $data = json_decode($json, true);
        } else {
            $context = stream_context_create(array(
                'http' => array('timeout' => 3),
            ));
            $json = @file_get_contents($url, false, $context);
            if(!$json) return;

            $data = @json_decode($json, true);
            if(empty($data['result'])) return;

            $new_file = $cache_file . '.' . uniqid();
            if(@file_put_contents($new_file, $json))
                @rename($new_file, $cache_file) or @unlink($new_file);
        }

        if($fp)
            @fclose($fp);

        if($data['result'] == 'ok')
            return $data['content'];
    }

    public function event_view_common_header_after($route, $data, &$output) {
        $output .= $this->consoleLog('WebwinkelKeur: OpenCart module $VERSION$ loaded');
        $output .= $this->getScript();
        $output .= $this->getCronTrigger();
    }

    private function getScript() {
        if (empty($this->settings['javascript'])) {
            return $this->consoleLog("WebwinkelKeur: JavaScript integration is disabled");
        }

        if (empty($this->settings['shop_id'])) {
            return $this->consoleLog("WebwinkelKeur: Shop ID is empty");
        }

        return
            '<script>_webwinkelkeur_id=' . (int) $this->settings['shop_id'] . '</script>' .
            '<script async src="https://' . $this->msg['APP_DOMAIN'] . '/js/sidebar.js"></script>';

        return $o;
    }

    private function consoleLog($message) {
        return sprintf('<script>console.log(%s)</script>', json_encode($message));
    }

    private function getCronTrigger() {
        if (!$this->model_extension_module_webwinkelkeur->shouldRunCron()) {
            return '';
        }

        $url = $this->url->link('extension/module/webwinkelkeur/cron', '', true);

        return sprintf(
            '<script>(function(i){i.src=%s;})(new Image)</script>',
            json_encode(html_entity_decode($url, ENT_QUOTES, 'UTF-8'))
        );
    }

}
