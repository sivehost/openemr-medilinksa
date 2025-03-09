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

    $tab = "billable";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Billable")]);
    exit;
}
?>

<?php
// OpenEMR Module: Claims & Invoices
$encounterBilling = sqlStatement("
    SELECT b.encounter, b.pid, p.fname, p.lname, 
           e.date AS encounter_date, SUM(b.fee) AS total_amount
    FROM billing b
    JOIN patient_data p ON b.pid = p.pid
    JOIN form_encounter e ON b.encounter = e.encounter
    WHERE b.billed = 0
    GROUP BY b.encounter
    ORDER BY e.date DESC");


?>

<!DOCTYPE html>
<html>
<head>
    <title>Billable</title>
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
<?php
echo '<div class="container mt-4">';
echo '<h2>Pending Claims & Invoices</h2>';
echo '<div class="table-responsive">';
echo '<table class="table table-striped">';
echo '<thead class="thead-light">
        <tr>
            <th>Patient</th>           
            <th>Encounter Date</th>
            <th>Encounter Number</th>             
            <th>Total Amount</th>
            <th>Actions</th>
        </tr>
      </thead>';
echo '<tbody>';

while ($encounter = sqlFetchArray($encounterBilling)) {
if($encounter['total_amount'] < 0.0000000000001){continue;}
    $encounterId = htmlspecialchars($encounter['encounter']);

    // Main row (collapsed)
    echo '<tr class="clickable" data-toggle="collapse" data-target="#collapse_' . $encounterId . '">
            <td>' . htmlspecialchars($encounter['fname'] . ' ' . $encounter['lname']) . '</td>    
            <td>' . htmlspecialchars( (!empty($encounter['encounter_date'])) ? date("d/m/Y", strtotime($encounter['encounter_date'])) : "N/A"
) . '</td>
            <td>' . htmlspecialchars($encounter['encounter']) . '</td>                    
            <td>' . htmlspecialchars(number_format($encounter['total_amount'], 2)) . '</td>
<td>
    <button class="btn btn-primary btn-sm process-btn" 
            data-href="claims.php?encounter='.$encounterId.'">
        Process
    </button>

    <button class="btn btn-secondary btn-sm invoice-btn" 
            data-href="invoice.php?encounter='.$encounterId.'">
        Invoice
    </button>
</td>
          </tr>';

    // Fetch individual billing items for this encounter
    $billingDetails = sqlStatement("
        SELECT b.code,b.code_text, b.code_type, b.fee 
        FROM billing b 
        WHERE b.billed = 0 AND b.activity <> 0 AND b.encounter = ?", [$encounterId]);

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



