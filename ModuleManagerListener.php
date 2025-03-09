<?php

/**
 * Class to be called from Laminas Module Manager for reporting management actions.
 *
 * @package   OpenEMR Modules
 * @link    http://www.open-emr.org
 *
 * @author    Sibusiso Khoza <randd@sive.host>
 * @copyright Copyright (c) 2025 Sibusiso Khoza <randd@sive.host>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */


use OpenEMR\Core\AbstractModuleActionListener;

/**
 * Allows maintenance of background tasks depending on Module Manager action.
 */
class ModuleManagerListener extends AbstractModuleActionListener
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handles module manager actions.
     *
     * @param string $methodName
     * @param string $modId
     * @param string $currentActionStatus
     * @return string On success, returns `$currentActionStatus`, otherwise returns an error string.
     */
    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists(self::class, $methodName)) {
            return self::$methodName($modId, $currentActionStatus);
        } else {
            return $currentActionStatus;
        }
    }

    /**
     * Returns the module namespace.
     *
     * @return string
     */
    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\MedilinkSA\\';
    }

    /**
     * Returns an instance of this class for the Laminas Manager.
     *
     * @return ModuleManagerListener
     */
    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    /**
     * Handles the module help request.
     *
     * @param string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function help_requested($modId, $currentActionStatus): mixed
    {
        if (file_exists(__DIR__ . '/show_help.php')) {
            include __DIR__ . '/show_help.php';
        }
        return $currentActionStatus;
    }

    /**
     * Enables the MedilinkSA module services.
     *
     * @param string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function enable($modId, $currentActionStatus): mixed
    {
        $logMessage = 'MedilinkSA Background tasks have been enabled';
        $sql = "UPDATE `background_services` SET `active` = '1' WHERE `name` = ? OR `name` = ? OR `name` = ?";
        $status = sqlQuery($sql, array('MedilinkSA_Send', 'MedilinkSA_Receive', 'MedilinkSA_Elig_Send_Receive'));
        error_log($logMessage . ' ' . text($status));
        return $currentActionStatus;
    }

    /**
     * Disables the MedilinkSA module services.
     *
     * @param string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function disable($modId, $currentActionStatus): mixed
    {
        $logMessage = 'MedilinkSA Background tasks have been disabled';
        $sql = "UPDATE `background_services` SET `active` = '0' WHERE `name` = ? OR `name` = ? OR `name` = ?";
        $status = sqlQuery($sql, array('MedilinkSA_Send', 'MedilinkSA_Receive', 'MedilinkSA_Elig_Send_Receive'));
        error_log($logMessage . ' ' . text($status));
        return $currentActionStatus;
    }

    /**
     * Unregisters the MedilinkSA module services.
     *
     * @param string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function unregister($modId, $currentActionStatus)
    {
        $logMessage = 'MedilinkSA Background tasks have been removed';
        $sql = "DELETE FROM `background_services` WHERE `name` = ? OR `name` = ? OR `name` = ?";
        $status = sqlQuery($sql, array('MedilinkSA_Send', 'MedilinkSA_Receive', 'MedilinkSA_Elig_Send_Receive'));
        error_log($logMessage . ' ' . text($status));
        return $currentActionStatus;
    }
}

