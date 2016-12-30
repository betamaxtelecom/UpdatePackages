<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/


$previousBulkSaveMode = $VTIGER_BULK_SAVE_MODE;
$VTIGER_BULK_SAVE_MODE = true;
require_once 'config/performance.php';
require_once 'config/debug.php';
require_once  'include/Loader.php';
require_once 'include/runtime/Controller.php';
require_once 'include/runtime/BaseModel.php';
require_once 'include/runtime/Globals.php';
Import_Data_Action::runScheduledImport();

$VTIGER_BULK_SAVE_MODE = $previousBulkSaveMode;