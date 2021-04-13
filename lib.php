<?php
require_once $CFG->dirroot . '/config.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->dirroot . '/user/renderer.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->dirroot . '/grade/report/grader/lib.php';
require_once $CFG->dirroot . '/local/thlib/lib.php';

class th_vmc_sms {
	const ONE_DAY = 24 * 60 * 60;
}

//done
function sms_login() {
	print_object("sms_login");
	global $CFG, $DB;

	$now = time();
	$config = get_config("local_th_vmc_sms");
	$daysend = (int) $config->daysmslogin;
	print_object("daysend: " . $daysend);

	$params = array('daythreshold1' => th_vmc_sms::ONE_DAY * ($daysend - 1), 'daythreshold2' => th_vmc_sms::ONE_DAY * $daysend);

	foreach ($params as $key => $daythres) {
		print_object(get_datetime($now - $daythres));
	}

	$sql = "SELECT *,$now-timecreated,$now-timecreated
			from {user}
			where ($now-timecreated)>:daythreshold1 and  ($now-timecreated)<:daythreshold2 and deleted = 0 and lastaccess = 0";

	$records = $DB->get_records_sql($sql, $params);
	print_object("records");
	print_object($records);
	$supportuser = core_user::get_support_user();
	print_object("supportuser");
	print_object($supportuser);

	foreach ($records as $key => $user) {
		print_object($user);
		$subject = get_string('subject_smslogin', 'local_th_vmc_sms');
		$message = get_string('email_smslogin', 'local_th_vmc_sms', ['userfullname' => fullname($user)]);

		print_object($message);

		$record = new \stdClass();
		$record->component = 'local_th_vmc_sms';
		$record->classname = '\local_th_vmc_sms\task\sms_login';
		$record->lastruntime = time();
		// $record->courseid = $courseid;
		$record->customdata = json_encode(array('userid' => (int) ($user->id)));

		$params = [$record->classname, $record->component, $record->customdata];
		$sql = 'classname = ? AND component = ? AND ' .
		$DB->sql_compare_text('customdata', \core_text::strlen($record->customdata) + 1) . ' = ?';

		$smstask = $DB->get_record_select('local_th_vmc_sms', $sql, $params);
		if ($smstask == "" || $smstask == null) {
			$send_ok = email_to_user($user, $supportuser, $subject, $message);
			if ($send_ok == true) {
				$DB->insert_record('local_th_vmc_sms', $record);
			}
		} else {
			print_object("Email already sended");
		}
	}
}

//done
function sms_learning() {
	print_object("sms_learning");

	global $CFG, $DB;

	$now = time();
	$config = get_config("local_th_vmc_sms");
	$daysend = (int) $config->daysmslearning;
	print_object("daysend: " . $daysend);

	$params = array('daythreshold1' => th_vmc_sms::ONE_DAY * ($daysend - 1), 'daythreshold2' => th_vmc_sms::ONE_DAY * $daysend);

	foreach ($params as $key => $daythres) {
		print_object(get_datetime($now - $daythres));
	}

	$sql = "SELECT ue.id,courseid,ra.userid,ue.timecreated
			from {user_enrolments} ue
			join {enrol} e
			on e.id = ue.enrolid and ($now  - ue.timecreated)>:daythreshold1 and ($now  - ue.timecreated)<=:daythreshold2
			join {role_assignments} ra
			on ue.userid = ra.userid and ra.roleid = 5
			join {context} c
			on c.contextlevel = 50 and c.id = ra.contextid and e.courseid = c.instanceid";

	$records = $DB->get_records_sql($sql, $params);
	print_object($records);
	$supportuser = core_user::get_support_user();
	foreach ($records as $key => $record) {

		$studentid = $record->userid;
		$courseid = $record->courseid;

		$courserecord = $DB->get_record('course', array('id' => $courseid));
		$coursefullname = $courserecord->fullname;
		$student = $DB->get_record('user', array('id' => $studentid));
		print_object(fullname($student));

		$subject = get_string('subject_smslearning', 'local_th_vmc_sms');
		$message = get_string('email_smslearning', 'local_th_vmc_sms', array('userfullname' => fullname($student), 'coursefullname' =>
			$coursefullname));

		$record = new \stdClass();
		$record->component = 'local_th_vmc_sms';
		$record->classname = '\local_th_vmc_sms\task\sms_learning';
		$record->lastruntime = time();
		$record->courseid = $courseid;
		$record->customdata = json_encode(array('studentid' => (int) ($student->id)));

		$params = [$record->classname, $record->component, $record->courseid, $record->customdata];
		$sql = 'classname = ? AND component = ? AND courseid = ? AND ' .
		$DB->sql_compare_text('customdata', \core_text::strlen($record->customdata) + 1) . ' = ?';

		$smstask = $DB->get_record_select('local_th_vmc_sms', $sql, $params);
		if ($smstask == "" || $smstask == null) {
			print_object("Email sending");
			$send_ok = email_to_user($student, $supportuser, $subject, $message);
			if ($send_ok) {
				$DB->insert_record('local_th_vmc_sms', $record);
			}
		} else {
			print_object("Email already sended");
		}
	}

}

