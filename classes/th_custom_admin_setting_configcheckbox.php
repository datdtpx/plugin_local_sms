<?php

namespace local_th_vmc_sms;

class th_custom_admin_setting_configcheckbox extends \admin_setting_configcheckbox {
	public function write_setting($data) {

		$name = $this->name;
		$value = $data;

		$task = null;
		$classname = null;
		switch ($name) {
		case 'smslogin':
			$task = new \local_th_vmc_sms\task\sms_demo();
			$classname = '\local_th_vmc_sms\task\sms_demo';
			break;
		default:
			$task = null;
			$classname = null;
		}

		if ($classname != null) {
			$task = \core\task\manager::get_default_scheduled_task($classname, false);
			$task->set_disabled(!$value);
			$result = \core\task\manager::configure_scheduled_task($task);
		}

		return parent::write_setting($data);
	}
}