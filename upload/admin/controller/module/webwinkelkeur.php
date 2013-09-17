<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->language->load('module/webwinkelkeur');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('design/layout');

        if(true) {
            $settings = array(
                'webwinkelkeur_module' => array(),
            );

            // We want to execute our module on every page. This is why we have
            // to add it for every layout manually.
            $layouts = $this->model_design_layout->getLayouts();
            foreach($layouts as $layout) {
                $settings['webwinkelkeur_module'][] = array(
                    'layout_id'     => $layout['layout_id'],
                    'position'      => 'content_bottom',
                    'status'        => 1,
                    'sort_order'    => 0,
                );
            }

            $this->model_setting_setting->editSetting('webwinkelkeur', $settings);
        }
    }
}
