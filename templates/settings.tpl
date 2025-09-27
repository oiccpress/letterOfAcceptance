{**
 * templates/settings.tpl
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the pluginTemplate plugin.
 *}
<script>
	$(function() {ldelim}
		$('#pluginLOASettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
	class="pkp_form"
	id="pluginLOASettings"
	method="POST"
	action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
	<!-- Always add the csrf token to secure your form -->
	{csrf}

	{fbvFormSection label="plugins.generic.letterOfAcceptance.loaTemplate"}
		{fbvElement
			type="textarea"
            rich=true
			id="loa_template"
            value=$loa_template
            variables=$loa_variables
			description="plugins.generic.letterOfAcceptance.loaTemplate"
		}
	{/fbvFormSection}
	{fbvFormButtons submitText="common.save"}
</form>