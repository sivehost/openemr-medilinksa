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
$config = $bootstrap->getGlobalConfig();

$username = $config->getUsername();
$password = $config->getPassword();
$grant_type = ($config->getGrantType())?'password':'';
$authUrl = $config->getTokens();

function getToken() {
 
    
$sql = "SELECT * FROM medilinksa_tokens ORDER BY created_at DESC LIMIT 1";
$result = sqlStatement($sql);

if ($result) {
    // Get the token data as an associative array
    $tokenData = sqlFetchArray($result);
    
    // Check if the token exists and if it hasn't expired
    if (isset($tokenData['access_token']) && strtotime($tokenData['expires']) > time()) {
        return $tokenData['access_token'];
    }
}
    
    return fetchNewToken();
}

function fetchNewToken() {
    global $authUrl, $username, $password, $grant_type;
    
    $postData = http_build_query([
        'grant_type' => $grant_type,
        'username' => $username,
        'password' => $password
    ]);
    
    $headers = [
        'Content-Type: application/www-form-url-encoded; version=1.0b',
        'Accept: application/json'
    ];
    
    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
    $tokenData = json_decode($response, true);
// Ensure we got valid token data
if ($tokenData && isset($tokenData['access_token'])) {
    // Prepare the SQL to insert the token data into the database.
    // Map the JSON keys to your DB columns:
    // - access_token and token_type are taken directly.
    // - userid is taken from the JSON.
    // - issued and expires are stored in JSON as '.issued' and '.expires'
    $sql = "INSERT INTO medilinksa_tokens (access_token, token_type, userid, issued, expires)
            VALUES (?, ?, ?, ?, ?)";
    
    // Execute the statement with the respective values.
    // (Adjust the parameters if your sqlStatement function differs.)
    sqlStatement($sql, [
        $tokenData['access_token'],
        $tokenData['token_type'],
        $tokenData['userid'],
        $tokenData['.issued'],
        $tokenData['.expires']
    ]);
    
    // Return the access token for further use
    return $tokenData['access_token'];
}
    }
    
    error_log("Failed to fetch token. HTTP Code: $httpCode. Response: $response");
    return null;
}

?>
