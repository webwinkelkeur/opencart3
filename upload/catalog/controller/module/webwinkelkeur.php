<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->load->model('setting/setting');

        $this->load->model('module/webwinkelkeur');

        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        if(!empty($settings['shop_id']) &&
           (!empty($settings['sidebar']) || !empty($settings['tooltip'])
            || !empty($settings['javascript']))
        ) {
            $js_settings = array(
                '_webwinkelkeur_id' => (int) $settings['shop_id'],
                '_webwinkelkeur_sidebar' => !empty($settings['sidebar']),
                '_webwinkelkeur_tooltip' => !empty($settings['tooltip']),
            );
            if(!empty($settings['sidebar_position'])) {
                $js_settings['_webwinkelkeur_sidebar_position'] = $settings['sidebar_position'];
            }
            if(!empty($settings['sidebar_top'])) {
                $js_settings['_webwinkelkeur_sidebar_top'] = $settings['sidebar_top'];
            }
            $this->data['settings'] = $js_settings;

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/webwinkelkeur.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/module/webwinkelkeur.tpl';
            } else {
                $this->template = 'default/template/module/webwinkelkeur.tpl';
            }

            $this->render();
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
    }
}
