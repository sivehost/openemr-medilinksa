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

    require_once "../../../../globals.php";
    require_once "../src/token.php";    
    require_once "../src/pquery.php";    


    use OpenEMR\Common\Acl\AclMain;
    use OpenEMR\Common\Twig\TwigContainer;
    use OpenEMR\Modules\MedilinkSA\ClaimsPage;

    $tab = "patient";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Patient")]);
    exit;
}
?>

<html>
    <head>
        <link rel="stylesheet" href="../../../../../public/assets/bootstrap/dist/css/bootstrap.min.css">
    </head>
    <title><?php echo xlt("Patient"); ?></title>
    <body>
        <div class="row"> 
            <div class="col">
            <?php
                require '../templates/navbar.php';
            ?>
            </div>
        </div>
<form method="post" action="patient.php" id="PatientForm">
    <div class="card p-3">  
        <div class="row">
            <!-- RSA ID Number or Passport -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="ssn"><?php echo xlt("RSA ID Number or Passport"); ?></label>
                    <input type="text" class="form-control" id="ssn" name="ssn" 
                           value="<?php echo isset($_POST['ssn']) ? attr($_POST['ssn']) : '' ?>" 
                           placeholder="<?php echo xla("Patient ID Number"); ?>"/>
                </div>
            </div>

            <!-- Dependant Code -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="depcode"><?php echo xlt("Dependant Code"); ?></label>
                    <input type="text" class="form-control" id="depcode" name="depcode" 
                           value="<?php echo isset($_POST['depcode']) ? attr($_POST['depcode']) : '' ?>" 
                           placeholder="<?php echo xla("Patient Dependant Code"); ?>"/>
                </div>
            </div>

            <!-- Medical Aid Member Number -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="maidn"><?php echo xlt("Medical Aid Member Number"); ?></label>
                    <input type="text" class="form-control" id="maidn" name="maidn" 
                           value="<?php echo isset($_POST['maidn']) ? attr($_POST['maidn']) : '' ?>" 
                           placeholder="<?php echo xla("Patient Medical Aid Number"); ?>"/>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Medical Aid Scheme Name -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="optionc"><?php echo xlt("Medical Aid Scheme Name"); ?></label>
                    <select class="form-control" id="optionc" name="optionc">
                        <option value=""><?php echo xla("Choose"); ?></option>
                        <?php
                        $sql = "SELECT desti_code, scheme_name FROM medilinksa_schemes";
                        $resulx = sqlStatement($sql);
                        while ($row = sqlFetchArray($resulx)) {
                            $selected = (isset($_POST['optionc']) && $_POST['optionc'] == $row['desti_code']) 
                                        ? ' selected="selected"' : '';
                            echo '<option value="' . attr($row['desti_code']) . '"' . $selected . '>' 
                                . htmlspecialchars($row['scheme_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Medical Aid Plan Name -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="pname"><?php echo xlt("Medical Aid Member Plan Name"); ?></label>
                    <input type="text" class="form-control" id="pname" name="pname" 
                           value="<?php echo isset($_POST['pname']) ? attr($_POST['pname']) : '' ?>" 
                           placeholder="<?php echo xla("Medical Aid Plan"); ?>"/>
                </div>
            </div>
        </div>

        <!-- Submit Button Row -->
        <div class="row mt-3">
            <div class="col-md-6">
                <button type="submit" name="SubmitButton" class="btn btn-primary btn-block" id="submitBtn">
                    <?php echo xlt("Submit"); ?>
                </button>
            </div>
            <div class="col-md-6 text-center">
                <!-- Loading message -->
                <div id="loadingMessage" style="display: none; margin-top: 10px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only"><?php echo xlt("Loading..."); ?></span>
                    </div>
                    <span><?php echo xlt("Submitting, please wait..."); ?></span>
                </div>
            </div>
        </div>            
    </div> 
</form>


<script>
// When the form is submitted, disable the button and show the loading message.
document.getElementById('membershipForm').addEventListener('submit', function() {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('loadingMessage').style.display = 'block';
});
</script>



        <?php
            $datas = [];
            
if (!empty($_POST['maidn']) && !empty($_POST['ssn']) && $_POST['optionc'] !='Choose') { //check if form was submitted
           
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ssn = $_POST['ssn'] ?? '';
    $depcode = $_POST['depcode'] ?? '';
    $maidn = $_POST['maidn'] ?? '';
    $optionc = $_POST['optionc'] ?? '';
    $pname = $_POST['pname'] ?? '';    

    // Check if SSN exists in patient_data table
    $query = "SELECT pid, fname, lname, DOB, ss, phone_home FROM patient_data WHERE ss = ? LIMIT 1";
    //$result = sqlQuery($query, [$ssn]);
    $result = sqlFetchArray(sqlStatement($query, [$ssn])) ?: [];

    if ($result) {
        $pid = $result['pid'];
        
        // Check if primary insurance exists for the patient
        $insuranceQuery = "SELECT provider, subscriber_lname, subscriber_fname, policy_number, medilinksa_dep_code, medilinksa_desti_code 
                           FROM insurance_data WHERE pid = ? AND type = 'primary'";
        $insuranceData = sqlQuery($insuranceQuery, [$pid]);
        
        if ($insuranceData) {
            // Update the existing insurance data
$updateQuery = "UPDATE insurance_data SET 
                medilinksa_dep_code = COALESCE(NULLIF(?, ''), medilinksa_dep_code),
                medilinksa_desti_code = COALESCE(NULLIF(?, ''), medilinksa_desti_code),
                policy_number = COALESCE(NULLIF(?, ''), policy_number),
                plan_name = COALESCE(NULLIF(?, ''), plan_name)
                WHERE pid = ? AND type = 'primary'";

sqlStatement($updateQuery, [$depcode, $optionc, $maidn, $pname, $pid]);

        } else {
            // Insert a new primary insurance record if it doesn't exist
            $insertQuery = "INSERT INTO insurance_data (pid, type, medilinksa_dep_code, medilinksa_desti_code, policy_number, plan_name) 
                            VALUES (?, 'primary', ?, ?, ?, ?)";
            sqlStatement($insertQuery, [$pid, $depcode, $optionc, $maidn, $pname]);
            
            // Retrieve the newly inserted insurance data
            $insuranceData = sqlQuery($insuranceQuery, [$pid]);
        }
        // Fetch the insurance provider name
        $providerName = $insuranceData['provider'];
        if (!empty($insuranceData['provider'])) {
            $providerQuery = "SELECT name FROM insurance_companies WHERE id = ? LIMIT 1";
            $providerResult = sqlQuery($providerQuery, [$insuranceData['provider']]);
            $providerName = $providerResult['name'] ?? 'N/A';
        }
        
        } else {
        // Redirect to add patient screen if SSN is not found
         exit("Patient with ID number $ssn not found. Please go create the patient first, then come back here.");
    }
}


        }
        if (empty($response)) {
            echo xlt("No results found");
        } else { 
        
// Output the table
// Retrieve the updated insurance data
        $insuranceQuery = "SELECT provider, subscriber_lname, subscriber_fname, policy_number, plan_name, medilinksa_dep_code, medilinksa_desti_code 
                           FROM insurance_data WHERE pid = ? AND type = 'primary'";
        //$insuranceData = sqlQuery($insuranceQuery, [$pid]);
        $insuranceData = sqlFetchArray(sqlStatement($insuranceQuery, [$pid])) ?: [];
if(!empty($result) AND !empty($insuranceData)){        
        // Display results in a responsive table
echo '<div class="container-fluid mt-4">'; // Use container-fluid for full-width responsiveness
echo '<h3>Details</h3>';
echo '<div class="table-responsive">'; // Only one table-responsive wrapper
echo '<table class="table table-bordered table-hover">'; // Added table-hover for better readability
echo '<thead class="table-dark">'; // Dark header for visibility
echo '<tr>
        <th>Patient ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>DOB</th>
        <th>Phone</th>
        <th>Medical Aid Provider</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Medical Aid Member Number</th>
        <th>Medical Aid Plan</th>                
        <th>Dependant Code</th>
        <th>Destination Code</th>
      </tr></thead>';
echo '<tbody>';
echo '<tr>
        <td>' . htmlspecialchars($result['ss']) . '</td>
        <td>' . htmlspecialchars($result['fname']) . '</td>
        <td>' . htmlspecialchars($result['lname']) . '</td>
        <td>' . htmlspecialchars($result['DOB']) . '</td>
        <td>' . htmlspecialchars($result['phone_home']) . '</td>
        <td>' . htmlspecialchars($providerName) . '</td>
        <td>' . htmlspecialchars($insuranceData['subscriber_fname']) . '</td>
        <td>' . htmlspecialchars($insuranceData['subscriber_lname']) . '</td>
        <td>' . htmlspecialchars($insuranceData['policy_number']) . '</td>
        <td>' . htmlspecialchars($insuranceData['plan_name']) . '</td>                
        <td>' . htmlspecialchars($insuranceData['medilinksa_dep_code']) . '</td>
        <td>' . htmlspecialchars($insuranceData['medilinksa_desti_code']) . '</td>
      </tr>';
echo '</tbody></table></div></div>'; 
    
        }
                }
        ?>      
        
    </body>
</html>

