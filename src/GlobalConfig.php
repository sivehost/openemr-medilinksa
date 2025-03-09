<?php

/**
 * Bootstrap custom module skeleton.  This file is an example custom module that can be used
 * to create modules that can be utilized inside the OpenEMR system.  It is NOT intended for
 * production and is intended to serve as the barebone requirements you need to get started
 * writing modules that can be installed and used in OpenEMR.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 *
 * @author    Sibusiso Khoza <randd@sive.host>
 * @copyright Copyright (c) 2025 Sibusiso Khoza <randd@sive.host>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\MedilinkSA;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Services\Globals\GlobalSetting;

class GlobalConfig
{

    public const CONFIG_OPTION_GRANTTYPE = 'MedilinkSA_config_granttype';    
    public const CONFIG_OPTION_CLIENTID = 'MedilinkSA_config_clientid';
    public const CONFIG_OPTION_USERNAME = 'MedilinkSA_config_username';    
    public const CONFIG_OPTION_PASSWORD = 'MedilinkSA_config_password';
    public const CONFIG_OPTION_TOKEN = 'MedilinkSA_config_token';
    public const CONFIG_OPTION_MEMBERSHIP = 'MedilinkSA_config_membership';
    public const CONFIG_OPTION_SUBMISSION = 'MedilinkSA_config_submission';
    public const CONFIG_OPTION_TRACE = 'MedilinkSA_config_trace';
    public const CONFIG_OPTION_REVERSE = 'MedilinkSA_config_reverse';

    public const CONFIG_AUTO_SEND_CLAIMS = 'MedilinkSA_config_auto_send_claims';    
    public const CONFIG_AUTO_SEND_HOUR = 'MedilinkSA_config_auto_send_hour';        
    public const CONFIG_ENABLE_MEMBERSHIP_DETAILS = "MedilinkSA_config_add_membership_details";    //this would be the results from medical aid
    public const CONFIG_ENABLE_REALTIME_MEMBERSHIP_CHECK = "MedilinkSA_enable_rtm";
    public const CONFIG_ENABLE_RESULTS_MEMBERSHIP_AGE = "MedilinkSA_membership_results_age";
    public const CONFIG_ENABLE_MENU = "MedilinkSA_add_menu_button";    

    
    public const CONFIG_OPTION_ENVIRONMENT = 'MedilinkSA_config_environment';    
    public const CONFIG_OPTION_CLIENTSECRET = 'MedilinkSA_config_clientsecret';
    public const CONFIG_OPTION_SCOPE = 'MedilinkSA_config_scope';
    public const CONFIG_OPTION_AUTHORITY = 'MedilinkSA_config_authority';
    public const CONFIG_AUTO_SEND_CLAIM_FILES = 'MedilinkSA_config_auto_send_claim_files';
    public const CONFIG_SERVICE_TYPE_CODES = "MedilinkSA_config_service_type_codes";
    public const CONFIG_ENABLE_ELIGIBILITY_CARD = "MedilinkSA_config_add_eligibility_card";
    public const CONFIG_USE_FACILITY_FOR_ELIGIBILITY = "MedilinkSA_config_use_facility_for_eligibility";
    public const CONFIG_ENABLE_REALTIME_ELIGIBILITY = "MedilinkSA_enable_rte";
    public const CONFIG_ENABLE_RESULTS_ELIGIBILITY = "MedilinkSA_eligibility_results_age";
    public const CONFIG_ENABLE_AUTO_SEND_ELIGIBILITY = "MedilinkSA_send_eligibility";
    public const CONFIG_X12_PARTNER_NAME = "MedilinkSA_x12_partner_name";
    
    private $globalsArray;


    /**
     * @var CryptoGen
     */
    private $cryptoGen;

    public function __construct(array $globalsArray)
    {
        $this->globalsArray = $globalsArray;
        $this->cryptoGen = new CryptoGen();
    }

    /**
     * Returns true if all of the settings have been configured.  Otherwise it returns false.
     *
     * @return bool
     */
    public function isConfigured()
    {
        // $keys = [self::CONFIG_OPTION_TEXT, self::CONFIG_OPTION_ENCRYPTED];
        // foreach ($keys as $key) {
        //     $value = $this->getGlobalSetting($key);
        //     if (empty($value)) {
        //         return false;
        //     }
        // }
        return true;
    }

    public function getGrantType()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_GRANTTYPE);
    }

    public function getClientId()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_CLIENTID);
    }
    
    public function getUsername()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_USERNAME);
    }    

    public function getPassword()
    {
        $encryptedValue = $this->getGlobalSetting(self::CONFIG_OPTION_PASSWORD);
        return $this->cryptoGen->decryptStandard($encryptedValue);
    }
    
    public function getTokens()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_TOKEN);
    }    

    public function getMembership()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_MEMBERSHIP);
    }    
    
    public function getSubmission()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_SUBMISSION);
    }        
    
    public function getTrace()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_TRACE);
    }    
    
    public function getReverse()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_REVERSE);
    }        

    public function getAutoSendClaims()
    {
        return $this->getGlobalSetting(self::CONFIG_AUTO_SEND_CLAIMS);
    }
    public function getAutoSendHour()
    {
        return $this->getGlobalSetting(self::CONFIG_AUTO_SEND_HOUR);
    }    
    public function getEnableMembershipDetails()
    {
        return $this->getGlobalSetting(self::CONFIG_ENABLE_MEMBERSHIP_DETAILS);
    }
    public function getEnableRealtimeMembership()
    {
        return $this->getGlobalSetting(self::CONFIG_ENABLE_REALTIME_MEMBERSHIP_CHECK);
    }    
    
    public function getEnableResultsMembershipAge()
    {
        return $this->getGlobalSetting(self::CONFIG_ENABLE_RESULTS_MEMBERSHIP_AGE);
    }    

    public function getClientSecret()
    {
        $encryptedValue = $this->getGlobalSetting(self::CONFIG_OPTION_CLIENTSECRET);
        return $this->cryptoGen->decryptStandard($encryptedValue);
    }

    public function getClientScope()
    {
        if ($this->getGlobalSetting(self::CONFIG_OPTION_ENVIRONMENT) == "S") {
            return "http://dev.medilinkapi.co.za/claims/reverse";
        } elseif ($this->getGlobalSetting(self::CONFIG_OPTION_ENVIRONMENT) == "D") {
            return "http://dev.medilinkapi.co.za/claims/reverse";
        }
        return "http://live.medilinkapi.co.za/claims/reverse";
    }

    public function getClientAuthority()
    {
        if ($this->getGlobalSetting(self::CONFIG_OPTION_ENVIRONMENT) == "S") {
            return "http://dev.medilinkapi.co.za/claims/reverse";
        } elseif ($this->getGlobalSetting(self::CONFIG_OPTION_ENVIRONMENT) == "D") {
            return "http://dev.medilinkapi.co.za/claims/reverse";
        }
        return "http://live.medilinkapi.co.za/claims/reverse";
    }

    public function getApiServer()
    {
        if ($this->getGlobalSetting(self::CONFIG_OPTION_ENVIRONMENT) == "S") {
            return "http://dev.medilinkapi.co.za/";
        } elseif ($this->getGlobalSetting(self::CONFIG_OPTION_ENVIRONMENT) == "D") {
            return "http://dev.medilinkapi.co.za/";
        }
        return "http://live.medilinkapi.co.za/";
    }



    public function getAutoSendFiles()
    {
        return $this->getGlobalSetting(self::CONFIG_AUTO_SEND_CLAIM_FILES);
    }




    public function getTextOption()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_TEXT);
    }

    /**
     * Returns our decrypted value if we have one, or false if the value could not be decrypted or is empty.
     *
     * @return bool|string
     */
    public function getEncryptedOption()
    {
        $encryptedValue = $this->getGlobalSetting(self::CONFIG_OPTION_ENCRYPTED);
        return $this->cryptoGen->decryptStandard($encryptedValue);
    }

    public function getGlobalSetting($settingKey)
    {
        return $this->globalsArray[$settingKey] ?? null;
    }

    public function getGlobalSettingSectionConfiguration()
    {
        $settings =         [
            self::CONFIG_OPTION_GRANTTYPE => [
                'title' => 'Grant Type is Password'
                ,'description' => 'Is the MedilinkSA Grant Type, a password grant type?'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => '1'
            ]
            ,self::CONFIG_OPTION_CLIENTID => [
                'title' => 'Client ID'
                ,'description' => 'Contact MedilinkSA for your API clientid'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => '1001'
            ]            
            ,self::CONFIG_OPTION_USERNAME => [
                'title' => 'Username'
                ,'description' => 'Contact MedilinkSA for your API username'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => 'admin'
            ]

            ,self::CONFIG_OPTION_PASSWORD => [
                'title' => 'Password'
                ,'description' => 'Contact MedilinkSA for your API password'
                ,'type' => GlobalSetting::DATA_TYPE_ENCRYPTED
                ,'default' => 'Abc@1234'
            ]
            ,self::CONFIG_OPTION_TOKEN => [
                'title' => 'Authentication endpoint'
                ,'description' => 'Contact MedilinkSA for your API authentication token endpoint url'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => 'http://dev.medilinkapi.co.za/token'
            ]
            ,self::CONFIG_OPTION_MEMBERSHIP => [
                'title' => 'Patient Query endpoint'
                ,'description' => 'Contact MedilinkSA for your API patient membership query endpoint url'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => 'http://dev.medilinkapi.co.za/patients/query'
            ]
            ,self::CONFIG_OPTION_SUBMISSION => [
                'title' => 'Claim Submission endpoint'
                ,'description' => 'Contact MedilinkSA for your API claim submission endpoint url'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => 'http://dev.medilinkapi.co.za/claims/submit'
            ]       
            ,self::CONFIG_OPTION_REVERSE => [
                'title' => 'Claim Reversal endpoint'
                ,'description' => 'Contact MedilinkSA for your API claim reversal endpoint url'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => 'http://dev.medilinkapi.co.za/claims/reverse'
            ]                   
            ,self::CONFIG_OPTION_TRACE => [
                'title' => 'Claim Traces endpoint'
                ,'description' => 'Contact MedilinkSA for your API claim traces endpoint url'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => 'http://dev.medilinkapi.co.za/claims/trace'
            ]
            ,self::CONFIG_AUTO_SEND_HOUR => [
                'title' => 'Claims auto send hour'
                ,'description' => 'The hour in the day to automatically send claims saved in the fee sheet that have status RELEASE, in the format 3pm for during the 3pm hour'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => '3pm'
            ]                    
            ,self::CONFIG_AUTO_SEND_CLAIMS => [
                'title' => 'Send Claims Automatically'
                ,'description' => 'Send Claims After Fee Sheet is saved at the end of the day at set hour'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]
            ,self::CONFIG_ENABLE_MEMBERSHIP_DETAILS => [
                'title' => 'Show Membership Details'
                ,'description' => 'Shows Membership details on the Patient Dashboard'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]

            ,self::CONFIG_ENABLE_REALTIME_MEMBERSHIP_CHECK => [
                'title' => 'Enable Membership Check During Appointment'
                ,'description' => 'Enables Membership status and details checks on patients when an appointment is created for that patient'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]
            ,self::CONFIG_ENABLE_RESULTS_MEMBERSHIP_AGE => [
                'title' => 'After how many days to check membership again'
                ,'description' => 'After how many days to check membership again from last data of check, eg 30 for check after 30 days'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => '180'
            ]
            ,self::CONFIG_ENABLE_MENU => [
                'title' => 'Add Menu item under Modules'
                ,'description' => 'Adding a menu item to the system, under Modules main menu item (requires logging out and logging in again)'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => '1'
            ]
        ];
        
        return $settings;
    }
}
