<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */
require_once 'modules/com_vtiger_workflow/include.inc';
require_once 'modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc';
require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';
require_once('include/events/include.inc');
include_once('vtlib/Vtiger/Module.php');

class YetiForceUpdate
{

	var $package;
	var $modulenode;
	var $return = true;
	var $filesToDelete = [
		'api/webservice/Portal/modules/Users/Authentication.php',
		'layouts/vlayout/modules/OSSMailView/przychodzaca.png',
		'layouts/vlayout/modules/OSSMailView/wewnetrzna.png',
		'layouts/vlayout/modules/OSSMailView/wychodzaca.png',
		'layouts/vlayout/modules/OSSMailView/zalacznik.png',
		'modules/OSSMailScanner/views/index.php',
		'modules/Settings/OSSMailScanner/views/index.php',
		'layouts/vlayout/modules/OSSMailScanner',
		'layouts/vlayout/skins/images/line.PNG'
	];

	function YetiForceUpdate($modulenode)
	{
		$this->modulenode = $modulenode;
	}

	function preupdate()
	{
		//$this->package->_errorText = 'Errot';
		return true;
	}

	function update()
	{
		$this->updateFiles();
		$this->databaseSchema();
		$this->databaseData();
	}

	function postupdate()
	{
		return true;
	}

	function updateFiles()
	{
		global $log, $root_directory;
		$log->debug("Entering YetiForceUpdate::updateFiles() method ...");
		if (!$root_directory)
			$root_directory = getcwd();
		$config = $root_directory . '/config/config.inc.php';
		if (file_exists($config)) {
			if (strpos(file_get_contents($config), 'isActiveSendingMails') === FALSE) {
				$configC = '

// Is sending emails active. 
$isActiveSendingMails = false;
';
				file_put_contents($config, $configC, FILE_APPEND);
			}
		}
		$log->debug("Exiting YetiForceUpdate::updateFiles() method ... ");
	}

