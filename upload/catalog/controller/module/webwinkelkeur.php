<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->load->model('setting/setting');

        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        if(!empty($settings['shop_id'])) {
            $shop_id = (int) $settings['shop_id'];
            $this->document->addStyle('//www.webwinkelkeur.nl/css/webwinkelkeur_button.css');
            $this->document->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
            $this->document->addScript('//www.webwinkelkeur.nl/fancybox/jquery.fancybox-1.3.4.pack.js');
            $this->document->addScript('//www.webwinkelkeur.nl/js/webwinkel_button.php?id=' . $shop_id);
        }
    }
}
