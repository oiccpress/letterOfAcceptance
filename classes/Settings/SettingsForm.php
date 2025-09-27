<?php
/**
 * @file SettingsForm.php
 *
 * Copyright (c) 2017-2023 Simon Fraser University
 * Copyright (c) 2017-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @brief Settings form class for the this plugin.
 */

namespace APP\plugins\generic\letterOfAcceptance\classes\Settings;

use APP\core\Application;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\plugins\generic\letterOfAcceptance\classes\Constants;
use APP\plugins\generic\letterOfAcceptance\LetterOfAcceptancePlugin;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class SettingsForm extends Form
{
    /** @var LetterOfAcceptancePlugin */
    public LetterOfAcceptancePlugin $plugin;

    /**
     * Defines the settings form's template and adds validation checks.
     *
     * Always add POST and CSRF validation to secure your form.
     */
    public function __construct(LetterOfAcceptancePlugin &$plugin)
    {
        $this->plugin = &$plugin;

        parent::__construct($this->plugin->getTemplateResource(Constants::SETTINGS_TEMPLATE));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Load settings already saved in the database
     *
     * Settings are stored by context, so that each journal, press,
     * or preprint server can have different settings.
     */
    public function initData(): void
    {
        $context = Application::get()
            ->getRequest()
            ->getContext();

        $this->setData(
            Constants::SETTING_TEMPLATE,
            $this->plugin->getSetting(
                $context ? $context->getId() : null,
                Constants::SETTING_TEMPLATE
            )
        );

        // Set variables available to the user in their LOA template
        // based on what we need
        $this->setData('loa_variables', [
            'currentDate' => __('plugins.generic.letterOfAcceptance.variable.currentDate'),
            'authorFullName' => __('plugins.generic.letterOfAcceptance.variable.authorFullName'),
            'authorAffiliation' => __('plugins.generic.letterOfAcceptance.variable.authorAffiliation'),
            'submissionTitle' => __('plugins.generic.letterOfAcceptance.variable.submissionTitle'),
            'submissionId' => __('plugins.generic.letterOfAcceptance.variable.submissionId'),
            'journalName' => __('plugins.generic.letterOfAcceptance.variable.journalName'),
            'siteName' => __('plugins.generic.letterOfAcceptance.variable.siteName'),
            'journalPrincipalContactName' => __('plugins.generic.letterOfAcceptance.variable.journalPrincipalContactName'),
            'journalPrincipalContactEmail' => __('plugins.generic.letterOfAcceptance.variable.journalPrincipalContactEmail'),
        ]);

        parent::initData();
    }

    /**
     * Load data that was submitted with the form
     */
    public function readInputData(): void
    {
        $this->readUserVars([Constants::SETTING_TEMPLATE]);

        parent::readInputData();
    }

    /**
     * Fetch any additional data needed for your form.
     *
     * Data assigned to the form using $this->setData() during the
     * initData() or readInputData() methods will be passed to the
     * template.
     *
     * In the example below, the plugin name is passed to the
     * template so that it can be used in the URL that the form is
     * submitted to.
     */
    public function fetch($request, $template = null, $display = false): ?string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());

        return parent::fetch($request, $template, $display);
    }

    /**
     * Save the plugin settings and notify the user
     * that the save was successful
     */
    public function execute(...$functionArgs): mixed
    {
        $context = Application::get()
            ->getRequest()
            ->getContext();

        $this->plugin->updateSetting(
            $context ? $context->getId() : null,
            Constants::SETTING_TEMPLATE,
            $this->getData(Constants::SETTING_TEMPLATE)
        );

        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            Notification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('common.changesSaved')]
        );

        return parent::execute();
    }
}