	function databaseSchema()
	{
		global $log, $adb;
		$log->debug("Entering YetiForceUpdate::databaseSchema() method ...");

		$result = $adb->query("SHOW COLUMNS FROM `vtiger_activitytype` LIKE 'color';");
		if (!$adb->num_rows($result)) {
			$adb->query("ALTER TABLE `vtiger_activitytype` ADD COLUMN `color` varchar(25) NULL after `sortorderid`;");
			$result = $adb->pquery("SELECT * FROM `vtiger_calendar_config` WHERE `type` = ? AND `name` IN (?,?);", ['colors', 'Meeting', 'Call']);
			while ($row = $adb->fetch_array($result)) {
				$adb->pquery("UPDATE `vtiger_activitytype` SET `color` = ? WHERE `activitytype` = ? ;", [$row['value'], $row['name']]);
			}
			$adb->pquery('DELETE FROM `vtiger_calendar_config` WHERE `type` = ? AND `name` IN (?,?);', ['colors', 'Meeting', 'Call']);
		}

		$result = $adb->pquery("SHOW COLUMNS FROM `vtiger_crmentity` LIKE 'shownerid';");
		$row = $adb->fetch_array($result);
		if ($row && (strpos($row['Type'], 'varchar') === false )) {
			$adb->query("ALTER TABLE `vtiger_crmentity` CHANGE `shownerid` `shownerid` varchar(255) NOT NULL after `smownerid`;");
			$adb->query("UPDATE `vtiger_crmentity` SET shownerid = '' WHERE shownerid IS NULL;");
		}

		$adb->query("CREATE TABLE IF NOT EXISTS `yetiforce_currencyupdate` (
					`id` int(19) NOT NULL AUTO_INCREMENT,
					`currency_id` int(19) NOT NULL,
					`fetch_date` date NOT NULL,
					`exchange_date` date NOT NULL,
					`exchange` decimal(10,4) NOT NULL,
					`bank_id` int(19) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `fk_1_vtiger_osscurrencies` (`currency_id`),
					CONSTRAINT `fk_1_vtiger_osscurrencies` FOREIGN KEY (`currency_id`) REFERENCES `vtiger_currency_info` (`id`) ON DELETE CASCADE
				  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
		$adb->query("CREATE TABLE IF NOT EXISTS `yetiforce_currencyupdate_banks` (
					`id` int(19) NOT NULL AUTO_INCREMENT,
					`bank_name` varchar(255) NOT NULL,
					`active` int(1) NOT NULL,
					PRIMARY KEY (`id`)
				  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		$result = $adb->pquery("SHOW COLUMNS FROM `vtiger_ossmailview`;");
		while ($row = $adb->fetch_array($result)) {
			if ($row['Field'] == 'from_id' && (strpos($row['Type'], 'varchar') === false )) {
				$adb->query("ALTER TABLE `vtiger_ossmailview` CHANGE `from_id` `from_id` varchar(50) NOT NULL after `type`;");
			}
			if ($row['Field'] == 'to_id' && (strpos($row['Type'], 'varchar') === false )) {
				$adb->query("ALTER TABLE `vtiger_ossmailview` CHANGE `to_id` `to_id` varchar(100) NOT NULL after `from_id`;");
			}
		}

		$log->debug("Exiting YetiForceUpdate::databaseSchema() method ...");
	}

	function databaseData()
	{
		global $log, $adb;
		$log->debug("Entering YetiForceUpdate::databaseData() method ...");

		$result = $adb->pquery("SELECT * FROM `vtiger_blocks` WHERE `blocklabel` = ? AND `tabid` = ?;", ['Oryginalna wiadomość', getTabid('OSSMailView')]);
		if ($adb->num_rows($result)) {
			$adb->pquery("UPDATE `vtiger_blocks` SET `blocklabel` = ? WHERE `blocklabel` = ? AND `tabid` = ?;", ['LBL_ORIGN_MESSAGE', 'Oryginalna wiadomość', getTabid('OSSMailView')]);
		}
		$result = $adb->query("SELECT * FROM `vtiger_field` WHERE `fieldlabel` = 'Treść' AND `columnname` = 'orginal_mail';");
		if ($adb->num_rows($result)) {
			$adb->pquery("UPDATE `vtiger_field` SET `fieldlabel` = ? WHERE `fieldlabel` = ? AND `columnname` = ?;", ['Content', 'Treść', 'orginal_mail']);
		}

		$this->relatedList();

		$result = $adb->query("SELECT * FROM `yetiforce_proc_marketing` WHERE `type` = 'lead' AND `param` = 'convert_status';");
		if (!$adb->num_rows($result)) {
			$adb->query("insert  into `yetiforce_proc_marketing`(`type`,`param`,`value`) values ('lead','convert_status','LBL_LEAD_ACQUIRED');");
		}

		$result = $adb->pquery("SELECT * FROM `vtiger_settings_field` WHERE `name` = ? ", ['LBL_CUSTOM_FIELD_MAPPING']);
		if (!$adb->num_rows($result)) {
			$blockid = $adb->query_result($adb->pquery("SELECT blockid FROM vtiger_settings_blocks WHERE label='LBL_STUDIO'", []), 0, 'blockid');
			$sequence = (int) $adb->query_result($adb->pquery("SELECT max(sequence) as sequence FROM vtiger_settings_field WHERE blockid=?", [$blockid]), 0, 'sequence') + 1;
			$fieldid = $adb->getUniqueId('vtiger_settings_field');
			$adb->pquery("INSERT INTO vtiger_settings_field (fieldid,blockid,sequence,name,iconpath,description,linkto)
			VALUES (?,?,?,?,?,?,?)", array($fieldid, $blockid, $sequence, 'LBL_CUSTOM_FIELD_MAPPING', '', 'LBL_CUSTOM_FIELD_MAPPING_DESCRIPTION', 'index.php?parent=Settings&module=Leads&view=MappingDetail'));
		}

		$mailTemplates = ['Notify Owner On Ticket Change', 'Notify Account On Ticket Change', 'Notify Contact On Ticket Closed', 'Notify Account On Ticket Closed', 'Notify Contact On Ticket Create', 'Notify Account On Ticket Create', 'Notify Contact On Ticket Change', 'Notify Owner On Ticket Closed', 'Notify Owner On Ticket Create'];
		$result = $adb->pquery("SELECT `content`,`name` FROM `vtiger_ossmailtemplates` WHERE `name` IN (" . generateQuestionMarks($mailTemplates) . ") AND `oss_module_list` = ?;", [$mailTemplates, 'HelpDesk']);
		while ($row = $adb->fetch_array($result)) {
			$content = str_replace('
	<li>#b#718#bEnd#: #a#718#aEnd#</li>', '', $row['content']);
			$adb->pquery("UPDATE `vtiger_ossmailtemplates` SET `content` = ? WHERE `name` = ? AND `oss_module_list` = ?;", [$content, $row['name'], 'HelpDesk']);
		}

		$result = $adb->query("SELECT * FROM `vtiger_entityname` WHERE `fieldname` = 'holidaysentitlement_year' AND `modulename` = 'HolidaysEntitlement';");
		if ($adb->num_rows($result)) {
			$adb->pquery("UPDATE `vtiger_entityname` SET `fieldname` = ?, `searchcolumn` = ? WHERE `modulename` = ?;", ['ossemployeesid,days', 'ossemployeesid', 'HolidaysEntitlement']);
			Settings_Search_Module_Model::UpdateLabels(['tabid' => getTabid('HolidaysEntitlement')]);
		}

		$result = $adb->query("SELECT * FROM `vtiger_links` WHERE `linklabel` = 'LBL_CREATED_BY_ME_BUT_NOT_MINE_ACTIVITIES' AND `linktype` = 'DASHBOARDWIDGET';");
		if (!$adb->num_rows($result)) {
			$linkModule = Vtiger_Module::getInstance('Home');
			$linkModule->addLink('DASHBOARDWIDGET', "LBL_CREATED_BY_ME_BUT_NOT_MINE_ACTIVITIES", 'index.php?module=Home&view=ShowWidget&name=CreatedNotMineActivities');
		}

		$result = $adb->query("SELECT * FROM `vtiger_cron_task` WHERE `module` = 'CurrencyUpdate';");
		if (!$adb->num_rows($result)) {
			$cron = ['LBL_CURRENCY_UPDATE', 'modules/Settings/CurrencyUpdate/cron/CurrencyUpdateCron.php', 86400, NULL, NULL, 1, 'CurrencyUpdate', 4, 'Recommended frequency for Currency Update is 24 hours'];
			Vtiger_Cron::register($cron[0], $cron[1], $cron[2], $cron[6], $cron[5], 0, $cron[8]);
		}

		$result = $adb->pquery("SELECT * FROM `vtiger_settings_field` WHERE `name` = ? ", ['LBL_CURRENCY_UPDATE']);
		if (!$adb->num_rows($result)) {
			$blockid = $adb->query_result($adb->pquery("SELECT blockid FROM vtiger_settings_blocks WHERE label='LBL_OTHER_SETTINGS'", []), 0, 'blockid');
			$sequence = (int) $adb->query_result($adb->pquery("SELECT max(sequence) as sequence FROM vtiger_settings_field WHERE blockid=?", [$blockid]), 0, 'sequence') + 1;
			$fieldid = $adb->getUniqueId('vtiger_settings_field');
			$adb->pquery("INSERT INTO vtiger_settings_field (fieldid,blockid,sequence,name,iconpath,description,linkto)
			VALUES (?,?,?,?,?,?,?)", [$fieldid, $blockid, $sequence, 'LBL_CURRENCY_UPDATE', '', 'LBL_CURRENCY_UPDATE_DESCRIPTION', 'index.php?module=CurrencyUpdate&view=Index&parent=Settings']);
		}

		$result = $adb->query("SELECT * FROM `vtiger_ossmailscanner_config` WHERE `conf_type` = 'exceptions';");
		if (!$adb->num_rows($result)) {
			$adb->pquery('insert  into `vtiger_ossmailscanner_config`(`conf_type`,`parameter`,`value`) values (?,?,?);', ['exceptions', 'crating_mails', NULL]);
			$adb->pquery('insert  into `vtiger_ossmailscanner_config`(`conf_type`,`parameter`,`value`) values (?,?,?);', ['exceptions', 'crating_mails', NULL]);
		}

		$adb->pquery("UPDATE `vtiger_settings_field` SET `linkto` = ? WHERE `linkto` = ?;", ['index.php?module=OSSMailScanner&parent=Settings&view=Index', 'index.php?module=OSSMailScanner&parent=Settings&view=index']);

		$result1 = $adb->pquery("SELECT fieldid FROM `vtiger_field` WHERE columnname = ? AND tablename = ?", ['parent_id', 'vtiger_troubletickets']);
		$result2 = $adb->pquery("SELECT * FROM `vtiger_fieldmodulerel` WHERE fieldid = ? AND relmodule = ?", [$adb->query_result($result1, 0, 'fieldid'), 'Vendors']);
		if ($adb->num_rows($result2) == 0) {
			$adb->query("insert  into `vtiger_fieldmodulerel`(`fieldid`,`module`,`relmodule`) values (" . $adb->query_result($result1, 0, 'fieldid') . ",'HelpDesk','Vendors');");
		}
		$result1 = $adb->pquery("SELECT fieldid FROM `vtiger_field` WHERE columnname = ? AND tablename = ?", ['linktoaccountscontacts', 'vtiger_project']);
		$result2 = $adb->pquery("SELECT * FROM `vtiger_fieldmodulerel` WHERE fieldid = ? AND relmodule = ?", [$adb->query_result($result1, 0, 'fieldid'), 'Vendors']);
		if ($adb->num_rows($result2) == 0) {
			$adb->query("insert  into `vtiger_fieldmodulerel`(`fieldid`,`module`,`relmodule`) values (" . $adb->query_result($result1, 0, 'fieldid') . ",'Project','Vendors');");
		}

		$moduleInstance = Vtiger_Module::getInstance('HelpDesk');
		$target_Module = Vtiger_Module::getInstance('Vendors');
		$target_Module->setRelatedList($moduleInstance, 'HelpDesk', array('add'), 'get_dependents_list');

		$moduleInstance = Vtiger_Module::getInstance('Project');
		$target_Module = Vtiger_Module::getInstance('Vendors');
		$target_Module->setRelatedList($moduleInstance, 'Project', array('add'), 'get_dependents_list');

		$log->debug("Exiting YetiForceUpdate::databaseData() method ...");
	}

	public function relatedList()
	{
		global $log, $adb;
		$log->debug("Entering YetiForceUpdate::relatedList() method ...");

		$addRelations = [];
		$addRelations['LettersIn'][] = ['related_tabid' => 'Contacts', 'label' => 'Contacts', 'actions' => 'ADD,SELECT', 'name' => 'get_related_list'];
		$addRelations['LettersOut'][] = ['related_tabid' => 'Contacts', 'label' => 'Contacts', 'actions' => 'ADD,SELECT', 'name' => 'get_related_list'];

		foreach ($addRelations as $moduleName => $relations) {
			$moduleInstance = Vtiger_Module::getInstance($moduleName);
			foreach ($relations as $relation) {
				$relatedInstance = Vtiger_Module::getInstance($relation['related_tabid']);
				$moduleInstance->setRelatedList($relatedInstance, $relation['label'], $relation['actions'], $relation['name']);
			}
		}
		$log->debug("Exiting YetiForceUpdate::relatedList() method ...");
	}
}
