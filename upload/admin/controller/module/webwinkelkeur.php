<?php
class ControllerModuleWebwinkelkeur extends Controller {
    private $error = array();

    public function index() {
        $this->language->load('common/header');

        $this->language->load('module/account');

        $this->load->model('module/webwinkelkeur');

        $this->document->setTitle('WebwinkelKeur');

        $data = array();

        $data['error_warning'] = array();

        $settings = $this->getSettings();

        $stores = $this->model_module_webwinkelkeur->getStores();

		if($this->request->server['REQUEST_METHOD'] == 'POST') {
            if($this->request->post['selectStore']) {
                $module_id = $this->findModule($this->request->post['store_id']);

                if(is_null($module_id)) {
                    $this->createModule($this->request->post);
                    $module_id = $this->findModule($this->request->post['store_id']);
                }
                $this->response->redirect($this->url->link('module/webwinkelkeur',
                        'token=' . $this->session->data['token'] . '&module_id=' . $module_id, 'SSL'));
            }

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
                $this->response->redirect($this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'SSL'));
            else
                $this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
        }

        if(isset($this->error['shopid']))
            $data['error_shopid'] = $this->error['shopid'];
        else
            $data['error_shopid'] = '';

        if(isset($this->error['apikey']))
            $data['error_apikey'] = $this->error['apikey'];
        else
            $data['error_apikey'] = '';

  		$data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'ssl'),
        );

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'ssl'),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => 'WebwinkelKeur',
			'href'      => $this->url->link('module/webwinkelkeur', 'token=' . $this->session->data['token'], 'ssl'),
      		'separator' => ' :: '
   		);

        $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'ssl');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['stores'] = $stores;

        $data['view_stores'] = array(array(
            'store_id' => 0,
            'name'     => $this->config->get('config_name'),
        ));

        if($settings['multistore'])
            $data['view_stores'] = array_merge($data['view_stores'], $data['stores']);

        foreach($data['view_stores'] as &$store) {
            if($store['store_id'] && isset($settings['store'][$store['store_id']]))
                $store['settings'] = $settings['store'][$store['store_id']];
            elseif($store['store_id'])
                $store['settings'] = $this->defaultSettings();
            else
                $store['settings'] = array_merge($settings, $this->request->post);
            $store['field_name'] = $store['store_id'] ? "store[{$store['store_id']}][%s]" : "%s";
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['multistore'] = $settings['multistore'];

        $data['invite_errors'] = $this->model_module_webwinkelkeur->getInviteErrors();

        $data['header'] = $this->load->controller('common/header') . $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('module/webwinkelkeur.tpl', $data));
    }

    private function validateForm() {
        if(!isset($this->request->post['multistore']))
            $this->request->post['multistore'] = false;

        if($this->request->post['multistore'])
            $default = $this->config->get('config_name') . ': ';
        else
            $default = '';

        if($this->request->post['multistore'] && !empty($this->request->post['store']))
            foreach($this->request->post['store'] as $store)
                foreach($this->validateSettings($store) as $error)
                    $this->data['error_warning'][] = $store['store_name'] . ': ' . $error;

        return $this->validateSettings($this->request->post);
    }

    private function validateSettings(array &$data) {
        $data['shop_id'] = trim($data['shop_id']);
        $data['api_key'] = trim($data['api_key']);

        if(!empty($data['shop_id']) && !ctype_digit($data['shop_id']))
            $this->error['shopid'] = 'Uw webwinkel ID mag alleen cijfers bevatten.';

        if($data['invite'] && !$data['api_key'])
            $this->error['apikey'] = 'Vul uw API key in.';

        return !$this->error;
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
        $this->load->model('extension/module');
        if(isset($this->request->get['module_id'])) {
            $settings = array();
            $settings = $this->model_extension_module->getModule($this->request->get['module_id']);
            return $this->defaultSettings($settings);
        }

        $this->load->model('setting/setting');
        $wwk_settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        $settings = array();
        foreach($wwk_settings as $key => $value) {
            preg_match('~^webwinkelkeur_(.*)$~', $key, $name);
            $settings[$name[1]] = $value;
        }

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
            'status'           => true,
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
            'store_id'         => (int) $data['store_id'],
            'tooltip'          => !!$data['tooltip'],
            'javascript'       => !!$data['javascript'],
            'rich_snippet'     => !!$data['rich_snippet'],
            'order_statuses'   => empty($data['order_statuses']) ? array() : $this->cleanIntegerArray($data['order_statuses']),
            'status'           => !!$data['status'],
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

    private function editLayouts() {
        $this->load->model('design/layout');

        $layouts = $this->model_design_layout->getLayouts();
        foreach($layouts as $layout) {
            $found = false;
            $layout_module = $this->model_design_layout->getLayoutModules($layout['layout_id']);
            foreach($layout_module as $module) {
                if($module['code'] == 'webwinkelkeur')
                    $found = true;
            }
            if($found) continue;

            $layout_module[] = array(
                'code'       => 'webwinkelkeur',
                'position'   => 'content_bottom',
                'sort_order' => 0,
            );

            $new_layout = array(
                'name'           => $layout['name'],
                'layout_route'   => $this->model_design_layout->getLayoutRoutes($layout['layout_id']),
                'layout_module'  => $layout_module,
            );

            $this->model_design_layout->editLayout($layout['layout_id'], $new_layout);
        }
    }

    private function editSettings(array $settings = array()) {
        // We want to execute our module on every page. This is why we have
        // to add it for every layout manually.
        $this->editLayouts();

        $this->load->model('extension/module');
        if(isset($this->request->get['module_id'])) {
            $this->model_extension_module->editModule($this->request->get['module_id'], $settings);
            return;
        }

        $this->load->model('setting/setting');

        $wwk_settings = array();
        foreach($settings as $key => $value) {
            $wwk_settings["webwinkelkeur_${key}"] = $value;
        }

        $this->model_setting_setting->editSetting('webwinkelkeur', $wwk_settings);
    }

    private function findModule($store) {
        if($store === 0) return 0;
        $this->load->model('extension/module');

        foreach($this->model_extension_module->getModulesByCode('webwinkelkeur') as $module) {
            $data = $this->model_extension_module->getModule($module['module_id']);
            if($data['store_id'] == $store)
                return $module['module_id'];
        }
        return null;
    }

    private function createModule($settings) {
        $this->load->model('extension/module');

        $data = $this->defaultSettings();
        $module_settings = array_merge($data, array(
            'store_id'  => $settings['store_id'],
        ));

        $this->model_extension_module->addModule('webwinkelkeur', $module_settings);
    }
}
