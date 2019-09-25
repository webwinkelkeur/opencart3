<?php
class ControllerExtensionModuleWebwinkelkeur extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);

        $this->load->model('extension/module/webwinkelkeur');
        $this->load->model('localisation/order_status');
        try {
            $this->load->model('setting/module');
        } catch (Exception $e) {
            $this->load->model('extension/module');
            $this->model_setting_module = $this->model_extension_module;
        }
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
    }

    public function index() {
        $this->language->load('common/header');

        $path_module = 'extension/module/webwinkelkeur';

        $path_extensions =
            is_file(DIR_APPLICATION . 'controller/marketplace/extension.php') ?
            'marketplace/extension' : 'extension/extension';

        $this->language->load('extension/module/account');

        $text_extension = $this->language->get('text_extension');

        $msg = @include DIR_SYSTEM . 'library/webwinkelkeur-messages.php';

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
                $this->response->redirect($this->link($path_module, ['module_id' => $module_id]));
            }

            if($this->validateForm()) {
                $form_data = $this->request->post['store'];
                $form_data['store_id'] =
                    isset($this->request->post['store_id'])
                    ? $this->request->post['store_id'] : null;

                $new_settings = $this->cleanSettings($form_data);
                $this->editSettings($new_settings);
                $this->response->redirect($this->link($path_extensions));
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
            'href' => $this->link('common/dashboard'),
        );

   		$data['breadcrumbs'][] = array(
       		'text'      => $text_extension,
			'href'      => $this->link($path_extensions),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
            'text'      => $msg['WEBWINKELKEUR'],
			'href'      => $this->link($path_module),
      		'separator' => ' :: '
   		);

        $data['cancel'] = $this->link($path_extensions);

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['stores'] = $stores;

        $data['view_stores'] = array(array(
            'name'     => $this->config->get('config_name'),
            'settings' => $settings,
        ));

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['invite_errors'] = $this->model_extension_module_webwinkelkeur->getInviteErrors();

        $data['header'] =
            $this->load->controller('common/header') .
            $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['version'] = '$VERSION$';

        return $this->render('extension/module/webwinkelkeur', $data);
    }

    private function render($__template, array $__data) {
        extract($__data);
        require DIR_TEMPLATE . $__template . '.tpl';
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
        $this->model_extension_module_webwinkelkeur->install();

        $this->createModule(array('store_id' => 0));
        $this->editSettings();
    }

    public function uninstall() {
        $this->model_extension_module_webwinkelkeur->uninstall();
    }

    private function getSettings() {
        if(isset($this->request->get['module_id'])) {
            $settings = $this->model_setting_module->getModule($this->request->get['module_id']);
            return $this->defaultSettings($settings);
        }

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
            'invite_first_order_id' => $this->getLastOrderID() + 1,
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
            'invite_first_order_id' => (int) $data['invite_first_order_id'],
            'store_id'         => (int) $data['store_id'],
            'javascript'       => !!$data['javascript'],
            'rich_snippet'     => !!$data['rich_snippet'],
            'order_statuses'   => empty($data['order_statuses']) ? array() : $this->cleanIntegerArray($data['order_statuses']),
            'status'           => !!$data['status'],
        );
    }

    private function getLastOrderID() {
        $result = $this->db->query("SELECT MAX(order_id) v FROM `" . DB_PREFIX . "order`");
        return $result->row ? (int) $result->row['v'] : 0;
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
        if(isset($this->request->get['module_id'])) {
            $modules = $this->model_setting_module->getModulesByCode('webwinkelkeur');
            foreach($modules as $module) {
                if($module['module_id'] === $this->request->get['module_id'])
                    $settings['name'] = $module['name'];
            }
            $this->model_setting_module->editModule($this->request->get['module_id'], $settings);
            return;
        }

        $wwk_settings = array();
        foreach($settings as $key => $value) {
            $wwk_settings["webwinkelkeur_${key}"] = $value;
        }

        $this->model_setting_setting->editSetting('webwinkelkeur', $wwk_settings);
    }

    private function findModule($store) {
        foreach($this->model_setting_module->getModulesByCode('webwinkelkeur') as $module) {
            $data = $this->model_setting_module->getModule($module['module_id']);
            if($data['store_id'] == $store)
                return $module['module_id'];
        }
        return null;
    }

    private function createModule($settings) {
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

    private function link($action, array $params = []) {
        $params += array_intersect_key($this->request->get, array_flip(['token', 'user_token']));
        return $this->url->link($action, http_build_query($params), true);
    }

}
