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

    $tab = "igraphs";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("I Graphs")]);
    exit;
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Graphs</title>
    <link rel="stylesheet" href="../../../../../public/assets/bootstrap/dist/css/bootstrap.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="<?php echo $GLOBALS['assets_static_relative']; ?>/chart.js/dist/chart.js"></script>

</head>
<body class="bg-light">
        <div class="row"> 
            <div class="col">
            <?php
                require '../templates/navbar.php';
            ?>
            </div>
        </div>
<!-- Add Bootstrap JS & JQuery START-->

<div class="container mt-4">
    <h2 class="mb-4 text-center">Invoice Dashboard</h2>

    <div class="row">
        <div class="col-md-6"><canvas id="statusChart"></canvas></div>
        <div class="col-md-6"><canvas id="invoiceChart"></canvas></div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6"><canvas id="paymentChart"></canvas></div>
        <div class="col-md-6"><canvas id="vatChart"></canvas></div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6"><canvas id="topPatientsChart"></canvas></div>
        <div class="col-md-6"><canvas id="unpaidChart"></canvas></div>
    </div>
</div>

<?php
// Query 1: Invoice Status Breakdown
$statusData = sqlStatement("SELECT status, COUNT(*) AS count FROM medilinksa_invoices GROUP BY status");
$status = [];
while ($row = sqlFetchArray($statusData)) {
    $status[$row['status']] = $row['count'];
}

// Query 2: Total Invoices Over Time
$invoiceData = sqlStatement("SELECT DATE(created_at) AS invoice_date, COUNT(*) AS total_invoices FROM medilinksa_invoices GROUP BY invoice_date ORDER BY invoice_date");
$invoices = [];
while ($row = sqlFetchArray($invoiceData)) {
    $invoices[$row['invoice_date']] = $row['total_invoices'];
}

// Query 3: Total Amount vs. Paid Over Time
$paymentData = sqlStatement("SELECT DATE(created_at) AS invoice_date, SUM(total_amount) AS total_invoiced, SUM(total_paid) AS total_received FROM medilinksa_invoices GROUP BY invoice_date ORDER BY invoice_date");
$payments = ["labels" => [], "invoiced" => [], "received" => []];
while ($row = sqlFetchArray($paymentData)) {
    $payments["labels"][] = $row['invoice_date'];
    $payments["invoiced"][] = $row['total_invoiced'];
    $payments["received"][] = $row['total_received'];
}

// Query 4: VAT Collected Over Time
$vatData = sqlStatement("SELECT DATE(created_at) AS invoice_date, SUM(vat_amount) AS total_vat FROM medilinksa_invoices GROUP BY invoice_date ORDER BY invoice_date");
$vat = ["labels" => [], "vat_collected" => []];
while ($row = sqlFetchArray($vatData)) {
    $vat["labels"][] = $row['invoice_date'];
    $vat["vat_collected"][] = $row['total_vat'];
}

// Query 5: Top Paying Patients
$topPatientsData = sqlStatement("SELECT pid, SUM(total_paid) AS total_paid FROM medilinksa_invoices GROUP BY pid ORDER BY total_paid DESC LIMIT 10");
$topPatients = ["labels" => [], "amounts" => []];
while ($row = sqlFetchArray($topPatientsData)) {
    $topPatients["labels"][] = "Patient #" . $row['pid'];
    $topPatients["amounts"][] = $row['total_paid'];
}

// Query 6: Unpaid Invoices Report
$unpaidData = sqlStatement("SELECT encounter, total_amount FROM medilinksa_invoices WHERE status = 'unpaid' ORDER BY total_amount DESC LIMIT 10");
$unpaidInvoices = ["labels" => [], "amounts" => []];
while ($row = sqlFetchArray($unpaidData)) {
    $unpaidInvoices["labels"][] = "Enc #" . $row['encounter'];
    $unpaidInvoices["amounts"][] = $row['total_amount'];
}
?>

<script>
    new Chart(document.getElementById("statusChart"), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($status)) ?>,
            datasets: [{ data: <?= json_encode(array_values($status)) ?>, backgroundColor: ['#007bff', '#28a745', '#dc3545'] }]
        }
    });

    new Chart(document.getElementById("invoiceChart"), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($invoices)) ?>,
            datasets: [{ label: "Total Invoices", data: <?= json_encode(array_values($invoices)) ?>, borderColor: '#007bff', fill: false }]
        }
    });

    new Chart(document.getElementById("paymentChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode($payments["labels"]) ?>,
            datasets: [
                { label: "Total Invoiced", data: <?= json_encode($payments["invoiced"]) ?>, backgroundColor: '#dc3545' },
                { label: "Total Received", data: <?= json_encode($payments["received"]) ?>, backgroundColor: '#28a745' }
            ]
        }
    });

    new Chart(document.getElementById("vatChart"), {
        type: 'line',
        data: {
            labels: <?= json_encode($vat["labels"]) ?>,
            datasets: [{ label: "VAT Collected", data: <?= json_encode($vat["vat_collected"]) ?>, borderColor: '#ffc107', fill: false }]
        }
    });

    new Chart(document.getElementById("topPatientsChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode($topPatients["labels"]) ?>,
            datasets: [{ label: "Total Paid", data: <?= json_encode($topPatients["amounts"]) ?>, backgroundColor: '#007bff' }]
        }
    });

    new Chart(document.getElementById("unpaidChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode($unpaidInvoices["labels"]) ?>,
            datasets: [{ label: "Unpaid Amount", data: <?= json_encode($unpaidInvoices["amounts"]) ?>, backgroundColor: '#dc3545' }]
        }
    });
</script>



<!-- Add Bootstrap JS & jQuery END-->

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



