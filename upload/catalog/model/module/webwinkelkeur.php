<?php
class ModelModuleWebwinkelkeur extends Model {
    public function getOrdersToInvite() {
        $max_time = time() - 1800;

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE webwinkelkeur_invite_sent = 0 AND webwinkelkeur_invite_tries < 10 AND webwinkelkeur_invite_time < $max_time AND order_status_id IN (3, 5)");

        return $query->rows;
    }

    public function sendInvites($shop_id, $api_key, $delay) {
        foreach($this->getOrdersToInvite() as $order) {
            $parameters = array(
                'id'        => $shop_id,
                'password'  => $api_key,
                'email'     => $order['email'],
                'order'     => $order['order_id'],
                'delay'     => $delay,
            );
            $url = 'http://www.webwinkelkeur.nl/api.php?' . http_build_query($parameters);
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET webwinkelkeur_invite_tries = webwinkelkeur_invite_tries + 1, webwinkelkeur_invite_time = " . time() . " WHERE order_id = " . $order['order_id'] . " AND webwinkelkeur_invite_tries = " . $order['webwinkelkeur_invite_tries']);
            $response = @file_get_contents($url);
            if(preg_match('|^Success:|', $response) || preg_match('|invite already sent|', $response)) {
                $this->db->query("UPDATE `" . DB_PREFIX . "order` SET webwinkelkeur_invite_sent = 1 WHERE order_id = " . $order['order_id']);
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "webwinkelkeur_invite_error` SET url = '" . $this->db->escape($url) . "', response = '" . $this->db->escape($response) . "', time = " . time());
            }
        }
    }
}
