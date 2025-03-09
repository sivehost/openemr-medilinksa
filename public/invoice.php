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
    require_once "../../../../../library/classes/fpdf/fpdf.php";

    use OpenEMR\Common\Acl\AclMain;
    use OpenEMR\Common\Twig\TwigContainer;
    use OpenEMR\Modules\MedilinkSA\ClaimsPage;
    use FPDF;

class PDFWithLetterhead extends FPDF {
 private $isFirstPage = true;
    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }
/*
    function Header() {
        // Set letterhead image
        $this->Image('/var/www/drcure/esign/1_1_header.gif', 10, 5, 190);
        $this->Ln(50); // Move down to prevent overlap
    }*/
    
    function Footer() {
        $this->SetY(-15); // Position footer 15mm from bottom
        $this->SetFont('Arial', 'I', 10); // Italic font
        
        // Centered Page X of Y
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C'); 
    }
    
        function AddPage($orientation = '', $size = '', $rotation = 0) {
        parent::AddPage($orientation, $size, $rotation);
        $this->isFirstPage = false;
    }
}

// Get the invoice ID from request
if (isset($_GET['invoice_id']) AND $_GET['yini'] == "PDF") {
 
$invoice_id = $_GET['invoice_id'];

// Fetch invoice details
$invoice = sqlQuery("SELECT * FROM medilinksa_invoices WHERE id = ?", [$invoice_id]);
if (!$invoice) {
    die("Invoice not found");
}

// Set headers for PDF output
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=invoice.pdf");
header("Cache-Control: private, max-age=0, must-revalidate");
header("Pragma: public");

// Fetch assigned provider ID from the encounter table
$providerQuery = sqlQuery("SELECT provider_id FROM form_encounter WHERE encounter = ?", [$invoice['encounter']]);

$provider_id = $providerQuery['provider_id'] ?? null;

// Fetch tax number & bank account details from users table
if ($provider_id) {
    $providerDetails = sqlQuery("SELECT federaltaxid, info FROM users WHERE id = ?", [$provider_id]);
    $taxNumber = $providerDetails['federaltaxid'] ?? "Not Available";
    $bankDetails =  explode("\n", trim($providerDetails['info'] ?? "No Bank Details Provided"));
} else {
    $taxNumber = "Not Available";
    $bankDetails = "No Bank Details Provided";
}

$insurance = sqlQuery("SELECT i.subscriber_fname, i.subscriber_lname, i.subscriber_ss, i.subscriber_street,
                              i.subscriber_street_line_2, i.subscriber_city, i.subscriber_state, i.subscriber_postal_code, i.subscriber_country
                              FROM insurance_data i WHERE i.pid = ? AND i.type = 'primary'", [$invoice['pid']]);

// Fetch billing items
$billingItems = sqlStatement("SELECT b.*, b.fee AS billing_fee, b.code AS billing_code, b.code_type AS billing_codet, p.*, ct.*, c.* FROM billing b 
                              JOIN patient_data p ON b.pid = p.pid JOIN code_types ct ON b.code_type = ct.ct_key  LEFT JOIN codes c ON b.code = c.code AND c.code_type = ct.ct_id 
                              WHERE b.activity = 1 AND b.encounter = ?", [$invoice['encounter']]);

// Fetch payments
$payments = sqlStatement("SELECT * FROM payments WHERE encounter = ?", [$invoice['encounter']]);

// Fetch VAT rates
$vatRatesQuery = sqlStatement("SELECT option_id, option_value FROM list_options WHERE list_id = 'taxrate'");
$vatRates = [];
while ($vatRow = sqlFetchArray($vatRatesQuery)) {
    $vatRates[$vatRow['option_id']] = $vatRow['option_value'];
}

// Create a new PDF document
// Create PDF with Letterhead
$pdf = new PDFWithLetterhead();
$pdf->AliasNbPages(); // Enables {nb} for total pages
$pdf->AddPage();
        $pdf->Image('/var/www/drcure/esign/1_1_header.gif', 10, 5, 190);
        $pdf->Ln(50); // Move down to prevent overlap
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, '', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(100, 7, 'Invoiced To:', 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(90, 7, 'Invoice No: ' . $invoice['id'], 0, 1, 'R');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 7, $insurance['subscriber_fname'] . ' ' . $insurance['subscriber_lname'], 0, 0);
$pdf->Cell(90, 7, 'Invoice Date: ' . date("d/m/Y", strtotime($invoice['created_at'])), 0, 1, 'R');
$pdf->Cell(100, 7, $insurance['subscriber_ss'], 0, 0);
$pdf->Cell(90, 7, 'Due Date: ' . date("d/m/Y", strtotime($invoice['created_at'] . ' +30 days')), 0, 1, 'R');
$pdf->Cell(100, 7, $insurance['subscriber_street'], 0, 0);
if (isset($taxNumber)) {$pdf->Cell(90, 7, 'VAT Number: ' . $taxNumber, 0, 1, 'R');}
$pdf->Cell(100, 7, $insurance['subscriber_street_line_2'], 0, 1);
$pdf->Cell(100, 7, $insurance['subscriber_city'] . ', ' . $insurance['subscriber_state'], 0, 1);
$pdf->Cell(100, 7, $insurance['subscriber_postal_code'] . ', ' . $insurance['subscriber_country'], 0, 1);




$pdf->Ln(5);

// Table Headers
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 7, 'Date', 1);
$pdf->Cell(100, 7, 'Description', 1);
$pdf->Cell(20, 7, 'Qty', 1, 0, 'L');
$pdf->Cell(30, 7, 'Fee', 1, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$totalAmount = 0;
$vatAmount = 0;
$subtotal = 0;
while ($item = sqlFetchArray($billingItems)) {
    $yoyo = explode(":",$item['taxrates']);
    $vatRate = isset($vatRates[$yoyo[0]]) ? (float) $vatRates[$yoyo[0]] : 0;
    
    //$vatRate = isset($vatRates[$item['taxrates']]) ? (float) $vatRates[$item['taxrates']] : 0;
    $vat = $item['billing_fee'] * $vatRate;
    $vatAmount += $vat;
    $subtotal += $item['billing_fee'];
    $totalAmount += $item['billing_fee'] + $vat;
    
    $pdf->Cell(40, 7, date("d/m/Y", strtotime($item['bill_date'])), 1);
    $pdf->Cell(100, 7, $item['billing_code'], 1);

    $pdf->Cell(20, 7, ($item['billing_codet']!="ICD10")?number_format($item['units'], 2):"", 1, 0, 'R');
    $pdf->Cell(30, 7, ($item['billing_codet']!="ICD10")?number_format($item['billing_fee'], 2):"", 1, 1, 'R');
}

$pdf->Cell(160, 7, 'Sub Total', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($subtotal, 2), 1, 1, 'R');

$pdf->Cell(160, 7, 'VAT', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($vatAmount, 2), 1, 1, 'R');

$pdf->Cell(160, 7, 'Total', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($totalAmount, 2), 1, 1, 'R');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, 'Payments', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);

$totalPaid = 0;
while ($payment = sqlFetchArray($payments)) {
    $totalPaid += $payment['amount2'];
    $pdf->Cell(40, 7, date("d/m/Y", strtotime($payment['dtime'])), 1);
    $pdf->Cell(80, 7, $payment['source'], 1);
    $pdf->Cell(40, 7, '- ' . number_format($payment['amount2'], 2), 1, 1, 'R');
}

$balanceDue = $totalAmount - $totalPaid;
$pdf->Cell(160, 7, 'Balance Due', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($balanceDue, 2), 1, 1, 'R');

$pdf->Ln(5);
$pdf->CheckPageBreak(40);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, 'Pay to:', 0, 1);
$pdf->SetFont('Arial', '', 10);
foreach ($bankDetails as $detail) {
//    $pdf->Cell(95, 7, $detail, 1, 0);
    $pdf->Cell(190, 7, $detail, 1, 1, 'L'); // Full width for bank details
}


$pdf->Output('receipt_' . $invoice_id . '.pdf', 'I');
}




    $tab = "invoice";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Invoice")]);
    exit;
}