//done
function sms_accountduedate() {
	print_object("sms_accountduedate");

	global $CFG, $DB;

	$now = time();
	$config = get_config("local_th_vmc_sms");

	$daysend = (int) $config->daysmsaccountduedate;
	$dayspendincourse = (int) $config->dayspanincourse;
	print_object("daysend: " . $daysend);
	print_object("dayspendincourse: " . $dayspendincourse);

	$params = array('daythreshold1' => th_vmc_sms::ONE_DAY * ($daysend - 1), 'daythreshold2' => th_vmc_sms::ONE_DAY * $daysend);

	foreach ($params as $key => $daythres) {
		print_object(get_datetime($now - $daythres));
	}

	$sql = "SELECT ue.id,courseid,ra.userid, ue.timecreated, from_unixtime($now-ue.timecreated), from_unixtime(ue.timecreated)
			from {user_enrolments} ue
			join {enrol} e
			on e.id = ue.enrolid and ($now  - ue.timecreated)>:daythreshold1 and ($now  - ue.timecreated)<=:daythreshold2
			join {role_assignments} ra
			on ue.userid = ra.userid and ra.roleid = 5
			join {context} c
			on c.contextlevel = 50 and c.id = ra.contextid and e.courseid = c.instanceid";

	$records = $DB->get_records_sql($sql, $params);
	print_object($records);

	$supportuser = core_user::get_support_user();

	foreach ($records as $key => $record) {

		$studentid = $record->userid;
		$courseid = $record->courseid;

		$courserecord = $DB->get_record('course', array('id' => $courseid));
		$coursefullname = $courserecord->fullname;

		$enroltime = $record->timecreated;
		print_object("enroltime: ");
		print_object(get_datetime($enroltime));
		$duetime = $enroltime + $dayspendincourse * th_vmc_sms::ONE_DAY;
		$duedate = get_datetime($duetime);
		$dayremain = $dayspendincourse - $daysend;

		$student = $DB->get_record('user', array('id' => $studentid));
		print_object(fullname($student));

		$subject = get_string('subject_smsaccountduedate', 'local_th_vmc_sms');
		$message = get_string('email_smsaccountduedate', 'local_th_vmc_sms', array('userfullname' => fullname($student),
			'coursefullname' => $coursefullname,
			'duedate' => $duedate,
			'dayremain' => $dayremain));

		$record = new \stdClass();
		$record->component = 'local_th_vmc_sms';
		$record->classname = '\local_th_vmc_sms\task\sms_accountduedate';
		$record->lastruntime = time();
		$record->courseid = $courseid;
		$record->customdata = json_encode(array('studentid' => (int) ($student->id)));

		$params = [$record->classname, $record->component, $record->courseid, $record->customdata];
		$sql = 'classname = ? AND component = ? AND courseid = ? AND ' .
		$DB->sql_compare_text('customdata', \core_text::strlen($record->customdata) + 1) . ' = ?';

		$smstask = $DB->get_record_select('local_th_vmc_sms', $sql, $params);
		if ($smstask == "" || $smstask == null) {
			print_object("Email sending");
			$send_ok = email_to_user($student, $supportuser, $subject, $message);
			if ($send_ok) {
				$DB->insert_record('local_th_vmc_sms', $record);
			}
		} else {
			print_object("Email already sended");
		}
	}
}

