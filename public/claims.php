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
    require_once "../src/csubmit.php";

    use OpenEMR\Common\Acl\AclMain;
    use OpenEMR\Common\Twig\TwigContainer;
    use OpenEMR\Modules\MedilinkSA\ClaimsPage;

    $tab = "claims";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Claims")]);
    exit;
}
?>

<html>
    <head>
        <link rel="stylesheet" href="../../../../../public/assets/bootstrap/dist/css/bootstrap.min.css">
    </head>
    <title><?php echo xlt("Claims"); ?></title>
    <body>
        <div class="row"> 
            <div class="col">
            <?php
                require '../templates/navbar.php';
            ?>
            </div>
        </div>
<form method="post" action="claims.php">
    <div class="card p-3">  
        <div class="row">
            <!-- Start Date -->
            <div class="col-md-3">
                <div class="form-group">
                    <label for="start_date"><?php echo xlt("Start Date"); ?></label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo isset($_POST['start_date']) ? attr($_POST['start_date']) : '' ?>" 
                           placeholder="yyyy-mm-dd"/>
                </div>
            </div>    

            <!-- End Date -->
            <div class="col-md-3">
                <div class="form-group">
                    <label for="end_date"><?php echo xlt("End Date"); ?></label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo isset($_POST['end_date']) ? attr($_POST['end_date']) : '' ?>" 
                           placeholder="yyyy-mm-dd"/>
                </div>
            </div>

            <!-- Claim Status -->
            <div class="col-md-3">
                <div class="form-group">
                    <label for="status"><?php echo xlt("Claim Status"); ?></label>
                    <select class="form-control" id="status" name="status">
                        <option value=""><?php echo xla("Choose"); ?></option>
                        <?php
                        $sql = "SELECT status FROM medilinksa_claims GROUP BY status";
                        $result = sqlStatement($sql);
                        while ($row = sqlFetchArray($result)) {
                            $selected = (isset($_POST['status']) && $_POST['status'] == $row['status']) ? ' selected="selected"' : '';
                            echo '<option value="' . attr($row['status']) . '"' . $selected . '>' . htmlspecialchars($row['status']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Claim Reference Number -->
            <div class="col-md-3">
                <div class="form-group">
                    <label for="refno"><?php echo xlt("Claim Reference Number"); ?></label>
                    <input type="text" class="form-control" id="refno" name="refno"  
                           value="<?php echo isset($_POST['refno']) ? attr($_POST['refno']) : '' ?>"  
                           placeholder="<?php echo xla("Claim Reference Number"); ?>"/>
                </div>
            </div>                        
        </div>

        <!-- Submit Button Row -->
        <div class="row mt-3">
            <div class="col text-right">
                <button type="submit" name="SubmitButton" class="btn btn-primary">
                    <?php echo xlt("Submit"); ?>
                </button>
            </div>
        </div>            
    </div> 
</form>


        <?php
            $datas = [];
        if (isset($_POST['SubmitButton'])) { //check if form was submitted
            $datas = array("AVAILABLE"=>"HERE");
// Capture form input (use NULL as default to prevent errors)
$startDate = $_POST['start_date'] ?? null;  // Format: YYYY-MM-DD
$endDate = $_POST['end_date'] ?? null;      // Format: YYYY-MM-DD
$status = $_POST['status'] ?? null;         // Example: 'pending', 'submitted'
$refno = $_POST['refno'] ?? null;           // Example: '2456058688'


// Start building the SQL query
$sql = "
    SELECT b.encounter, b.pid, p.fname, p.lname, 
           e.date AS encounter_date, SUM(b.fee) AS total_amount, 
           mc.type AS type, mc.status AS status, mc.refno AS refno, mc.message AS message, mc.refno AS refno, mc.created_at AS created_at, mc.mtype AS mtype, mc.due AS due, mc.paid AS paid
    FROM billing b
    JOIN patient_data p ON b.pid = p.pid
    JOIN form_encounter e ON b.encounter = e.encounter
    JOIN medilinksa_claims mc ON b.encounter = mc.encounter
    WHERE b.billed = 1
";

// Prepare an array to hold SQL parameters
$params = [];

// Apply **date range filtering** if both `startDate` and `endDate` are set
if (!empty($startDate) && !empty($endDate)) {

$startDate = isset($_POST['start_date']) && !empty($_POST['start_date']) 
    ? date('Y-m-d', strtotime($_POST['start_date'])) 
    : null;

$endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) 
    ? date('Y-m-d', strtotime($_POST['end_date'])) 
    : null;

    $sql .= " AND DATE(mc.created_at) BETWEEN ? AND ? ";
    $params[] = $startDate;
    $params[] = $endDate;
}

// Apply **status filtering** if `$status` is set
if (!empty($status)) {
    $sql .= " AND mc.status = ? ";
    $params[] = $status;
}

// Apply **refno filtering** if `$refno` is set
if (!empty($refno)) {
    $sql .= " AND mc.refno = ? ";
    $params[] = $refno;
}

// Ensure proper grouping
$sql .= " GROUP BY b.encounter ORDER BY e.date DESC";

// Execute the SQL query
$encounterBilling = sqlStatement($sql, $params);

$claimtype = "NW"; //NW = New Send or RS = Resend                                                                                                                                 
    
echo '<div class="container mt-4">';
echo '<h2>Claims</h2>';
echo '<div class="table-responsive">';
echo '<table class="table table-striped">';
echo '<thead class="thead-light">
        <tr>
            <th>Patient</th>
            <th>RefNo</th>                   
            <th>Date</th>
            <th>Number</th>            
            <th>Due</th>
            <th>Paid</th>            
            <th>From</th>            
            <th>Status</th>            
            <th>MType</th>                    
            <th>Message</th>           
            <th>Actions</th>
        </tr>
      </thead>';
echo '<tbody>';

while ($encounter = sqlFetchArray($encounterBilling)) {
if($encounter['total_amount'] < 0.0000000000001){continue;}
    $encounterId = htmlspecialchars($encounter['encounter']);

    // Main row (collapsed)
echo '<tr class="clickable" data-toggle="collapse" data-target="#collapse_' . htmlspecialchars($encounterId) . '">
    <td>' . htmlspecialchars($encounter['fname'] . ' ' . $encounter['lname']) . '</td>
    <td>' . htmlspecialchars($encounter['refno']??"none") . '</td>    
    <td>' . htmlspecialchars(!empty($encounter['created_at']) ? date("d/m/Y", strtotime($encounter['created_at'])) : "N/A") . '</td>
    <td>' . htmlspecialchars($encounter['encounter']) . '</td>                    
    <td>' . htmlspecialchars(number_format($encounter['due'], 2)) . '</td>
    <td>' . htmlspecialchars(number_format($encounter['paid'], 2)) . '</td>    
    <td>' . htmlspecialchars($encounter['type']) . '</td>    
<td>';

    // PHP for status mapping
    $statusMap = [
        "FA" => "Full Acceptance",
        "PA" => "Partial Acceptance",
        "RV" => "Reversed",
        "RJ" => "Rejected",
        "PD" => "PAID"        
    ];
    echo isset($statusMap[$encounter['status']]) ? $statusMap[$encounter['status']] : htmlspecialchars($encounter['status']);

echo '</td> 

    <td>' . htmlspecialchars($encounter['mtype']) . '</td>        
    <td>' . htmlspecialchars($encounter['message']) . '</td>   
    <td>';

if (!$encounter['status']) {

    echo '<button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?encounter=' . htmlspecialchars($encounterId) . '">
        Resend
    </button>'; $claimtype = "NW";
} elseif ($encounter['status'] == "RJ") {
echo '<div class="d-flex gap-2">
        <button class="btn btn-warning btn-sm process-btn" 
            data-href="claims.php?revcounter=' . htmlspecialchars($encounterId) . '&refc=' . htmlspecialchars($encounter['refno']) . '">
            Reverse
        </button>
        <button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?encounter=' . htmlspecialchars($encounterId) . '">
            Retry
        </button>
      </div>';
$claimtype = "RS";
} elseif ($encounter['status'] == "RV") {
    echo '<button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php">
        Refresh
    </button>';
} elseif ($encounter['status'] == "FA" OR $encounter['status'] == "PA") {

echo '<div class="d-flex gap-2">
        <button class="btn btn-warning btn-sm process-btn" 
            data-href="claims.php?revcounter=' . htmlspecialchars($encounterId) . '&refc=' . htmlspecialchars($encounter['refno']) . '">
            Reverse
        </button>
        <button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?counterd=' . htmlspecialchars($encounterId) . '&refno=' . htmlspecialchars($encounter['refno']) . '">
            Trace
        </button>
      </div>'; 
}  else {

    echo '<button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?dencounter=' . htmlspecialchars($encounterId) . '">
       Complete
    </button>';
}

echo '</td></tr>';


    // Fetch individual billing items for this encounter
    $billingDetails = sqlStatement("
        SELECT b.code,b.code_text, b.code_type, b.fee 
        FROM billing b 
        WHERE b.billed = 1 AND b.activity <> 0 AND b.encounter = ?", [$encounterId]);

    // Expanded row (hidden initially)
    echo '<tr id="collapse_' . $encounterId . '" class="collapse">
            <td colspan="4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>';

    while ($item = sqlFetchArray($billingDetails)) {
        echo '<tr>
                <td>' . htmlspecialchars($item['code']) . '</td>
                <td>' . htmlspecialchars($item['code_type']) . '</td>
                <td>' . htmlspecialchars(($item['code_type']!="ICD10")?number_format($item['fee'], 2):"") . '</td>
              </tr>';
    }

    echo '      </tbody>
                </table>
            </td>
          </tr>';
}

echo '</tbody></table></div></div>';

        }
        if (empty($datas)) {
            
$encounterBilling = sqlStatement("
    SELECT b.encounter, b.pid, p.fname, p.lname, 
           e.date AS encounter_date, SUM(b.fee) AS total_amount, mc.type AS type, mc.status AS status, mc.message AS message, mc.refno AS refno, mc.created_at AS created_at, mc.mtype AS mtype, mc.due AS due, mc.paid AS paid
    FROM billing b
    JOIN patient_data p ON b.pid = p.pid
    JOIN form_encounter e ON b.encounter = e.encounter
    JOIN medilinksa_claims mc ON b.encounter = mc.encounter
    WHERE b.billed = 1
    GROUP BY b.encounter
    ORDER BY e.date DESC");

$claimtype = "NW"; //NW = New Send or RS = Resend                                                                                                                                 
    
echo '<div class="container mt-4">';
echo '<h2>Claims</h2>';
echo '<div class="table-responsive">';
echo '<table class="table table-striped">';
echo '<thead class="thead-light">
        <tr>
            <th>Patient</th>           
            <th>RefNo</th>             
            <th>Date</th>
            <th>Number</th>            
            <th>Due</th>
            <th>Paid</th>            
            <th>From</th>
            <th>Status</th>                       
            <th>MType</th>                     
            <th>Message</th>              
            <th>Actions</th>
        </tr>
      </thead>';
echo '<tbody>';

while ($encounter = sqlFetchArray($encounterBilling)) {
if($encounter['total_amount'] < 0.0000000000001){continue;}
    $encounterId = htmlspecialchars($encounter['encounter']);

    // Main row (collapsed)
echo '<tr class="clickable" data-toggle="collapse" data-target="#collapse_' . htmlspecialchars($encounterId) . '">
    <td>' . htmlspecialchars($encounter['fname'] . ' ' . $encounter['lname']) . '</td>    
    <td>' . htmlspecialchars($encounter['refno']??"none") . '</td>    
    <td>' . htmlspecialchars(!empty($encounter['created_at']) ? date("d/m/Y", strtotime($encounter['created_at'])) : "N/A") . '</td>
    <td>' . htmlspecialchars($encounter['encounter']) . '</td>                    
    <td>' . htmlspecialchars(number_format($encounter['due'], 2)) . '</td>
    <td>' . htmlspecialchars(number_format($encounter['paid'], 2)) . '</td>
    <td>' . htmlspecialchars($encounter['type']) . '</td>
<td>';

    // PHP for status mapping
    $statusMap = [
        "FA" => "Full Acceptance",
        "PA" => "Partial Acceptance",
        "RV" => "Reversed",
        "RJ" => "Rejected",
        "PD" => "PAID"        
    ];
    echo isset($statusMap[$encounter['status']]) ? $statusMap[$encounter['status']] : htmlspecialchars($encounter['status']);

echo '</td>  

    <td>' . htmlspecialchars($encounter['mtype']) . '</td>        
    <td>' . htmlspecialchars($encounter['message']) . '</td>   
    <td>';

if (!$encounter['status']) {

    echo '<button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?encounter=' . htmlspecialchars($encounterId) . '">
        Resend
    </button>'; $claimtype = "NW";
} elseif ($encounter['status'] == "RJ") {
echo '<div class="d-flex gap-2">
        <button class="btn btn-warning btn-sm process-btn" 
            data-href="claims.php?revcounter=' . htmlspecialchars($encounterId) . '&refc=' . htmlspecialchars($encounter['refno']) . '">
            Reverse
        </button>
        <button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?encounter=' . htmlspecialchars($encounterId) . '">
            Retry
        </button>
      </div>';
$claimtype = "RS";
} elseif ($encounter['status'] == "RV") {
    echo '<button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php">
        Refresh
    </button>';
} elseif ($encounter['status'] == "FA" OR $encounter['status'] == "PA") {
    
echo '<div class="d-flex gap-2">
        <button class="btn btn-warning btn-sm process-btn" 
            data-href="claims.php?revcounter=' . htmlspecialchars($encounterId) . '&refc=' . htmlspecialchars($encounter['refno']) . '">
            Reverse
        </button>
        <button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?counterd=' . htmlspecialchars($encounterId) . '&refno=' . htmlspecialchars($encounter['refno']) . '">
            Trace
        </button>
      </div>';    
}  else {

    echo '<button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?dencounter=' . htmlspecialchars($encounterId) . '">
       Complete
    </button>';
}

echo '</td></tr>';


    // Fetch individual billing items for this encounter
    $billingDetails = sqlStatement("
        SELECT b.code,b.code_text, b.code_type, b.fee 
        FROM billing b 
        WHERE b.billed = 1 AND b.activity <> 0 AND b.encounter = ?", [$encounterId]);

    // Expanded row (hidden initially)
    echo '<tr id="collapse_' . $encounterId . '" class="collapse">
            <td colspan="4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>';

    while ($item = sqlFetchArray($billingDetails)) {
        echo '<tr>
                <td>' . htmlspecialchars($item['code']) . '</td>
                <td>' . htmlspecialchars($item['code_type']) . '</td>
                <td>' . htmlspecialchars(($item['code_type']!="ICD10")?number_format($item['fee'], 2):"") . '</td>
              </tr>';
    }

    echo '      </tbody>
                </table>
            </td>
          </tr>';
}

echo '</tbody></table></div></div>';
    
            
            
            
            
        } else { ?>

        <?php }

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['encounter'])) {

    $encounter = $_GET['encounter'];
    
    billencounter($encounter,$claimtype);
    $_GET = [];      
}elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['revcounter']) && isset($_GET['refc'])) {

    $encounter = $_GET['revcounter'];
    $refno = $_GET['refc'];
    $apiResponse = sendClaimReversal($encounter,$refno);
    
// Extract relevant data
$version = $apiResponse['Version'] ?? null;
$clientId = $apiResponse['ClientId'] ?? null;
$reversals = $apiResponse['Reversal'] ?? [];

?>
<div class="container">
    <h2 class="mb-4">Reversal Details</h2>

    <?php foreach ($reversals as $reversal): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Reversal Reference: <?= htmlspecialchars($reversal["ReveralReference"] ?? "N/A") ?>
            </div>
            <div class="card-body">

                <!-- General Reversal Details -->
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Reversal Number</th>
                            <th>Claim Number</th>
                            <th>Result</th>
                            <th>Claim Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($reversal["ReversalNbr"] ?? "N/A") ?></td>
                            <td><?= htmlspecialchars($reversal["ClaimNbr"] ?? "N/A") ?></td>
                            <td><?= htmlspecialchars($reversal["ReversalResult"] ?? "N/A") ?></td>
                            <td><?= htmlspecialchars($reversal["ClaimReference"] ?? "N/A") ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Echo Data -->
                <?php if (!empty($reversal["Echo"])): ?>
                    <h4 class="mt-3">Echo Data</h4>
                    <table class="table table-bordered">
                        <thead class="table-secondary">
                            <tr>
                                <th>Echo Type</th>
                                <th>Echo Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reversal["Echo"] as $echo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($echo["EchoType"]) ?></td>
                                    <td><?= htmlspecialchars($echo["EchoData"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Claim Messages (if not empty) -->
                <?php if (!empty($reversal["ClaimMessages"])): ?>
                    <h4 class="mt-3">Claim Messages</h4>
                    <ul class="list-group">
                        <?php foreach ($reversal["ClaimMessages"] as $message): ?>
                            <li class="list-group-item"><?= htmlspecialchars($message['MessageType']) ?></li>
                            <li class="list-group-item"><?= htmlspecialchars($message['Message']) ?></li>     
                            <li class="list-group-item"><?= htmlspecialchars($message['Origin']) ?></li>                                 
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>

</div>
    
<?php
    $_GET = []; 
    
}elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['counterd']) && isset($_GET['refno'])) {

    $encounter = $_GET['counterd'];
    $refno = $_GET['refno'];
    $apiResponse = submitClaimTrace($encounter,$refno);
    
    ?>
<div class="container">
        <h2 class="mb-4">Claim Details</h2>

        <?php foreach ($apiResponse["Documents"] as $document): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Claim Reference: <?= htmlspecialchars($document["ResponseHeader"]["AuthReference"]["ClaimReference"]) ?>
                </div>
                <div class="card-body">
                    
                    <!-- General Claim Details -->
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Claim Number</th>
                                <th>Result</th>
                                <th>Account Number</th>
                                <th>Hospital Indicator</th>
                                <th>Workstation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["Header"]["ClaimNbr"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["Header"]["ClaimResult"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["Header"]["AccountNbr"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["Header"]["HospitalIndicator"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["Header"]["WksNbr"]) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Financial Details -->
                    <h4 class="mt-3">Financials</h4>
                    <table class="table table-bordered">
                        <thead class="table-secondary">
                            <tr>
                                <th>Due By Scheme</th>
                                <th>Paid to Provider</th>
                                <th>Paid to Patient</th>
                                <th>Patient Levy</th>
                                <th>Tariff Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["DueByScheme"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["PaidToProvider"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["PaidToPatient"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["PatientLevy"]) ?></td>
                                <td><?= htmlspecialchars($document["ResponseHeader"]["ClaimFinancials"]["SchemeFinancials"]["TariffAmount"]) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Items Section -->
                    <h4 class="mt-3">Items</h4>
                    <table class="table table-bordered">
                        <thead class="table-info">
                            <tr>
                                <th>Item Number</th>
                                <th>Charge Code</th>
                                <th>Type</th>
                                <th>Payment Advice</th>
                                <th>Total Claimed</th>
                                <th>Due By Scheme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($document["Item"] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item["ItemHeader"]["ItemNumber"]) ?></td>
                                    <td><?= htmlspecialchars($item["ItemHeader"]["ChargeCode"]) ?></td>
                                    <td><?= htmlspecialchars($item["ItemHeader"]["Type"]) ?></td>
                                    <td><?= htmlspecialchars($item["ItemHeader"]["PaymentAdvice"]) ?></td>
                                    <td><?= htmlspecialchars($item["ItemHeader"]["TotalClaimed"]) ?></td>
                                    <td><?= htmlspecialchars($item["ItemFinancials"]["SchemeItemFinancials"]["DueByScheme"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Member & Patient Information -->
                    <h4 class="mt-3">Member & Patient Information</h4>
                    <table class="table table-bordered">
                        <thead class="table-warning">
                            <tr>
                                <th>Member Number</th>
                                <th>Member Name</th>
                                <th>Patient Name</th>
                                <th>Dependant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= htmlspecialchars($document["Information"]["Member"]["MemberNumber"]) ?></td>
                                <td><?= htmlspecialchars($document["Information"]["Member"]["Surname"]) . ', ' . htmlspecialchars($document["Information"]["Member"]["Initials"]) ?></td>
                                <td><?= htmlspecialchars($document["Information"]["Patient"]["Firstname"]) . ' ' . htmlspecialchars($document["Information"]["Patient"]["Surname"]) ?></td>
                                <td><?= htmlspecialchars($document["Information"]["Patient"]["Dependant"]) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

    </div>    
    
    <?php
$_GET = [];          
}
       ?>


<!-- Add Bootstrap JS & jQuery -->

<script src="../../../../../public/assets/jquery/dist/jquery.min.js"></script>
<script src="../../../../../public/assets/bootstrap/dist/js/bootstrap.min.js"></script>

<script>
$(document).ready(function(){
    $(".clickable").click(function(){
        var target = $(this).attr("data-target");
        $(target).collapse("toggle");
    });
});
</script>

<script>
$(document).ready(function(){
    $(".process-btn, .invoice-btn").click(function(){
        var button = $(this);
        var href = button.attr("data-href");

        // Change button text and disable it
        button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
        button.prop("disabled", true);

        // Redirect after a short delay
        setTimeout(function() {
            window.location.href = href;
        }, 500);
    });
});
</script>
    </body>
</html>

