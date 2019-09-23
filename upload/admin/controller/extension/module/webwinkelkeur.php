<?php
class ControllerExtensionModuleWebwinkelkeur extends Controller {
    private $error = array();

    public function index() {
        $this->language->load('common/header');

        $this->load->model('setting/setting');

        $path_module = 'extension/module/webwinkelkeur';
        $path_extensions = 'marketplace/extension';

        $this->language->load('extension/module/account');

        $text_extension = $this->language->get('text_extension');

        $msg = @include DIR_SYSTEM . 'library/webwinkelkeur-messages.php';

        $this->load->model('extension/module/webwinkelkeur');

        $this->model_extension_module_webwinkelkeur->installEvents();

        $this->document->setTitle($msg['WEBWINKELKEUR']);

        $data = array();

        $data['msg'] = $msg;

        $data['error_warning'] = array();

        $settings = $this->getSettings();

        $stores = $this->model_extension_module_webwinkelkeur->getStores();

		if($this->request->server['REQUEST_METHOD'] == 'POST') {
            if(!empty($this->request->post['selectStore'])) {
                $module_id = $this->findModule($this->request->post['store_id']);

                if(is_null($module_id)) {
                    $this->createModule($this->request->post);
                    $module_id = $this->findModule($this->request->post['store_id']);
                }
                $this->response->redirect($this->url->link($path_module,
                        'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module_id, 'SSL'));
            }

            if($this->validateForm()) {
                $form_data = $this->request->post['store'];
                $form_data['store_id'] =
                    isset($this->request->post['store_id'])
                    ? $this->request->post['store_id'] : null;

                $new_settings = $this->cleanSettings($form_data);
                $this->editSettings($new_settings);
                $this->response->redirect($this->url->link($path_extensions, 'user_token=' . $this->session->data['user_token'], 'SSL'));
            }
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'ssl'),
        );

   		$data['breadcrumbs'][] = array(
       		'text'      => $text_extension,
			'href'      => $this->url->link($path_extensions, 'user_token=' . $this->session->data['user_token'], 'ssl'),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
            'text'      => $msg['WEBWINKELKEUR'],
			'href'      => $this->url->link($path_module, 'user_token=' . $this->session->data['user_token'], 'ssl'),
      		'separator' => ' :: '
   		);

        $data['cancel'] = $this->url->link($path_extensions, 'user_token=' . $this->session->data['user_token'], 'ssl');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['stores'] = $stores;

        $data['view_stores'] = array(array(
            'name'     => $this->config->get('config_name'),
            'settings' => $settings,
        ));

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['invite_errors'] = $this->model_extension_module_webwinkelkeur->getInviteErrors();

        $data['header'] = $this->load->controller('common/header') . $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['version'] = '$VERSION$';

        $this->response->setOutput($this->load->view('extension/module/webwinkelkeur', $data));
    }

    private function validateForm() {
        return $this->validateSettings($this->request->post['store']);
    }

    private function validateSettings(array &$data) {
        $msg = @include DIR_SYSTEM . 'library/webwinkelkeur-messages.php';

        $data['shop_id'] = trim($data['shop_id']);
        $data['api_key'] = trim($data['api_key']);

        if(!empty($data['shop_id']) && !ctype_digit($data['shop_id']))
            $this->error['shopid'] = $msg['SHOP_ID_INVALID'];

        if($data['invite'] && !$data['api_key'])
            $this->error['apikey'] = $msg['API_KEY_MISSING'];

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/module/webwinkelkeur');

        $this->model_extension_module_webwinkelkeur->install();

        $this->createModule(array('store_id' => 0));
        $this->editSettings();
    }

    public function uninstall() {
        $this->load->model('extension/module/webwinkelkeur');

        $this->model_extension_module_webwinkelkeur->uninstall();
    }

    private function getSettings() {
        $this->load->model('setting/module');
        if(isset($this->request->get['module_id'])) {
            $settings = $this->model_setting_module->getModule($this->request->get['module_id']);
            return $this->defaultSettings($settings);
        }

        $this->load->model('setting/setting');
        $wwk_settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        $settings = array();
        foreach($wwk_settings as $key => $value) {
            preg_match('~^webwinkelkeur_(.*)$~', $key, $name);
            $settings[$name[1]] = $value;
        }

        return $this->defaultSettings($settings);
    }

    private function defaultSettings($data = array()) {
        if(!is_array($data)) $data = array();
        return array_merge(array(
            'shop_id'          => false,
            'api_key'          => false,
            'store_id'         => 0,
            'invite'           => 0,
            'limit_order_data' => false,
            'invite_delay'     => 7,
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
            'invite'           => (int) $data['invite'],
            'limit_order_data' => !!$data['limit_order_data'],
            'invite_delay'     => (int) $data['invite_delay'],
            'store_id'         => (int) $data['store_id'],
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

    private function editSettings(array $settings = array()) {
        $this->load->model('setting/module');

        if(isset($this->request->get['module_id'])) {
            $modules = $this->model_setting_module->getModulesByCode('webwinkelkeur');
            foreach($modules as $module) {
                if($module['module_id'] === $this->request->get['module_id'])
                    $settings['name'] = $module['name'];
            }
            $this->model_setting_module->editModule($this->request->get['module_id'], $settings);
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
        $this->load->model('setting/module');

        foreach($this->model_setting_module->getModulesByCode('webwinkelkeur') as $module) {
            $data = $this->model_setting_module->getModule($module['module_id']);
            if($data['store_id'] == $store)
                return $module['module_id'];
        }
        return null;
    }

    private function createModule($settings) {
        $this->load->model('setting/module');
        $this->load->model('setting/store');

        $stores = $this->model_setting_store->getStores();
        foreach($stores as $store) {
            if($store['store_id'] == $settings['store_id'])
                $name = $store['name'];
        }
        if (!isset ($name)) {
            $name = $this->config->get('config_name');
        }

        $data = $this->defaultSettings();
        $module_settings = array_merge($data, array(
            'name'      => $name,
            'store_id'  => $settings['store_id'],
        ));

        $this->model_setting_module->addModule('webwinkelkeur', $module_settings);
    }
}
