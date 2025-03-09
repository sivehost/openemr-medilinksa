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

    require_once "../../../../globals.php";

    use OpenEMR\Common\Acl\AclMain;
    use OpenEMR\Common\Twig\TwigContainer;

    $tab = "home";

//ensure user has proper access
if (!AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("MedilinkSA - Home")]);
    exit;
}
?>
<html>
    <head>
        <link rel="stylesheet" href="../../../../../public/assets/bootstrap/dist/css/bootstrap.min.css">
    </head>
<title> <?php echo xlt("MedilinkSA - Home"); ?>  </title>
<body>
    <div class="row"> 
        <div class="col">
            <?php
                require '../templates/navbar.php';
            ?>
        </div>
    </div>
    <div class="row"> 
        <div class="col">
            <h1><?php echo xlt(""); ?></h1>        
        </div>
    </div>
    <div class="row"> 
        <div class="col">
            <div class="card">
                <p>
                    <?php echo xlt("Welcome to MedilinkSA billing. This is the home page to help you understand what you can expect to find on all the other menu items."); ?>             
                </p>
               
                <h6> <?php echo xlt("Menu item descriptions"); ?>   </h6>
                <ul>
                    <li>
                        <?php echo xlt("Patient -> Here you can put in the medical aid name, medical aid number, dependant code for each patient by using an existing patient ID number"); ?>                          
                    </li>                
                    <li>
                        <?php echo xlt("Membership -> Here you can check membership status and details like dependants using medical aid number"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Billable -> These are all the billable encounters with fee sheets saved, so if an encounter had a fee sheet saved with prices, it will appear here - you can then choose to send to claims or invoice patient"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Claims -> Here you see the status of claims and what to fix if any, pending, sent, rejected, successful, should be able to filter by date and status, should be able to send claims to Collections"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Invoices -> Here you see the invoices, you can allocate credit to an invoice, change status to refunded or remove credit to unpaid, change status to cancelled, should be able to send invoices to Collections"); ?>                         
                    </li>
                    <li>
                        <?php echo xlt("Trace -> Here you can trace a claim"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Reverse -> Here you can reverse a claim"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Collections -> Here you see the invoices and claims that have been sent to collections and their status whether successful, partial, failed and can allocate amount collected"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Denial Report -> Here you see how much claims were denied vs approved, should be able to filter by date and status"); ?>                          
                    </li>
                    <li>
                        <?php echo xlt("Aging Report -> Here you see 30 days, 60 days and 90 days old claims and invoices, should be able to choose to show both claims and invoices, and by status, should show revenue"); ?>                          
                    </li>  
                    <li>
                        <?php echo xlt("Collections Report - of those claims denied, how many have been collected"); ?>                          
                    </li>                    
                </ul>

                <h6><?php echo xlt("Technical Support"); ?></h6>
                <ul>
                    <li> <?php echo xlt("WhatsApp"); ?>: <a href="tel:+27738415369">+27 73 841 5369<a>   </li>
                    <li> <?php echo xlt("Email Support"); ?>: <a href = "support@sive.host">support@sive.host</a> </li>
                </ul>
            </div>
        
        </div>
    </div>
</body>
</html>
