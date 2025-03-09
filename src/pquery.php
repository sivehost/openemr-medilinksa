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
use OpenEMR\Modules\MedilinkSA\Bootstrap;

$bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
$configpq = $bootstrap->getGlobalConfig();

$queryUrl = $configpq->getMembership();

function queryPatient($requestData) {
    global $queryUrl;
    
    $token = getToken();
    if (!$token) {
        die("Failed to retrieve token.");
    }
    
    $headers = [
        'Content-Type: application/json; version=1.0b',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];
    
    $ch = curl_init($queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 501) {
        unlink('medilink_token.json'); // Delete invalid token and retry
        return queryPatient($requestData);
    }
    
    return json_decode($response, true);
}

// Recursive function to output an array as a Bootstrap responsive table.
function displayArrayAsTable($array) {

        // If not an array, simply return its value as a string.
    if (!is_array($array)) {
        return htmlspecialchars((string)'');
    }
    $html = '<table class="table table-bordered table-striped">';
    

    
    foreach ($array as $key => $value) {
        $html .= '<tr>';
        // Key as a header cell
        $html .= '<th scope="row" style="width: 25%;">' . htmlspecialchars($key) . '</th>';
        $html .= '<td>';
        if (is_array($value)) {
            if (empty($value)) {
                $html .= 'None';
            } else {
                // Check if the array is numerically indexed (i.e., multiple items)
                if (array_keys($value) === range(0, count($value) - 1)) {
                    foreach ($value as $subvalue) {
                        if (is_array($subvalue)) {
                            $html .= displayArrayAsTable($subvalue);
                        } else {
                            $html .= htmlspecialchars($subvalue) . '<br/>';
                        }
                    }
                } else {
                    $html .= displayArrayAsTable($value);
                }
            }
        } else {
            $html .= htmlspecialchars($value);
        }
        $html .= '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    return '<div class="table-responsive">'.$html.'</div>';
}
?>

