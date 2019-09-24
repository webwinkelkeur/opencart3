<?php
class ModelExtensionModuleWebwinkelkeur extends Model {

    private $eventTriggers = [
        'catalog/view/common/header/after' => 'event_view_common_header_after',
        'catalog/view/*/template/common/header/after' => 'event_view_common_header_after',
    ];

    public function install() {
        if(!in_array('webwinkelkeur_invite_sent', $this->getColumnNames('order'))) {
            $this->db->query("
                ALTER TABLE `" . DB_PREFIX . "order`
                ADD COLUMN webwinkelkeur_invite_sent BOOLEAN NOT NULL DEFAULT 0
            ");
        }

        if(!in_array('webwinkelkeur_invite_tries', $this->getColumnNames('order'))) {
            $this->db->query("
                ALTER TABLE `" . DB_PREFIX . "order`
                ADD COLUMN webwinkelkeur_invite_tries INT NOT NULL DEFAULT 0
            ");
        }

        if(!in_array('webwinkelkeur_invite_time', $this->getColumnNames('order'))) {
            $this->db->query("
                ALTER TABLE `" . DB_PREFIX . "order`
                ADD COLUMN webwinkelkeur_invite_time INT NOT NULL DEFAULT 0
            ");
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET webwinkelkeur_invite_sent = 1");

        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "webwinkelkeur_invite_error`");

        $this->db->query("
            CREATE TABLE `" . DB_PREFIX . "webwinkelkeur_invite_error` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(255) NOT NULL,
                response TEXT NOT NULL,
                time BIGINT NOT NULL,
                INDEX time (time)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ");

        $this->installEvents();
    }

    public function installEvents() {
        foreach ($this->eventTriggers as $trigger => $method) {
            $this->installEvent($trigger, $method);
        }
    }

    private function installEvent($trigger, $method) {
        $code = $this->getEventCode($trigger);
        $action = 'extension/module/webwinkelkeur/' . $method;

        if ($this->db->query("
            SELECT 1
            FROM `" . DB_PREFIX . "event`
            WHERE
                `trigger` = '" . $this->db->escape($trigger) . "'
                AND `action` = '" . $this->db->escape($action) . "'
        ")->row) {
            return;
        }

        $this->getEventModel()->addEvent($code, $trigger, $action);
    }

    private function getEventCode($trigger) {
        return md5('webwinkelkeur/' . $trigger);
    }

    public function uninstall() {
        if(in_array('webwinkelkeur_invite_sent', $this->getColumnNames('order'))) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` DROP COLUMN webwinkelkeur_invite_sent");
        }

        if(in_array('webwinkelkeur_invite_tries', $this->getColumnNames('order'))) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` DROP COLUMN webwinkelkeur_invite_tries");
        }

        if(in_array('webwinkelkeur_invite_time', $this->getColumnNames('order'))) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` DROP COLUMN webwinkelkeur_invite_time");
        }

        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "webwinkelkeur_invite_error`");

        $this->uninstallEvents();
    }

    private function getColumnNames($table) {
        $query = $this->db->query("DESCRIBE `" . DB_PREFIX . $table . "`");

        $column_names = array();

        foreach($query->rows as $column)
            $column_names[] = $column['Field'];

        return $column_names;
    }

    private function uninstallEvents() {
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "event`
            WHERE `action` LIKE 'extension/module/webwinkelkeur/%'
        ");
    }

    private function getEventModel() {
        try {
            $this->load->model('setting/event');
            return $this->model_setting_event;
        } catch (Exception $e) {}

        try {
            $this->load->model('extension/event');
            return $this->model_extension_event;
        } catch (Exception $e) {}

        throw new RuntimeException("Failed to load either setting/event or extension/event model");
    }

    public function getInviteErrors() {
        $until = time() - 86400 * 3;

        $query = $this->db->query("
            SELECT *
            FROM `" . DB_PREFIX . "webwinkelkeur_invite_error`
            WHERE time > $until
            ORDER BY time
        ");

        return array_map(function ($row) {
            try {
                $row['time'] = new DateTimeImmutable('@' . $row['time']);
            } catch (Exception $e) {
                $row['time'] = new DateTimeImmutable('@0');
            }

            $response = json_decode($row['response'], true);
            $row['message'] = empty($response['message']) ? $row['response'] : $response['message'];

            return $row;
        }, $query->rows);
    }

    public function getStores() {
        $query = $this->db->query("
            SELECT
                store_id, value as name
            FROM
                `". DB_PREFIX . "setting`
            WHERE `key` = 'config_name'
        ");
        return $query->rows;
    }

}
