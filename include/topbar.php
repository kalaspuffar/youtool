<?php
$script = basename($_SERVER["SCRIPT_FILENAME"], '.php');
?>
<div class="row topbar">
    <div class="one-half column">        
        <h1 style="vertical-align:middle"><img src="images/logo.png" width="40" height="40"> YouTool</h1>
    </div>    
    <div class="one-half column">
        <?php if (isset($user)) { ?>
            <p>Site quota: <?php showQuota() ?>&nbsp;&nbsp;&nbsp;User: <?php showPayment() ?></p>
        <?php } ?>
    </div>
</div>
<div class="row topmenu">
    <?php if (isset($user)) { ?>
    <a class="button <?php echo $script == 'list_videos' ? 'button-primary' : '' ?>" href="list_videos.php">List videos</a>
    <a class="button <?php echo $script == 'category' ? 'button-primary' : '' ?>" href="category.php">Categories</a>
    <a class="button <?php echo $script == 'block' ? 'button-primary' : '' ?>" href="block.php">Block editor</a>
    <?php
    if (isset($user['id']) && $user['id'] == 1) {
    // IN DEVELOPMENT
    ?>
    <a class="button <?php echo $script == 'research' ? 'button-primary' : '' ?>" href="research.php">Research</a>
    <a class="button <?php echo $script == 'list_impact' ? 'button-primary' : '' ?>" href="list_impact.php">Impact</a>
    <?php } ?>
    <a class="button <?php echo $script == 'comments' ? 'button-primary' : '' ?>" href="comments.php">List comments</a>
    <a class="button <?php echo $script == 'payments' ? 'button-primary' : '' ?>" href="payments.php">Payment</a>
    <?php }?>
</div>