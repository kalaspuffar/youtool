<?php
$script = basename($_SERVER["SCRIPT_FILENAME"], '.php');
?>
<div class="row topbar">
    <div class="one-half column">        
        <h1 style="vertical-align:middle"><img src="images/logo.png" width="40" height="40"> YouTool</h1>
    </div>
    <div class="one-half column">
        <p>Site quota: <?php showQuota() ?>&nbsp;&nbsp;&nbsp;User: <?php showPayment() ?></p>
    </div>
</div>
<div class="row topmenu">
    <a class="button <?php echo $script == 'list_videos' ? 'button-primary' : '' ?>" href="list_videos.php">List videos</a>
    <a class="button <?php echo $script == 'category' ? 'button-primary' : '' ?>" href="category.php">Categories</a>
    <a class="button <?php echo $script == 'block' ? 'button-primary' : '' ?>" href="block.php">Block editor</a>
    <a class="button <?php echo $script == 'comments' ? 'button-primary' : '' ?>" href="comments.php">List comments</a>
    <a class="button <?php echo $script == 'payment' ? 'button-primary' : '' ?>" href="payment.php">Payments</a>
</div>

<?php

/*
sb-ea52530553949@personal.example.com
ivKD-3LI

sb-cnz43s31236862@business.example.com
0%Q2q';^
*/