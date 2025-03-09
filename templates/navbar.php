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

?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#"><?php echo xlt("MedilinkSA"); ?> </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item <?php if ($tab == "home") {
                echo "active";
                                } ?>">
                <a class="nav-link" href="index.php"><?php echo xlt("Home"); ?></a>
            </li>
            <li class="nav-item <?php if ($tab == "patient") {
                echo "active";
                                } ?>">
                <a class="nav-link" href="patient.php"><?php echo xlt("Patient"); ?></a>
            </li>            
            <li class="nav-item <?php if ($tab == "membership") {
                echo "active";
                                } ?>">
                <a class="nav-link" href="membership.php"><?php echo xlt("Membership"); ?></a>
            </li>
            <li class="nav-item <?php if ($tab == "billable") {
                echo "active";
                                } ?>" >
                <a class="nav-link" href="billable.php"><?php echo xlt("Billable"); ?></a>                            
            </li>
            <li class="nav-item <?php if ($tab == "claims") {
                echo "active";
                                } ?>">
                <a class="nav-link" href="claims.php"><?php echo xlt("Claims"); ?></a>
            </li>            
            <li class="nav-item <?php if ($tab == "invoice") {
                echo "active";
                                } ?>" >
                <a class="nav-link" href="invoice.php"><?php echo xlt("Invoices"); ?></a>                            
            </li>
            <li class="nav-item <?php if ($tab == "cgraphs") {
                echo "active";
                                } ?>" >
                <a class="nav-link" href="cgraphs.php"><?php echo xlt("C Graphs"); ?></a>                            
            </li>
            <li class="nav-item <?php if ($tab == "igraphs") {
                echo "active";
                                } ?>" >
                <a class="nav-link" href="igraphs.php"><?php echo xlt("I Graphs"); ?></a>                            
            </li>            
        </ul>        
    </div>
</nav>       
