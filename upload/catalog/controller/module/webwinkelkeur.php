<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->load->model('setting/setting');

        $this->load->model('module/webwinkelkeur');

        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        if(!empty($settings['shop_id']) && !empty($settings['sidebar'])) {
            $shop_id = (int) $settings['shop_id'];
            $this->document->addStyle('//www.webwinkelkeur.nl/css/webwinkelkeur_button.css');
            $this->document->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
            $this->document->addScript('//www.webwinkelkeur.nl/fancybox/jquery.fancybox-1.3.4.pack.js');
            $this->document->addScript('//www.webwinkelkeur.nl/js/webwinkel_button.php?id=' . $shop_id);
        }

        if($this->model_module_webwinkelkeur->getOrdersToInvite()) {
            $this->output = '<div style="display:block;position:absolute;top:-9999px;left:-9999px;width:1px;height:1px;background:url(index.php?route=module/webwinkelkeur/cron);"></div>';
        }
    }

    public function cron() {
        $this->load->model('setting/setting');

        $this->load->model('module/webwinkelkeur');

        ignore_user_abort(true);
        
        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');
        
        if(!empty($settings['shop_id']) && !empty($settings['api_key']) && !empty($settings['invite'])) {
            $this->model_module_webwinkelkeur->sendInvites($settings['shop_id'], $settings['api_key'], $settings['invite_delay']);
        }

        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
}