function sms_ketthuckhoahoc() {
	print_object("sms_ketthuckhoahoc");

	global $CFG, $DB;

	$now = time();
	$config = get_config("local_th_vmc_sms");

	$daysend = (int) $config->daysmsketthuckhoahoc;
	$dayspendincourse = (int) $config->dayspanincourse;

	$params = array('daythreshold1' => th_vmc_sms::ONE_DAY * ($daysend - 1), 'daythreshold2' => th_vmc_sms::ONE_DAY * $daysend);

	foreach ($params as $key => $daythres) {
		print_object(get_datetime($now - $daythres));
	}

	$sql = "SELECT ue.id,courseid,ra.userid, ue.timecreated, from_unixtime($now-ue.timecreated), from_unixtime(ue.timecreated)
			from {user_enrolments} ue
			join {enrol} e
			on e.id = ue.enrolid and ($now  - ue.timecreated)>:daythreshold1 and ($now  - ue.timecreated)<=:daythreshold2
			join {role_assignments} ra
			on ue.userid = ra.userid and ra.roleid = 5
			join {context} c
			on c.contextlevel = 50 and c.id = ra.contextid and e.courseid = c.instanceid";

	$records = $DB->get_records_sql($sql, $params);
	print_object($records);

	$supportuser = core_user::get_support_user();

	foreach ($records as $key => $record) {

		$studentid = $record->userid;
		$courseid = $record->courseid;

		$courserecord = $DB->get_record('course', array('id' => $courseid));
		$coursefullname = $courserecord->fullname;

		$enroltime = $record->timecreated;
		print_object("enroltime: ");
		print_object(get_datetime($enroltime));
		$duetime = $enroltime + $dayspendincourse * th_vmc_sms::ONE_DAY;
		$duedate = get_datetime($duetime);

		$student = $DB->get_record('user', array('id' => $studentid));
		print_object(fullname($student));

		$subject = get_string('subject_smsketthuckhoahoc', 'local_th_vmc_sms');
		$message = get_string('email_smsketthuckhoahoc', 'local_th_vmc_sms', array('userfullname' => fullname($student),
			'coursefullname' => $coursefullname,
			'duedate' => $duedate));

		$record = new \stdClass();
		$record->component = 'local_th_vmc_sms';
		$record->classname = '\local_th_vmc_sms\task\sms_ketthuckhoahoc';
		$record->lastruntime = time();
		$record->courseid = $courseid;
		$record->customdata = json_encode(array('studentid' => (int) ($student->id)));

		$params = [$record->classname, $record->component, $record->courseid, $record->customdata];
		$sql = 'classname = ? AND component = ? AND courseid = ? AND ' .
		$DB->sql_compare_text('customdata', \core_text::strlen($record->customdata) + 1) . ' = ?';

		$smstask = $DB->get_record_select('local_th_vmc_sms', $sql, $params);
		if ($smstask == "" || $smstask == null) {
			print_object("Email sending");
			$send_ok = email_to_user($student, $supportuser, $subject, $message);
			if ($send_ok) {
				$DB->insert_record('local_th_vmc_sms', $record);
			}
		} else {
			print_object("Email already sended");
		}
	}
}
// function sms_demo{
// 	return mtrace("Hello World");
// }