<?php
require_once DIR_SYSTEM . 'library/Peschar_URLRetriever.php';
class ModelModuleWebwinkelkeur extends Model {
    private function getOrdersToInvite($settings) {
        $max_time = time() - 1800;

        $where = array();

        if($this->getMultistore())
            $where[] = 'o.store_id = ' . (int) $this->config->get('config_store_id');

        if(empty($settings['order_statuses']))
            $where[] = '0';
        else
            $where[] = 'o.order_status_id IN (' . implode(',', array_map('intval', $settings['order_statuses'])) . ')';

        if(empty($where))
            $where = '0';
        else
            $where = implode(' AND ', $where);

        $query = $this->db->query($q="
            SELECT o.*, l.code as language_code
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "language` l USING(language_id)
            WHERE
                o.webwinkelkeur_invite_sent = 0
                AND o.webwinkelkeur_invite_tries < 10
                AND o.webwinkelkeur_invite_time < $max_time
                AND $where
        ");

        return $query->rows;
    }

    public function sendInvites() {
        $msg = @include DIR_SYSTEM . 'library/webwinkelkeur-messages.php';

        $settings = $this->getSettings();

        if(empty($settings['shop_id']) ||
           empty($settings['api_key']) ||
           empty($settings['invite'])
        )
            return;

        foreach($this->getOrdersToInvite($settings) as $order) {
            $this->db->query("
                UPDATE `" . DB_PREFIX . "order`
                SET
                    webwinkelkeur_invite_tries = webwinkelkeur_invite_tries + 1,
                    webwinkelkeur_invite_time = " . time() . "
                WHERE
                    order_id = " . $order['order_id'] . "
                    AND webwinkelkeur_invite_tries = " . $order['webwinkelkeur_invite_tries'] . "
                    AND webwinkelkeur_invite_time = " . $order['webwinkelkeur_invite_time'] . "
            ");
            if($this->db->countAffected()) {
                $parameters = array(
                    'id'        => $settings['shop_id'],
                    'password'  => $settings['api_key'],
                    'email'     => $order['email'],
                    'order'     => $order['order_id'],
                    'delay'     => $settings['invite_delay'],
                    'lang'      => str_replace('-', '_', $order['language_code']),
                    'customername' => "$order[payment_firstname] $order[payment_lastname]",
                    'client'    => 'opencart2',
                );
                if($settings['invite'] == 2)
                    $parameters['noremail'] = '1';
                $url = 'http://' . $msg['APP_DOMAIN'] . '/api.php?' . http_build_query($parameters);
                $retriever = new Peschar_URLRetriever();
                $response = $retriever->retrieve($url);
                if(preg_match('|^Success:|', $response) || preg_match('|invite already sent|', $response)) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "order` SET webwinkelkeur_invite_sent = 1 WHERE order_id = " . $order['order_id']);
                } else {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "webwinkelkeur_invite_error` SET url = '" . $this->db->escape($url) . "', response = '" . $this->db->escape($response) . "', time = " . time());
                }
            }
        }
    }

    public function getSettings() {
        $this->load->model('setting/setting');

        $store_id = $this->config->get('config_store_id');

        $this->load->model('extension/module');
        foreach($this->getModulesByCode('webwinkelkeur') as $module) {
            $data = $this->model_extension_module->getModule($module['module_id']);
            if($data['store_id'] == $store_id)
                return $data;
        }

        $wwk_settings = $this->model_setting_setting->getSetting('webwinkelkeur');

        $settings = array();
        foreach($wwk_settings as $key => $value) {
            preg_match('~^webwinkelkeur_(.*)$~', $key, $name);
            $settings[$name[1]] = $value;
        }

        return $settings;
    }

    public function getMultistore() {
        $this->load->model('setting/setting');

        $settings = $this->getSettings('webwinkelkeur');

        return !empty($settings['multistore']);
    }

    public function getModulesByCode($code) {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "module`" .
                " WHERE `code` = '" . $this->db->escape($code) . "'" .
                " ORDER BY `name`");

        return $query->rows;
    }
}
