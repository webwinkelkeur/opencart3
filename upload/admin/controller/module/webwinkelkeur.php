<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->language->load('common/header');

        $this->load->model('module/webwinkelkeur');

        $this->document->setTitle('WebwinkelKeur');

        $this->data['error_warning'] = array();

		if($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
            $this->editSettings(array(
                'shop_id'          => trim($this->request->post['shop_id']),
                'api_key'          => trim($this->request->post['api_key']),
                'sidebar'          => !!$this->request->post['sidebar'],
                'sidebar_position' => $this->request->post['sidebar_position'],
                'sidebar_top'      => $this->request->post['sidebar_top'],
                'invite'           => (int) $this->request->post['invite'],
                'invite_delay'     => (int) $this->request->post['invite_delay'],
                'tooltip'          => !!$this->request->post['tooltip'],
                'javascript'       => !!$this->request->post['javascript'],
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
       		'text'      => 'WebwinkelKeur',
			'href'      => $this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'ssl'),
      		'separator' => ' :: '
   		);

        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'ssl');

        foreach($this->getSettings() as $key => $value)
            $this->data[$key] = $value;

        foreach(array(
            'shop_id',
            'api_key',
            'sidebar',
            'invite',
            'invite_delay',
        ) as $field)
            if(isset($this->request->post[$field]))
                $this->data[$field] = $this->request->post[$field];

        $this->data['invite_errors'] = $this->model_module_webwinkelkeur->getInviteErrors();

		$this->template = 'module/webwinkelkeur.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
    }

    private function validateForm() {
        $this->request->post['shop_id'] = trim($this->request->post['shop_id']);

        if(!$this->request->post['shop_id'])
            $this->data['error_warning'][] = 'Vul uw webwinkel ID in.';
        elseif(!ctype_digit($this->request->post['shop_id']))
            $this->data['error_warning'][] = 'Uw webwinkel ID mag alleen cijfers bevatten.';

        if($this->request->post['invite'] && !$this->request->post['api_key'])
            $this->data['error_warning'][] = 'Vul uw API key in.';

        return empty($this->data['error_warning']);
    }

    public function install() {
        $this->load->model('module/webwinkelkeur');

        $this->model_module_webwinkelkeur->install();

        $this->editSettings();

        $this->redirect($this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'SSL'));
    }

    public function uninstall() {
        $this->load->model('module/webwinkelkeur');

        $this->model_module_webwinkelkeur->uninstall();
    }

    private function getSettings() {
        $this->load->model('setting/setting');

        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        return array_merge(array(
            'shop_id'          => false,
            'api_key'          => false,
            'sidebar'          => true,
            'sidebar_position' => 'left',
            'sidebar_top'      => '',
            'invite'           => 0,
            'invite_delay'     => 7,
            'tooltip'          => true,
            'javascript'       => true,
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