if (isset($_GET['encounter'])) {

$encounter = $_GET['encounter'];

// Fetch patient and billing details, ensuring we get the correct VAT by checking code_types
$billingItems = sqlStatement("SELECT b.*, b.fee AS billing_fee, p.*, ct.*, c.*  
                              FROM billing b 
                              JOIN patient_data p ON b.pid = p.pid 
                              JOIN code_types ct ON b.code_type = ct.ct_key 
                              LEFT JOIN codes c ON b.code = c.code AND c.code_type = ct.ct_id 
                              WHERE b.activity = 1 AND b.encounter = ?", [$encounter]);


$totalAmount = 0;
$vatAmount = 0;

// Fetch VAT rates from list_options
$vatRatesQuery = sqlStatement("SELECT option_id, option_value FROM list_options WHERE list_id = 'taxrate'");
$vatRates = [];
while ($vatRow = sqlFetchArray($vatRatesQuery)) {
    $vatRates[$vatRow['option_id']] = $vatRow['option_value'];
}

// Calculate VAT and total amount directly in billing table
while ($item = sqlFetchArray($billingItems)) {
    $amount = $item['billing_fee'];
    $yoyo = explode(":",$item['taxrates']);
    $vatRate = isset($vatRates[$yoyo[0]]) ? (float) $vatRates[$yoyo[0]] : 0;
    $vat = $amount * $vatRate;
    $vatAmount += $vat;
    $totalAmount += $amount + $vat;
    $patient = $item;
    // Update VAT and billed status in billing table
    sqlStatement("UPDATE billing SET billed = 1, bill_date = NOW() WHERE id = ?", [$item['id']]);
}
 
$payments = sqlStatement("SELECT * FROM payments WHERE encounter = ?", [$invoice['encounter']]);
$totalpaid = 0;
while ($payment = sqlFetchArray($payments)) {$totalpaid = number_format($payment['amount2'], 2);}

// Insert Invoice Data
$invoiceQuery = "INSERT INTO medilinksa_invoices (total_paid, pid, encounter, total_amount, vat_amount, status, created_at) VALUES (?,?, ?, ?, ?, 'unpaid', NOW())";
sqlStatement($invoiceQuery, [$totalpaid, $patient['pid'], $encounter, $totalAmount, $vatAmount]);
}
?>

<?php
// Fetch all invoices for summary
$invoices = sqlStatement("SELECT * FROM medilinksa_invoices ORDER BY created_at DESC");


?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
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
<div class="container mt-4"> <!-- Main Section -->
        <h2>Invoices</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Patient ID</th>
                        <th>Encounter</th>
                        <th>Total Amount</th>
                        <th>Total Paid</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($invoice = sqlFetchArray($invoices)) {
                        // Calculate total payments made for this invoice
                        $totalPaidResult = sqlQuery("SELECT SUM(amount2) AS total_paid FROM payments WHERE encounter = ?", [$invoice['encounter']]);
                        $totalPaid = $totalPaidResult['total_paid'] ?? 0;
                        $balanceDue = $invoice['total_amount'] - $totalPaid;
                        
                        // Determine status based on payments
                        $newStatus = ($balanceDue < 0.01) ? 'paid' : 'unpaid';
                        if ($newStatus !== $invoice['status']) {
                            sqlStatement("UPDATE medilinksa_invoices SET status = ? WHERE id = ?", [$newStatus, $invoice['id']]);
                        }
                        
                    ?>
                        <tr class="clickable" data-toggle="collapse" data-target="#invoice_<?php echo $invoice['id']; ?>">
                            <td><?php echo htmlspecialchars($invoice['id']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['pid']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['encounter']); ?></td>
                            <td>R <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td>R <?php echo number_format($totalPaid, 2); ?></td>
                            <td>R <?php echo number_format($balanceDue, 2); ?></td>
                            <td><?php echo htmlspecialchars($newStatus); ?></td>
                            <td>
                                <a href="invoice.php?payment2=yebo&invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success btn-sm">Allocate Funds</a>
                                <a href="invoice.php?yini=PDF&invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-secondary btn-sm">Print</a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8">
                                <div id="invoice_<?php echo $invoice['id']; ?>" class="collapse">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date of Service</th>
                                                <th>Description</th>
                                                <th class="text-right">Price</th>
                                                <th class="text-right">Qty</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-right">VAT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                        
                                            $billingItems = sqlStatement("SELECT b.*, b.fee AS billing_fee, b.code AS billing_code, b.code_type AS billing_codet, p.*, ct.*, c.* FROM billing b 
                              JOIN patient_data p ON b.pid = p.pid JOIN code_types ct ON b.code_type = ct.ct_key  LEFT JOIN codes c ON b.code = c.code AND c.code_type = ct.ct_id 
                              WHERE b.activity = 1 AND b.encounter = ?", [$invoice['encounter']]);
                                            
// Fetch VAT rates from list_options
$vatRatesQuery = sqlStatement("SELECT option_id, option_value FROM list_options WHERE list_id = 'taxrate'");$vatRates = [];
while ($vatRow = sqlFetchArray($vatRatesQuery)) {$vatRates[$vatRow['option_id']] = $vatRow['option_value'];}
$toto =0;
$veta =0;
                                            
                                            while ($item = sqlFetchArray($billingItems)) {
                                            $yoyo = explode(":",$item['taxrates']);
                                            $vatRate = isset($vatRates[$yoyo[0]]) ? (float) $vatRates[$yoyo[0]] : 0;
                                            $veta += $item['billing_fee']*$vatRate;
                                            $toto += $item['billing_fee'] + $veta;
                                            ?>
                                             <tr>
                                                  <td><?php echo date("d/m/Y", strtotime($item['date'])); ?></td>
                                                  <td><?php echo htmlspecialchars($item['billing_code']); ?></td>
                                                  <td class="text-right"> <?php echo ($item['billing_codet']!="ICD10")?number_format($item['billing_fee'], 2):""; ?></td>
                                                  <td class="text-right"><?php echo ($item['billing_codet']!="ICD10")?number_format($item['units'], 2):""; ?></td>
                                                  <td class="text-right"> <?php echo ($item['billing_codet']!="ICD10")?number_format($item['billing_fee'], 2):""; ?></td>
                                                  <td class="text-right"> <?php echo ($item['billing_codet']!="ICD10")?number_format($item['billing_fee']*$vatRate, 2):""; ?></td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td colspan="4"></td>                                            
                                                <td class="font-weight-bold text-right bg-blue" style="border: 1px solid;"><?php echo number_format($toto, 2); ?></td>
                                                <td class="text-right bg-blue" style="border: 1px solid;"><?php echo number_format($veta, 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"><b>Payment Transactions</b></td>
                                            </tr>
                                            <?php
                                            $payments = sqlStatement("SELECT * FROM payments WHERE encounter = ?", [$invoice['encounter']]);
                                            while ($payment = sqlFetchArray($payments)) {
                                            ?>
                                                <tr>
                                                    <td><?php echo date("d/m/Y", strtotime($payment['dtime'])); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['source']); ?></td>
                                                    <td colspan="3"></td>
                                                    <td class="text-right text-danger">- <?php echo number_format($payment['amount2'], 2); ?></td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td colspan="6"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4"></td>
                                                <td class="font-weight-bold text-right bg-blue" style="border: 1px solid;">Balance Due</td>
                                                <td class="text-right bg-blue" style="border: 1px solid;"> <?php echo number_format($balanceDue, 2); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    
    
<?php
if ((isset($_GET['invoice_id']) AND isset($_GET['payment2']) && $_GET['payment2'] == "yebo") OR (isset($_POST['invoice_id']) AND isset($_POST['payment2']) && $_POST['payment2'] == "yebo") ) {
$saved = 0;
$invoice_id = $_GET['invoice_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = $_POST['form_pid'] ?? null;
    $encounter = $_POST['form_encounter'] ?? null;
    $amount = $_POST['form_amount'] ?? 0;
    $discount = $_POST['form_discount'] ?? 0;
    $method = $_POST['form_method'] ?? '';
    $reference = $_POST['form_source'] ?? '';
    $date = $_POST['form_date'] ?? date('Y-m-d');
    $invoice_id = $_POST['invoice_id'];
    
    
    

// Check if an invoice already exists
    $invoice = sqlQuery("SELECT * FROM medilinksa_invoices WHERE pid = ? AND encounter = ?", [$pid, $encounter]);
    
    if ($invoice) {
        // Update existing invoice
        $invoice['total_paid'] = $_POST['totalpad'] ?? $invoice['total_paid'];
        $invoice['total_amount'] = $_POST['totalamt'] ?? $invoice['total_amount'];
        $new_total_paid = $invoice['total_paid'] + $amount;
        $new_status = ($new_total_paid >= $invoice['total_amount']) ? 'paid' : 'unpaid';
        sqlStatement("UPDATE medilinksa_invoices SET total_paid = ?, status = ?, updated_at = NOW() WHERE pid = ? AND encounter = ?", 
            [$new_total_paid, $new_status, $pid, $encounter]);
    } else {
        // Insert new invoice
        $total_amount = $amount + $discount; // Assuming total amount includes discount
        $vat_amount = 0; // VAT calculation logic should go here if applicable
        $status = ($amount >= $total_amount) ? 'paid' : 'unpaid';
        sqlStatement("INSERT INTO medilinksa_invoices (pid, encounter, total_amount, vat_amount, total_paid, status, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())", 
            [$pid, $encounter, $total_amount, $vat_amount, $amount, $status]);
    }
    
        // Insert into payments table
    sqlStatement("INSERT INTO payments (pid, encounter, dtime, method, source, amount1, amount2, user) 
                  VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)",
        [$pid, $encounter, $method, $reference, $discount, $amount, $_SESSION['authUser']]);
        $saved = 1;
    
    }

// Fetch invoice details
$invoice = sqlQuery("SELECT * FROM medilinksa_invoices WHERE id = ?", [$invoice_id]);
if (!$invoice) {
    die("");
}

// Fetch billing items
$billingItems = sqlStatement("SELECT b.*, b.fee AS billing_fee, b.code AS billing_code, b.code_type AS billing_codet, p.*, ct.*, c.* FROM billing b 
                              JOIN patient_data p ON b.pid = p.pid JOIN code_types ct ON b.code_type = ct.ct_key  LEFT JOIN codes c ON b.code = c.code AND c.code_type = ct.ct_id 
                              WHERE b.activity = 1 AND b.encounter = ?", [$invoice['encounter']]);
$pid = $invoice['pid'];
$encounter = $invoice['encounter'];

// Fetch payments
$payments = sqlStatement("SELECT * FROM payments WHERE encounter = ?", [$invoice['encounter']]);

// Fetch VAT rates
$vatRatesQuery = sqlStatement("SELECT option_id, option_value FROM list_options WHERE list_id = 'taxrate'");
$vatRates = [];
while ($vatRow = sqlFetchArray($vatRatesQuery)) {
    $vatRates[$vatRow['option_id']] = $vatRow['option_value'];
}
$totalAmount = 0;
$vatAmount = 0;
$subtotal = 0;
while ($item = sqlFetchArray($billingItems)) {
    $yoyo = explode(":",$item['taxrates']);
    $vatRate = isset($vatRates[$yoyo[0]]) ? (float) $vatRates[$yoyo[0]] : 0;
    
    //$vatRate = isset($vatRates[$item['taxrates']]) ? (float) $vatRates[$item['taxrates']] : 0;
    $vat = $item['billing_fee'] * $vatRate;
    $vatAmount += $vat;
    $subtotal += $item['billing_fee'];
    $totalAmount += $item['billing_fee'] + $vat;
}


$totalPaid = 0;
$balance_due = 0;
while ($payment = sqlFetchArray($payments)) {
    $totalPaid += $payment['amount2'];
}

$balance_due = $totalAmount - $totalPaid;

?>

    <h2>Allocate Payment</h2>
    
    <form id="paymentForm" action="invoice.php"  method="post">
        <input type="hidden" name="form_pid" value="<?php echo attr($pid); ?>">
        <input type="hidden" name="invoice_id" value="<?php echo attr($invoice_id); ?>">
        <input type="hidden" name="totalamt" value="<?php echo attr($totalAmount); ?>">  
        <input type="hidden" name="totalpad" value="<?php echo attr($totalPaid); ?>">          
        <input type="hidden" name="payment2" value="yebo">      
        <input type="hidden" name="form_encounter" value="<?php echo attr($encounter); ?>">
        <p><strong>Amount Paid: </strong>R <?php echo number_format($totalPaid, 2); ?></p>        
        <p><strong>Balance Due: </strong>R <?php echo number_format($balance_due, 2); ?></p>
        <?php if($saved == 1){echo "Funds allocated with success.<br/>";} ?>
        <!--label>Discount Amount:</label> <input type="text" name="form_discount" class="form-control"><br-->
        <label>Payment Method:</label>
        <select name="form_method" class="form-control">
            <option value="cash">Cash</option>
            <option value="credit_card">Credit Card</option>
            <option value="payfast">PayFast</option>
            <option value="paypal">PayPal</option>
        </select><br>
        <label>Reference Number:</label> <input type="text" name="form_source" class="form-control"><br>
        
        <label>Amount Paid:</label> <input type="text" name="form_amount" class="form-control" value="<?php echo $balance_due; ?>"><br>
        <label>Posting Date:</label> <input type="date" name="form_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"><br>
        <button type="submit">Allocate Payment</button>
    </form>



<?php

}
?>    
    
    
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

</div> <!-- Sub Main Section -->
</body>
</html>

