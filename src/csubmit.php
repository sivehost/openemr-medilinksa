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

$submitClaimUrl = $configpq->getSubmission();
$claimUrl = $configpq->getTrace();
$claimReverseUrl = $configpq->getReverse();
$clientId = $configpq->getClientId();

function sendClaimReversal($encounter,$refno) {
    global $claimReverseUrl;
    global $clientId;
    
    $token = getToken();
    if (!$token) {
        die("Failed to retrieve token.");
    }

// Check if a claim already exists for this encounter
$existingClaim = sqlQuery("SELECT id FROM medilinksa_claims WHERE encounter = ?", [$encounter]);

if ($existingClaim) {
    $claimId = $existingClaim['id'];
} else {
 die("Failed to retrieve claim.");
    }


$encountData = sqlQuery("SELECT * FROM `form_encounter` WHERE `encounter` = ? ORDER BY `id` DESC LIMIT 1", [$encounter]);
if (!$encountData) {die("Error: Encounter not found.");}    
    
    $providerData = sqlQuery("SELECT * FROM `users` WHERE `id` = ? ORDER BY `id` DESC LIMIT 1", [$encountData['provider_id']]);


$reversalNbr = sqlInsert("INSERT INTO medilinksa_claims_history  (encounter, claim_id)   VALUES (?, ?)",    [$encounter, $claimId]); 

 

    $requestData = [
    "Version" => substr("1.0b",0,4),
    "ClientId" => "".substr($clientId,0,4),
        "Reversal" => [
            [
                "ReversalNbr" => "".substr($reversalNbr,0,35),
                "ClaimReference" => "".substr($refno,0,30),
                "ClaimNbr" => "".substr($encounter,0,35),
                "Echo" => [
                        "EchoType" => "" . substr($encountData['id'],0,4),
                        "EchoData" => "" . substr($encountData['encounter'],0,100)
                        ]
            ]        
                        ]
                    ];

    $headers = [
        'Content-Type: application/json; version=1.0b',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];
    
    $ch = curl_init($claimReverseUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 501) {
        return submitClaimTrace($requestData);
    }
    
    $apiResponse = json_decode($response, true);
    
// Decode the API response
$responseJson = $response;
$stutu = "PA";
// Default values if no messages exist
$firstMessageType = 'Unknown';
$firstMessage = 'No message';
$typed = "DrCure";
// Extract the first message if available
if (isset($apiResponse['Reversal'][0]['ReveralReference'])) {
    $firstMessageType = $apiResponse['Reversal'][0]['ReversalResult'] ?? 'Unknown';
    $firstMessage = $apiResponse['Reversal'][0]['ClaimMessages'][0]['Message'] ?? 'No Message';
    $typed = $apiResponse['Reversal'][0]['Origin'] ?? 'Switch';
    $apiResponse['RefNo'] = $apiResponse['Reversal'][0]['ReveralReference'];
    $stutu = ($apiResponse['Reversal'][0]['ReversalResult'] == 'FA') ? 'RV':$apiResponse['Reversal'][0]['ReversalResult'];
    $stutu = ($apiResponse['Reversal'][0]['ReversalResult'] == 'RV') ? 'RV':$stutu;
    if($firstMessage == "Already reversed claim."){$stutu = "RV";}
    
}else{
$firstMessageType = $apiResponse['Reversal'][0]['ClaimMessages'][0]['MessageType'] ?? 'No MessageType';
$firstMessage = $apiResponse['Reversal'][0]['ClaimMessages'][0]['Message'] ?? 'No Message';
$typed = $apiResponse['Reversal'][0]['ClaimMessages'][0]['Origin'] ?? 'DrCure';
$apiResponse['RefNo'] = $apiResponse['Reversal'][0]['ReveralReference'] ?? '402';
$stutu = $apiResponse['Reversal'][0]['ReversalResult'] ?? 'PA';
}

// Update the claim record with response data, HTTP status, message type, and first message
sqlStatement("UPDATE medilinksa_claims SET status = ?, mtype = ?,message = ?, reverse_data = ? WHERE id = ?", 
    [$stutu, $firstMessageType, $firstMessage, $response, $claimId]);
    
sqlStatement("UPDATE medilinksa_claims_history 
    SET claim_data = ?, reverse_data = ?, refno = ?, httpcode = ?, 
        mtype = ?, status = ?, message = ?, type = ? 
    WHERE id = ?", [json_encode($requestData), $response, $apiResponse['RefNo'] ?? 402, 
     $httpCode ?? 402, $firstMessageType, $stutu, $firstMessage, $typed, $reversalNbr]);

    
    
    return $apiResponse;
}

