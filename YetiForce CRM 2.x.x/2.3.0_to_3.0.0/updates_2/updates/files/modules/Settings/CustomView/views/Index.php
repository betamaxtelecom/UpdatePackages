<?php

/**
 * CustomView config view class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Settings_CustomView_Index_View extends Settings_Vtiger_Index_View
{

	function preProcess(Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', Settings_CustomView_Module_Model::getSupportedModules());
	}

	public function process(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$supportedModuleId = $request->get('sourceModule');
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = Settings_LangManagement_Module_Model::getInstance($qualifiedModuleName);
		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE_MODULE_ID', $supportedModuleId);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		if ($request->isAjax()) {
			$viewer->view('IndexContents.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('Index.tpl', $qualifiedModuleName);
		}
	}

	function getFooterScripts(Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.CustomView.resources.CustomView'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
