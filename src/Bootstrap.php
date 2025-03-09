<?php

/**
 * Bootstrap MedilinkSA module.
 * This file initializes the MedilinkSA module in OpenEMR.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 *
 * @author    Sibusiso Khoza <randd@sive.host>
 * @copyright Copyright (c) 2025 Sibusiso Khoza <randd@sive.host>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\MedilinkSA;  

/**
 * Import OpenEMR core classes.
 */
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Events\RestApiExtend\RestApiResourceServiceEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\Services\Globals\GlobalSetting;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use OpenEMR\Events\PatientDemographics\RenderEvent as pRenderEvent;
use OpenEMR\Events\Appointments\AppointmentSetEvent;

use OpenEMR\Modules\MedilinkSA\MedilinkSARteService; 

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";
    const MODULE_NAME = "MedilinkSA";

    private $eventDispatcher;
    private $globalsConfig;
    private $moduleDirectoryName;
    private $twig;
    private $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        global $GLOBALS;

        if (empty($kernel)) {
            $kernel = new Kernel();
        }

        $twig = new TwigContainer($this->getTemplatePath(), $kernel);
        $this->twig = $twig->getTwig();
        $this->moduleDirectoryName = basename(dirname(__DIR__));
        $this->eventDispatcher = $eventDispatcher;
        $this->globalsConfig = new GlobalConfig($GLOBALS);
        $this->logger = new SystemLogger();
    }

    public function subscribeToEvents()
    {
        $this->addGlobalSettings();

        if ($this->globalsConfig->isConfigured()) {
            $this->registerMenuItems();
            $this->registerTemplateEvents();
            $this->subscribeToApiEvents();
            $this->registerDemographicsEvents();
            $this->registerEligibilityEvents();
        }
    }

    public function getGlobalConfig()
    {
        return $this->globalsConfig;
    }

    public function addGlobalSettings()
    {
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, [$this, 'addGlobalSettingsSection']);
    }

    public function addGlobalSettingsSection(GlobalsInitializedEvent $event)
    {
        global $GLOBALS;

        $service = $event->getGlobalsService();
        $section = xlt("MedilinkSA");
        $service->createSection($section, 'Portal');

        $settings = $this->globalsConfig->getGlobalSettingSectionConfiguration();

        foreach ($settings as $key => $config) {
            $value = $GLOBALS[$key] ?? $config['default'];
            $service->appendToSection(
                $section,
                $key,
                new GlobalSetting(
                    xlt($config['title']),
                    $config['type'],
                    $value,
                    xlt($config['description']),
                    true
                )
            );
        }
    }

    public function registerDemographicsEvents()
    {
        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_ELIGIBILITY_CARD)) {
            $this->eventDispatcher->addListener(pRenderEvent::EVENT_SECTION_LIST_RENDER_AFTER, [$this, 'renderEligibilitySection']);
        }
    }

    public function registerEligibilityEvents()
    {
        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_REALTIME_ELIGIBILITY)) {
            $this->eventDispatcher->addListener(AppointmentSetEvent::EVENT_HANDLE, [$this, 'renderAppointmentSetEvent']);
        }
    }

    public function renderAppointmentSetEvent(AppointmentSetEvent $event)
    {
        MedilinkSARteService::createEligibilityFromAppointment($event->eid);
    }

    public function renderEligibilitySection(pRenderEvent $event)
    {
        $path = str_replace("src", "templates", __DIR__);
        $pid = $event->getPid();
        ?>
        <section class="card mb-2">
        <?php
        expand_collapse_widget(
            xl("MedilinkSA Eligibility"),
            "medilinksaelig",
            xl("Edit"),
            "",
            "",
            "html",
            "notab",
            false,
            false,
            false
        );
        ?>
        <div> <?php include $path . "/navbar.php"; ?> </div>
    </section>
        <?php
    }

    public function registerTemplateEvents()
    {
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, [$this, 'addTemplateOverrideLoader']);
    }

    public function addTemplateOverrideLoader(TwigEnvironmentEvent $event)
    {
        try {
            $twig = $event->getTwigEnvironment();
            if ($twig === $this->twig) {
                return;
            }
            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath($this->getTemplatePath());
            }
        } catch (LoaderError $error) {
            $this->logger->errorLogCaller("Failed to create template loader", ['innerMessage' => $error->getMessage(), 'trace' => $error->getTraceAsString()]);
        }
    }

    public function registerMenuItems()
    {
        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_MENU)) {
            $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'addCustomModuleMenuItem']);
        }
    }

    public function addCustomModuleMenuItem(MenuEvent $event)
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'mod';
        $menuItem->menu_id = 'mod0';
        $menuItem->label = xlt("MedilinkSA");
        $menuItem->url = "/interface/modules/custom_modules/MedilinkSA/public/index.php";
        $menuItem->children = [];
        $menuItem->acl_req = [];

        foreach ($menu as $item) {
            if ($item->menu_id == 'modimg') {
                $item->children[] = $menuItem;
                break;
            }
        }

        $event->setMenu($menu);
    }

    public function subscribeToApiEvents()
    {
    }

    public function getTemplatePath()
    {
        return \dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
    }
}