function submitClaimTrace($encounter,$refno) {
    global $claimUrl;
    global $clientId;
    
    $token = getToken();
    if (!$token) {
        die("Failed to retrieve token.");
    }

// Check if a claim already exists for this encounter
$existingClaim = sqlQuery("SELECT id FROM medilinksa_claims WHERE encounter = ?", [$encounter]);

if ($existingClaim) {
    $claimId = $existingClaim['id'];
} else {
 die("Failed to retrieve claim.");
    }


$encountData = sqlQuery("SELECT * FROM `form_encounter` WHERE `encounter` = ? ORDER BY `id` DESC LIMIT 1", [$encounter]);
if (!$encountData) {die("Error: Encounter not found.");}    
    
    $providerData = sqlQuery("SELECT * FROM `users` WHERE `id` = ? ORDER BY `id` DESC LIMIT 1", [$encountData['provider_id']]);

$requestData = [
    "Version" => substr("1.0b",0,4),
    "ClientId" => "".substr($clientId,0,4),
    "Document" => [[
        "ClaimReference" => "".substr($refno,0,7),
        "RefNo" => "".substr($refno,0,30),
        "BhfNumber" => [
            "Supplier" => "".substr($providerData['upin'],0,7)
        ]
    ]]
];

    $headers = [
        'Content-Type: application/json; version=1.0b',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];
    
    $ch = curl_init($claimUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 501) {
        return submitClaimTrace($requestData);
    }
    
    $apiResponse = json_decode($response, true);

$kanidue = 0;
$paid2prov = 0;

foreach ($apiResponse["Documents"] as $document){
$kanidue = $kanidue + $document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["DueByScheme"];
$paid2prov = $paid2prov + $document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["PaidToProvider"];
}

// Decode the API response
$responseJson = $response;
$stutu = "PA";
// Default values if no messages exist
$firstMessageType = 'Unknown';
$firstMessage = 'No message';
$typed = "DrCure";
// Extract the first message if available
if (isset($apiResponse['ReqMessages'])) {
    $firstMessageType = $apiResponse['ReqMessages'][0]['MessageType'] ?? 'Unknown';
    $firstMessage = $apiResponse['ReqMessages'][0]['Message'] ?? 'No message';
    $typed = $apiResponse['ReqMessages'][0]['Origin'] ?? 'DrCure';
if($paid2prov >= $kanidue AND $kanidue > 0.01 ){$stutu = "PD";}    
}else{
$firstMessageType = $apiResponse['Documents'][0]['ResponseHeader']['ClaimMessages'][0]['MessageType'] ?? 'No MessageType';
$firstMessage = $apiResponse['Documents'][0]['ResponseHeader']['ClaimMessages'][0]['Message'] ?? 'No Message';
$typed = $apiResponse['Documents'][0]['ResponseHeader']['ClaimMessages'][0]['Origin'] ?? 'DrCure';
$apiResponse['RefNo'] = $apiResponse['Documents'][0]['ResponseHeader']['AuthReference']['ClaimReference'] ?? '402';
$stutu = $apiResponse['Documents'][0]['ResponseHeader']['Header']['ClaimResult'] ?? 'PA';
if($paid2prov >= $kanidue AND $kanidue > 0.01 ){$stutu = "PD";}
}

// Update the claim record with response data, HTTP status, message type, and first message
sqlStatement("UPDATE medilinksa_claims SET due = ?, paid = ?, status = ?, trace_data = ? WHERE id = ?", 
    [$kanidue, $paid2prov, $stutu, $response, $claimId]);
    
$insertId = sqlInsert("INSERT INTO medilinksa_claims_history 
    (due, paid, encounter, claim_data, trace_data, refno, httpcode, mtype, status, message, type, claim_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
    [$kanidue, $paid2prov, $encounter, json_encode($requestData), $response, $apiResponse['RefNo'] ?? 402, 
     $httpCode ?? 402, $firstMessageType, $stutu, $firstMessage, $typed, $claimId]); 
    
    
    return $apiResponse;
}



function submitClaim($requestData) {
    global $submitClaimUrl;
    
    $token = getToken();
    if (!$token) {
        die("Failed to retrieve token.");
    }
    
    $headers = [
        'Content-Type: application/json; version=1.0b',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];
    
    $ch = curl_init($submitClaimUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 501) {

        return submitClaim($requestData);
    }
    
//    sqlStatement("UPDATE medilinksa_claims SET response_data = '".$response."', httpcode = '".$httpCode."' WHERE encounter = 10");
// Decode response JSON
    $decodedResponse = json_decode($response, true);

    // Ensure it's an array before appending HTTP code
    if (!is_array($decodedResponse)) {
        $decodedResponse = [];
    }

    // Append the HTTP code
    $decodedResponse['httpcode'] = $httpCode;

    return $decodedResponse;
}

function billencounter($encounter, $claimtype = "NW"){

global $clientId;
$imali = 0.00;
    // Mark all items in billing table as billed
    sqlStatement("UPDATE billing SET billed = 1 WHERE encounter = ?", [$encounter]);
    
    $encountData = sqlQuery("SELECT * FROM `form_encounter` WHERE `encounter` = ? ORDER BY `id` DESC LIMIT 1", [$encounter]);
if (!$encountData) {die("Error: Encounter not found.");}    
    
    $providerData = sqlQuery("SELECT * FROM `users` WHERE `id` = ? ORDER BY `id` DESC LIMIT 1", [$encountData['provider_id']]);
  //  $RefproviderData = sqlQuery("SELECT * FROM `users` WHERE `id` = ? ORDER BY `id` DESC LIMIT 1", [$encountData['referring_provider_id']]); //required for specialists
    $patientData = sqlQuery("SELECT * FROM `patient_data` WHERE `pid` = ? ORDER BY `pid` DESC LIMIT 1", [$encountData['pid']]);
    $insuranceData = sqlQuery("SELECT * FROM `insurance_data` WHERE `type` = 'primary' AND `pid` = ? ORDER BY `pid` DESC LIMIT 1", [$encountData['pid']]);  


    // Fetch claim data from billing table
    $billingItems = sqlStatement("SELECT * FROM billing WHERE encounter = ? ORDER BY id ASC", [$encounter]);
    $claimData = [];
    $itum = [];
    $cldie = [];
$cala = 1;
$tibalo = 1;
$sinangaki = 0;
$typ = array("Tariff" => "1", "Consultation" => "1", "Modifier" => "2", "Material" => "3", "Medicine" => "4");
    while ($item = sqlFetchArray($billingItems)) {
    if($item['code_type'] == "ICD10"){
        $cldie[] = 
                    [
                        "Level" => substr(($cala == 1) ? "01" : "02",0,2),
                        "Code" => substr($item['code'],0,10)
                    ];
                    $cala = 8;
                } else{
                if($item['code_type'] == "TAX"){continue;}
                $imali = $imali + $item['fee'];
$providxData = sqlQuery("SELECT * FROM `users` WHERE `id` = ? ORDER BY `id` DESC LIMIT 1", [$item['provider_id']]);

// Given text input
$icd10Text = $item['justify'];
$modies = $item['modifier'];

// Split the input string into an array of codes
$codes = explode(":", $icd10Text);
$modes = explode(":", $modies);

$diagnosisArray = [];
$modis = [];
$first = true; // Track first element

foreach ($modes as $mcode) {
    // Extract the actual ICD10 code (remove 'ICD10|')
    $icdCodem = explode("|", $mcode) ?? '';
//M3|Description|Quantity|Units|UnitTp
    if (!empty($icdCodem)) {
        $modis[] = [
            "Modifier" => substr($icdCodem[0] ?? '',0,15),
            "Description" => substr($icdCodem[1] ?? '',0,50),
            "Quantity" => substr($icdCodem[2] ?? '',0,10),
            "Units" => substr($icdCodem[3] ?? '',0,7),
            "UnitTp" => substr($icdCodem[4] ?? '',0,2),       
        ];
    }else{$modis = [""];}
}

foreach ($codes as $code) {
    // Extract the actual ICD10 code (remove 'ICD10|')
    $icdCode = explode("|", $code)[1] ?? '';

    if (!empty($icdCode)) {
        $diagnosisArray[] = [
            "Level" => substr($first ? "01" : "02",0,2),
            "Code" => substr($icdCode,0,10)
        ];
        $first = false; // Set to false after first iteration
    }
}


        $itum[] = [
                    "ItemHeader" => [
                        "ItemNumber" => substr("$tibalo",0,4),
                        "Type" => substr($typ[$item['code_type']],0,2),
                        "PaymentAdvice" => substr("P",0,1),
                        "ItemAuth" => substr($item['notecodes'],0,30)
                    ],
                    "Echo" => [
                        [
                            "EchoType" => substr("" . $item['id'],0,4),
                            "EchoData" => substr($item['date'],0,100)
                        ]
                    ],
                    "Procedure" => [
                        "Tariff" => substr($item['code'],0,15),
                        "Description" => substr($item['code_text'], 0, 50),
                        "Quantity" => substr("1",0,10),
                        "Units" => substr("" . $item['units'],0,7),
                        "UnitTp" => substr("06",0,2)
                    ],
                    "Modifier" => $modis,                    
                    "Treatment" => [
                        "StartDate" => substr(date("Ymd", strtotime($encountData['date'])),0,8),
                        "StartTime" => substr(date("His", strtotime($encountData['date'])),0,6),
                        "EndDate" => substr(date("Ymd", strtotime($encountData['onset_date'])),0,8),
                        "EndTime" => substr(date("His", strtotime($encountData['onset_date'])),0,6)
                    ],
                    "Diagnosis" => $diagnosisArray,
                    "BhfNumberItem" => [
                        "Treating" => substr($providxData['upin'],0,7)
                    ],
                    "HpcItem" => [
                        "Treating" => substr($providxData['npi'] ?? "",0,13)
                    ],
                    "ItemFinancials" => [
                        "Total" => substr(number_format($item['fee'], 2, '.', ''),0,9)
                    ]
                ];
        
        $tibalo += 1;
        }
        
    }


$hospitalIndicator = in_array($encountData['class_code'], [11, 12]) ? "OUT" : "IN";

$claimData = [
    "ClientId" => substr($clientId,0,4),
    "Version" => substr("1.0b",0,4),
    "Document" => [
        [
            "ClaimHeader" => [
                "Header" => [
                    "ProviderType" => substr($providerData['physician_type'],0,3),
                    "SupplierName" => substr($providerData['lname'],0,40),
                    "ClaimType" => substr($claimtype,0,2),
                    "ClaimNbr" => substr($encounter,0,35),
                    "Option" => substr($insuranceData['medilinksa_desti_code'],0,4),
                    "Country" => substr($insuranceData['subscriber_country'],0,2),
                    "Sector" => substr("Private",0,9), //Public or Private
                    "Vendor" => substr("DrCure",0,30),
                    "VendorVersion" => substr("7.0.2",0,20),
                    "PcNbr" => substr($_SESSION['authUser'], 0, 10),
                    "WksNbr" => substr($_SESSION['authUser'], 0, 10),
                    "PlaceOfService" => substr($encountData['class_code'],0,3),
                    "NumberOfItems" => substr("". count($itum),0,4),
                    "HospitalIndicator" => substr($hospitalIndicator[0],0,1)
                ],
                "BhfNumber" => [
                    "Supplier" => substr($providerData['upin'],0,7),
                //    "Prescribing" => substr($providerData['upin'],0,7),     // required for dispensing claims             
               //     "Referring" => substr($RefproviderData['upin'],0,7), //required for specialists
               //     "Admitting" => substr($RefproviderData['upin'],0,7), //required for specialists               
                    "Treating" => substr($providerData['upin'],0,7)
                ],
                "HpcNumber" => [
                    "Supplier" => substr($providerData['npi'],0,13),
                //    "Prescribing" => substr($providerData['npi'],0,13),     // required for dispensing claims             
               //     "Referring" => substr($RefproviderData['npi'],0,13), //required for specialists                    
                    "Treating" => substr($providerData['npi'],0,13)
                ],
                "Date" => [
                    "Created" => substr(date("Ymd", strtotime($encountData['date'])),0,8),
                    "Service" => substr(date("Ymd", strtotime($encountData['date'])),0,8)
                ],
                "Echo" => [
                    [
                        "EchoType" => substr("" . $encountData['id'],0,4),
                        "EchoData" => substr($encountData['encounter'],0,100)
                    ]
                ],
                "Member" => [
                    "MemberNumber" => substr($insuranceData['policy_number'],0,50),
                    "Title" => substr("Mr/Mrs",0,5),
                    "Surname" => substr($insuranceData['subscriber_lname'],0,40),
                    "Initials" => substr($insuranceData['subscriber_fname'][0].$insuranceData['subscriber_mname'][0],0,5),
                    "IdNumber" => substr($insuranceData['subscriber_ss'],0,20)
                ],
                "Patient" => [
                    "Initials" => substr($patientData['fname'][0].$patientData['mname'][0],0,5),
                    "Firstname" => substr($patientData['fname'],0,40),
                    "Surname" => substr($patientData['lname'],0,40),
                    "Dependant" => substr($insuranceData['medilinksa_dep_code'],0,3),
                    "BirthDate" => substr(date("Ymd", strtotime($patientData['DOB'])),0,8),
                    "IdNumber" => substr($patientData['ss'],0,20),
                    "Gender" => substr($patientData['sex'][0] ?? "U",0,1),
                    "Relation" => substr($insuranceData['subscriber_relationship'],0,1),
                    "NewBorn" => substr("N",0,1),
                    "Race" => substr("W",0,1)
                ],
                "Address" => [
                    "Line1" => substr($patientData['street'],0,30),
                    "Line2" => substr($patientData['street_line_2'],0,30),
                    "Suburb" => substr($patientData['county'],0,30),
                    "City" => substr($patientData['city'],0,30),
                    "Postal" => substr($patientData['postal_code'],0,6)
                ],
                "ClaimFinancials" => [
                    "Total" => substr(number_format($imali, 2, '.', ''),0,9)
                ],
                "Account" => [
                    "AccountNbr" => substr($patientData['pubpid'],0,30),
                    "ClaimAuth" => substr($encountData['reason'],0,30),
                    "HospitalIndicator" => substr($hospitalIndicator,0,3)
                ],
                "ClaimDiagnosis" => $cldie
              ],
                "Item" => $itum
            ]
        ]
    ];
    

    // Insert claim into medilinksa_claims table
$claimJson = json_encode($claimData);

// Check if a claim already exists for this encounter
$existingClaim = sqlQuery("SELECT id FROM medilinksa_claims WHERE encounter = ?", [$encounter]);

if ($existingClaim) {
    // Update the existing claim
    sqlStatement("UPDATE medilinksa_claims SET claim_data = ?, httpcode = 0 WHERE encounter = ?", [$claimJson, $encounter]);
    $claimId = $existingClaim['id'];
} else {
    // Insert new claim if none exists
    $claimId = sqlInsert("INSERT INTO medilinksa_claims (encounter, claim_data, httpcode, type) VALUES (?, ?, 0, 'DrCure')", [$encounter, $claimJson]);
}



    // Submit claim to Medilink API
    $apiResponse = submitClaim($claimData);

// Decode the API response
$responseJson = json_encode($apiResponse);
$stutu = "PA";
// Default values if no messages exist
$firstMessageType = 'Unknown';
$firstMessage = 'No message';
$typed = "DrCure";
// Extract the first message if available
if (isset($apiResponse['ReqMessages'])) {
    $firstMessageType = $apiResponse['ReqMessages'][0]['MessageType'] ?? 'Unknown';
    $firstMessage = $apiResponse['ReqMessages'][0]['Message'] ?? 'No message';
    $typed = $apiResponse['ReqMessages'][0]['Origin'] ?? 'DrCure';
    
}else{
$firstMessageType = $apiResponse['Documents'][0]['ResponseHeader']['ClaimMessages'][0]['MessageType'] ?? 'No MessageType';
$firstMessage = $apiResponse['Documents'][0]['ResponseHeader']['ClaimMessages'][0]['Message'] ?? 'No Message';
$typed = $apiResponse['Documents'][0]['ResponseHeader']['ClaimMessages'][0]['Origin'] ?? 'DrCure';
$apiResponse['RefNo'] = $apiResponse['Documents'][0]['ResponseHeader']['AuthReference']['ClaimReference'] ?? '402';
$stutu = $apiResponse['Documents'][0]['ResponseHeader']['Header']['ClaimResult'] ?? 'PA';
}

// Update the claim record with response data, HTTP status, message type, and first message
sqlStatement("UPDATE medilinksa_claims SET due =?, response_data = ?,refno = ?, httpcode = ?, mtype = ?, status = ?, message = ?, type = ? WHERE id = ?", 
    [$imali,json_encode($apiResponse),$apiResponse['RefNo'] ?? 402, $apiResponse['httpcode'] ?? 402, $firstMessageType, $stutu, $firstMessage, $typed, $claimId]);
    
$insertId = sqlInsert("INSERT INTO medilinksa_claims_history 
    (due, encounter, claim_data, response_data, refno, httpcode, mtype, status, message, type, claim_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",  // Now we have 10 placeholders
    [$imali, $encounter, $claimJson, json_encode($apiResponse), $apiResponse['RefNo'] ?? 402, 
     $apiResponse['httpcode'] ?? 402, $firstMessageType, $stutu, $firstMessage, $typed, $claimId]  // 10 values
);


  


}


?>

