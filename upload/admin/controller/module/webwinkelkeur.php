<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->language->load('common/header');

        $this->document->setTitle('Webwinkelkeur');

		if($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->editSettings(array(
                'shop_id'       => $this->request->post['shop_id'],
                'api_key'       => $this->request->post['api_key'],
                'invite'        => !!$this->request->post['invite'],
                'invite_delay'  => (int) $this->request->post['invite_delay'],
            ));

			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
        }

  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'ssl'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => 'Webwinkelkeur',
			'href'      => $this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'ssl'),
      		'separator' => ' :: '
   		);

        $this->data['error_warning'] = array();

        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'ssl');

        foreach($this->getSettings() as $key => $value)
            $this->data[$key] = $value;

		$this->template = 'module/webwinkelkeur.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
    }

    public function install() {
        $this->editSettings();

        $this->redirect($this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'SSL'));
    }

    private function getSettings() {
        $this->load->model('setting/setting');

        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        return array_merge(array(
            'shop_id'       => false,
            'api_key'       => false,
            'invite'        => false,
            'invite_delay'  => 7,
        ), $settings);
    }
    
    private function editSettings(array $settings = array()) {
        $this->load->model('setting/setting');
        $this->load->model('design/layout');

        $settings = array_merge($settings, array(
            'webwinkelkeur_module' => array(),
        ));

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
