{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	{include file="Header.tpl"|vtemplate_path:$MODULE}
	<div class="bodyContents">
		<div class="mainContainer">
			<div class="contentsDiv col-md-12 marginLeftZero dashboardContainer">
				{include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME DASHBOARDHEADER_TITLE=vtranslate($MODULE, $MODULE)}
				<div class="dashboardViewContainer">
					<div class="col-xs-12 paddingLRZero">
						{if !empty($MODULES_WITH_WIDGET)}
							<ul class="nav nav-tabs massEditTabs selectDashboradView">
								{foreach from=$MODULES_WITH_WIDGET item=MODULE_WIDGET}
									<li class="{if $MODULE_NAME eq $MODULE_WIDGET} active {/if}" data-module="{$MODULE_WIDGET}"><a>{vtranslate($MODULE_WIDGET, $MODULE_WIDGET)}</a></li>
								{/foreach}
							</ul>
						{/if}
					</div>
					{include file='dashboards/DashBoardButtons.tpl'|@vtemplate_path:$MODULE}
					<div class="col-xs-12 paddingLRZero">
{/strip}
