<?php
class ControllerModuleWebwinkelkeur extends Controller {
    public function index() {
        $this->language->load('common/header');

        $this->load->model('module/webwinkelkeur');

        $this->document->setTitle('WebwinkelKeur');

        $this->data['error_warning'] = array();

        $settings = $this->getSettings();

        $stores = $this->model_module_webwinkelkeur->getStores();

		if($this->request->server['REQUEST_METHOD'] == 'POST') {
            $validated = $this->validateForm();

            $new_settings = $this->cleanSettings($this->request->post);
            $new_settings['multistore'] = !!$this->request->post['multistore'];

            if($new_settings['multistore'])
                foreach($stores as $store)
                    $new_settings['store'][$store['store_id']] = isset($this->request->post['store'][$store['store_id']]) ? $this->cleanSettings($this->request->post['store'][$store['store_id']]) : $this->defaultSettings();

            $this->editSettings($new_settings);

            if(!$validated)
                $settings = $this->getSettings();
            elseif($new_settings['multistore'] != $settings['multistore'])
                $this->redirect($this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'SSL'));
            else
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

        $this->data['stores'] = $stores;

        $this->data['view_stores'] = array(array(
            'store_id' => 0,
            'name'     => $this->config->get('config_name'),
        ));

        if($settings['multistore'])
            $this->data['view_stores'] = array_merge($this->data['view_stores'], $this->data['stores']);

        foreach($this->data['view_stores'] as &$store) {
            if($store['store_id'] && isset($settings['store'][$store['store_id']]))
                $store['settings'] = $settings['store'][$store['store_id']];
            elseif($store['store_id'])
                $store['settings'] = $this->defaultSettings();
            else
                $store['settings'] = array_merge($settings, $this->request->post);
            $store['field_name'] = $store['store_id'] ? "store[{$store['store_id']}][%s]" : "%s";
        }

        $this->load->model('localisation/order_status');

        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->data['multistore'] = $settings['multistore'];

        $this->data['invite_errors'] = $this->model_module_webwinkelkeur->getInviteErrors();

		$this->template = 'module/webwinkelkeur.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
    }

    private function validateForm() {
        if(!isset($this->request->post['multistore']))
            $this->request->post['multistore'] = false;

        if($this->request->post['multistore'])
            $default = $this->config->get('config_name') . ': ';
        else
            $default = '';

        foreach($this->validateSettings($this->request->post) as $error)
            $this->data['error_warning'][] = $default . $error;

        if($this->request->post['multistore'] && !empty($this->request->post['store']))
            foreach($this->request->post['store'] as $store)
                foreach($this->validateSettings($store) as $error)
                    $this->data['error_warning'][] = $store['store_name'] . ': ' . $error;

        return empty($this->data['error_warning']);
    }

    private function validateSettings(array &$data) {
        $data['shop_id'] = trim($data['shop_id']);
        $data['api_key'] = trim($data['api_key']);

        $errors = array();

        if(!empty($data['shop_id']) && !ctype_digit($data['shop_id']))
            $errors[] = 'Uw webwinkel ID mag alleen cijfers bevatten.';

        if($data['invite'] && !$data['api_key'])
            $errors[] = 'Vul uw API key in.';

        return $errors;
    }

    public function install() {
        $this->load->model('module/webwinkelkeur');

        $this->model_module_webwinkelkeur->install();

        $this->editSettings();
    }

    public function uninstall() {
        $this->load->model('module/webwinkelkeur');

        $this->model_module_webwinkelkeur->uninstall();
    }

    private function getSettings() {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('webwinkelkeur');
        return array_merge(
            array('multistore' => false),
            $this->defaultSettings($settings)
        );
    }

    private function defaultSettings($data = array()) {
        if(!is_array($data)) $data = array();
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
            'rich_snippet'     => false,
            'order_statuses'   => array(3, 5),
        ), $data);
    }

    private function cleanSettings($data) {
        if(!is_array($data)) $data = array();
        $data = array_merge(array('order_statuses' => array()), $data);
        $data = $this->defaultSettings($data);
        return array(
            'shop_id'          => trim($data['shop_id']),
            'api_key'          => trim($data['api_key']),
            'sidebar'          => !!$data['sidebar'],
            'sidebar_position' => $data['sidebar_position'],
            'sidebar_top'      => $data['sidebar_top'],
            'invite'           => (int) $data['invite'],
            'invite_delay'     => (int) $data['invite_delay'],
            'tooltip'          => !!$data['tooltip'],
            'javascript'       => !!$data['javascript'],
            'rich_snippet'     => !!$data['rich_snippet'],
            'order_statuses'   => empty($data['order_statuses']) ? array() : $this->cleanIntegerArray($data['order_statuses']),
        );
    }

    private function cleanIntegerArray($array) {
        if(!is_array($array))
            return array();
        $new = array();
        foreach($array as $value)
            if((is_string($value) && ctype_digit($value))
               || is_integer($value) || is_float($value)
            )
                $new[] = (int) $value;
        return $new;
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
