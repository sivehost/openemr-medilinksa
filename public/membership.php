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

    $tab = "membership";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Membership")]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Membership</title>
        <link rel="stylesheet" href="../../../../../public/assets/bootstrap/dist/css/bootstrap.min.css">
</head>
<body>
        <div class="row"> 
            <div class="col">
            <?php
                require '../templates/navbar.php';
            ?>
            </div>
        </div>

<form method="post" action="membership.php" id="membershipForm">
    <div class="card p-3">  
        <div class="row">
            <!-- Medical Aid Member Number -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="maidn"><?php echo xlt("Medical Aid Member Number"); ?></label>
                    <input type="text" class="form-control" id="maidn" name="maidn" 
                           value="<?php echo isset($_POST['maidn']) ? attr($_POST['maidn']) : '' ?>" 
                           placeholder="<?php echo xla("Patient Medical Aid Number"); ?>"/>
                </div>
            </div>

            <!-- Medical Aid Scheme Name -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="optionc"><?php echo xlt("Medical Aid Scheme Name"); ?></label>
                    <select class="form-control" id="optionc" name="optionc">
                        <option value=""><?php echo xla("Choose"); ?></option>
                        <?php
                        // Query the medilinksa_schemes table for desti_code and scheme_name.
                        $sql = "SELECT desti_code, scheme_name FROM medilinksa_schemes";
                        $result = sqlStatement($sql);
                        while ($row = sqlFetchArray($result)) {
                            $selected = (isset($_POST['optionc']) && $_POST['optionc'] == $row['desti_code']) 
                                        ? ' selected="selected"' : '';
                            echo '<option value="' . attr($row['desti_code']) . '"' . $selected . '>' 
                                . htmlspecialchars($row['scheme_name']) . '</option>';
                        }
                        ?>
                    </select>
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
        if (!empty($_POST['maidn']) && !empty($_POST['optionc']) && $_POST['optionc'] !='Choose') { //check if form was submitted
           
            
$requestData = [
    "ClientId" => "1001",
    "Version" => "1",
    "Query" => [[
        "RequestType" => "MQ",
        "RequestId" => "3534534",
        "BhfNumber" => "1447130",
        "MemberNumber" => $_POST['maidn'],
        "Option" => $_POST['optionc'],
        //"Echo" => [[
            //"EchoType" => "ID",
            //"EchoData" => "ABC"
        //]]
    ]]
];

$response = queryPatient($requestData);

        }
        if (empty($response)) {
            echo xlt("No results found");
        } else { 
        
// Output the table
echo '<div class="table-responsive">';
echo displayArrayAsTable($response);
echo '</div>';
        
                }
        ?>      
        
    </body>
</html>

