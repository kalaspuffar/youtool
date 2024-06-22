<?php
$script = basename($_SERVER["SCRIPT_FILENAME"], '.php');
?>
<hr/>
<div class="row">                
    <a class="button <?php echo $script == 'terms' ? 'button-primary' : '' ?>" href="terms.php">Terms and condition</a>
    <a class="button <?php echo $script == 'privacy' ? 'button-primary' : '' ?>" href="privacy.php">Privacy Policy</a>
</div>