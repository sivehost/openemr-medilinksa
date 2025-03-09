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

    $tab = "cgraphs";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("C Graphs")]);
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
<div class="container mt-4">
    <h2 class="mb-4 text-center">Claims Graphs</h2>

    <div class="row">
        <div class="col-md-6"><canvas id="statusChart"></canvas></div>
        <div class="col-md-6"><canvas id="claimsChart"></canvas></div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6"><canvas id="financialChart"></canvas></div>
        <div class="col-md-6"><canvas id="httpChart"></canvas></div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6"><canvas id="typeChart"></canvas></div>
        <div class="col-md-6"><canvas id="processingChart"></canvas></div>
    </div>
</div>

<?php
// Query 1: Claim Status Distribution
$statusData = sqlStatement("SELECT status, COUNT(*) AS count FROM medilinksa_claims GROUP BY status");
$status = [];
while ($row = sqlFetchArray($statusData)) {
    $status[$row['status']] = $row['count'];
}

// Query 2: Total Claims Over Time
$claimsData = sqlStatement("SELECT DATE(created_at) AS claim_date, COUNT(*) AS total_claims FROM medilinksa_claims GROUP BY claim_date ORDER BY claim_date");
$claims = [];
while ($row = sqlFetchArray($claimsData)) {
    $claims[$row['claim_date']] = $row['total_claims'];
}

// Query 3: Total Due vs. Paid Over Time
$financialData = sqlStatement("SELECT DATE(created_at) AS claim_date, SUM(due) AS total_due, SUM(paid) AS total_paid FROM medilinksa_claims GROUP BY claim_date ORDER BY claim_date");
$financial = ["labels" => [], "due" => [], "paid" => []];
while ($row = sqlFetchArray($financialData)) {
    $financial["labels"][] = $row['claim_date'];
    $financial["due"][] = $row['total_due'];
    $financial["paid"][] = $row['total_paid'];
}

// Query 4: HTTP Response Code Analysis
$httpData = sqlStatement("SELECT httpcode, COUNT(*) AS occurrences FROM medilinksa_claims GROUP BY httpcode ORDER BY occurrences DESC");
$http = [];
while ($row = sqlFetchArray($httpData)) {
    $http[$row['httpcode']] = $row['occurrences'];
}

// Query 5: Claim Type Distribution
$typeData = sqlStatement("SELECT type, COUNT(*) AS count FROM medilinksa_claims GROUP BY type");
$type = [];
while ($row = sqlFetchArray($typeData)) {
    $type[$row['type']] = $row['count'];
}

// Query 6: Claim Processing Time Analysis
$processingData = sqlStatement("SELECT TIMESTAMPDIFF(MINUTE, created_at, updated_at) AS processing_time FROM medilinksa_claims");
$processing = [];
while ($row = sqlFetchArray($processingData)) {
    $processing[] = $row['processing_time'];
}
?>

<script>
    new Chart(document.getElementById("statusChart"), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($status)) ?>,
            datasets: [{ data: <?= json_encode(array_values($status)) ?>, backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107'] }]
        }
    });

    new Chart(document.getElementById("claimsChart"), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($claims)) ?>,
            datasets: [{ label: "Total Claims", data: <?= json_encode(array_values($claims)) ?>, borderColor: '#007bff', fill: false }]
        }
    });

    new Chart(document.getElementById("financialChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode($financial["labels"]) ?>,
            datasets: [
                { label: "Total Due", data: <?= json_encode($financial["due"]) ?>, backgroundColor: '#dc3545' },
                { label: "Total Paid", data: <?= json_encode($financial["paid"]) ?>, backgroundColor: '#28a745' }
            ]
        }
    });

    new Chart(document.getElementById("httpChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($http)) ?>,
            datasets: [{ label: "Occurrences", data: <?= json_encode(array_values($http)) ?>, backgroundColor: '#ffc107' }]
        }
    });

    new Chart(document.getElementById("typeChart"), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($type)) ?>,
            datasets: [{ data: <?= json_encode(array_values($type)) ?>, backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107'] }]
        }
    });

    new Chart(document.getElementById("processingChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode($processing) ?>,
            datasets: [{ label: "Processing Time (minutes)", data: <?= json_encode($processing) ?>, backgroundColor: '#007bff' }]
        }
    });
</script>    
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



