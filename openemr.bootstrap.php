<?php

 /**
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
 * @global OpenEMR\Core\ModulesClassLoader $classLoader
 */

$classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\MedilinkSA\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');
/**
 * @global EventDispatcher $eventDispatcher Injected by the OpenEMR module loader;
 */

$bootstrap = new Bootstrap($eventDispatcher);
$bootstrap->subscribeToEvents();
