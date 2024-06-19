<?php
require_once(__DIR__ . '/../include/functions.php');

if (isset($_COOKIE['auth_key'])) {
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE auth_key = ?");
    $stmt->bind_param("s", $_COOKIE['auth_key']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        header("Location: https://youtool.app");
    }
    $user = $result->fetch_assoc();
    $user = getUserAccess($user['id']);    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit video</title>
    <meta name="description" content="Small site to handle your YouTube channel.">
    <meta name="author" content="Daniel Persson">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
    <script src="https://www.paypal.com/sdk/js?client-id=AW6-r25iKzEDvpouUw9xUStsrE3AfWLe46bciWovpTRgd9ROzsuBHERSGBbdgx0r3S9Ys3hqtC_CBJZa&components=buttons&currency=USD"></script>
</head>
<body>
    <div class="section hero">
        <div class="container">
            <?php require_once(__DIR__ . '/../include/topbar.php'); ?>

            <div class="row">                
                <div class="one-third column price-card">
                    <h5>Free</h5>
                    <p>$0</p>
                    <p>Requires: Read Access</p>
                    <span>Features:</span>
                    <ul class="feature-list">
                        <li>Organize videos</li>
                        <li>Schedule blocks</li>
                        <li>Categorise videos and blocks</li>
                        <li>Auto generate description</li>
                        <li>Read and hide comments</li>
                    </ul>
                </div>
                <div class="one-third column selected-price-card">
                    <h5>Pro</h5>
                    <p>$<?php echo number_format($MONTHLY_PRICE, 2, '.', ''); ?> per month</p>
                    <p>Requires: Write Access</p>
                    <span>Features:</span>
                    <ul class="feature-list">
                        <li>Organize videos</li>
                        <li>Schedule blocks</li>
                        <li>Categorise videos and blocks</li>
                        <li>Auto generate description</li>
                        <li>Read and hide comments</li>
                        <li>Automaticly update videos</li>
                        <li>Answer comments</li>
                        <li>Generate titles (In-development)</li>
                    </ul>
                </div>                        
                <div class="one-third column price-card">
                    <?php if (!isset($user['id'])) { ?>
                        <h5>Give read access:</h5>
                        <a href="https://accounts.google.com/o/oauth2/auth?client_id=326206426889-v2nr3cr60pie5o6rdhv11schbrfl5340.apps.googleusercontent.com&redirect_uri=https://youtool.app/redirect.php&scope=https://www.googleapis.com/auth/youtube.readonly&response_type=code&access_type=offline">
                            <img src="images/web_dark_rd_ctn.svg" id="signin_button"/>
                        </a>
                        <hr/>
                    <?php } ?>

                    <h5>Give write access:</h5>
                    <a href="https://accounts.google.com/o/oauth2/auth?client_id=326206426889-v2nr3cr60pie5o6rdhv11schbrfl5340.apps.googleusercontent.com&redirect_uri=https://youtool.app/redirect.php&scope=https://www.googleapis.com/auth/youtube.force-ssl&response_type=code&access_type=offline">
                        <img src="images/web_dark_rd_ctn.svg" id="signin_button"/>
                    </a><br/>

                    <?php if (isset($user['id'])) { ?>
                        <hr/>

                        <div id="payment_response"></div>

                        <label for="tick">Pay for usage <span id="month_display">1</span> months</label>
                        <input type="range" min="1" max="12" value="1" id="months" name="months" />       

                        <label>Price: <span id="price_display"><?php echo number_format($MONTHLY_PRICE, 2, '.', ''); ?></span> USD</label>

                        <div id="paypal-button-container"></div>
                    <?php } ?>
                </div>            
            </div>
        </div>
    </div>
    <?php if (isset($user['id'])) { ?>
    <script>
        const monthSelect = document.getElementById('months');
        const monthDisplay = document.getElementById('month_display');
        const priceDisplay = document.getElementById('price_display');
        const paymentResponse = document.getElementById('payment_response');
    
        monthSelect.addEventListener('input', function(e) {
            monthDisplay.innerHTML = e.target.value;
            priceDisplay.innerHTML = (e.target.value * <?php echo $MONTHLY_PRICE ?>).toLocaleString(undefined, {minimumFractionDigits: 2 });
        });

        paypal.Buttons({
            async createOrder() {
                const data = {
                    'months': monthSelect.value,
                    'op': 'create-paypal-order'
                }

                const response = await fetch("/paymentAjax.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data)
                });

                const order = await response.json();
                if (order?.status == 'FAILURE') {
                    paymentResponse.innerHTML = '<span style="color:red">' + order.message + '</span>';
                }
                return order.id;
            },                        
            async onApprove(data, actions) {
                try {
                    const postdata = {
                        'orderId': data.orderID,
                        'op': 'approve-paypal-order'
                    }

                    const response = await fetch("/paymentAjax.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(postdata)
                    });
                    const orderData = await response.json();

                    const errorDetail = orderData?.details?.[0];
                    if (orderData?.status == 'FAILURE') {                                    
                        paymentResponse.innerHTML = '<span style="color:red">' + orderData.message + '</span>';
                        return;
                    }

                    if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
                        return actions.restart();
                    } else if (errorDetail) {
                        throw new Error(`${errorDetail.description} (${orderData.debug_id})`);
                    } else if (!orderData.purchase_units) {
                        throw new Error(JSON.stringify(orderData));
                    } else {
                        const transaction =
                            orderData?.purchase_units?.[0]?.payments?.captures?.[0] ||
                            orderData?.purchase_units?.[0]?.payments?.authorizations?.[0];

                        paymentResponse.innerHTML = '<span style="color:green">' +
                            `Transaction ${transaction.status}: ${transaction.id}` + '</span>';
                    }
                } catch (error) {
                    console.error(error);
                    paymentResponse.innerHTML = '<span style="color:red">' +
                        `Sorry, your transaction could not be processed...<br><br>${error}` + '</span>';
                }
            },
            style: {
                layout: 'vertical',
                color:  'gold',
                shape:  'rect',
                label:  'pay'
            }
        }).render('#paypal-button-container');                    
    </script>                
    <?php } ?>
</body>
</html>