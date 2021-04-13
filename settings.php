<?php
// This file is part of local_thlib for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings page
 *
 * @package       local_thlib
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

	$settings = new admin_settingpage('local_th_vmc_sms', get_string('pluginname', 'local_th_vmc_sms'));
	$ADMIN->add('localplugins', $settings);

	$configs = array();
//

	$configs[] = new admin_setting_heading('local_th_vmc_sms/headingsms', get_string('setting_sms', 'local_th_vmc_sms'), '');

	$configs[] = new \local_th_vmc_sms\th_custom_admin_setting_configcheckbox('local_th_vmc_sms/smslogin',
		get_string('setting_sms', 'local_th_vmc_sms'),
		get_string('smsdesc', 'local_th_vmc_sms'),
		0);

	$configs[] = new admin_setting_configtext('local_th_vmc_sms/daysms',
		get_string('setting_daysms', 'local_th_vmc_sms'),
		get_string('setting_daysms', 'local_th_vmc_sms'),
		50, PARAM_INT, 5);

//add config
	foreach ($configs as $config) {
		$config->pin = 'local_thlib';
		$settings->add($config);
	}

}
