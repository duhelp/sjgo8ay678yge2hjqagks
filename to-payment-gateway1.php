<?php
require_once 'razorpay-php/Razorpay.php';
// $keyId='rzp_test_RnFKpTup4T425G';
// $secretKey = 'mjkbNKV1rgPIPNhDR5D8ecyD';
use Razorpay\Api\Api;
$keyId='rzp_live_U1m9d9uBMh1IFj';

$secretKey = 'IGcvpcNMs3KMprZznhrMiI5g';
use app\modules\paytm\models\LibConfigPaytm;
use app\modules\paytm\models\LibEncdecPaytm;
use app\modules\payment\models\PaymentGateway;

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: 0");
$api = new Api($keyId,$secretKey);

$checkSum = "";
$paramList = array();
$ORDER_ID = $trans->id;
$config = LibConfigPaytm::getData();
// Create an array having all required parameters for creating checksum.
// $paramList["MID"] = $config->mid;
// $paramList["ORDER_ID"] = $trans->id;
// $paramList["CUST_ID"] = $trans->customerId;
// $paramList["INDUSTRY_TYPE_ID"] = $config->industry;
// $paramList["CHANNEL_ID"] = $config->channel;
// $paramList["TXN_AMOUNT"] = $fee;
// $paramList["WEBSITE"] = $config->website;
// $paramList["CALLBACK_URL"] = LibConfigPaytm::getCallBackUrl();
// $paramList["EMAIL"] = $trans->email;
// $paramList["MSISDN"] = $trans->mobile;
$order = $api->order->create(array(
    'receipt'=>$trans->id,
    'amount' =>$fee*100,
    'payment_capture' => 1,
    'currency' => 'INR',
));
//Here checksum string will return by getChecksumFromArray() function.
// $checkSum = LibEncdecPaytm::getChecksumFromArray($paramList, $config->key);

?>
<html>
<head>
    <title>Merchant Check Out Page</title>
</head>
<body>
<center><h1>Please do not refresh this page...</h1></center>
<!-- <form method="post" action="<?php  ?>" name="f1">
    <table border="1">
        <tbody>
        //<?php
        //foreach ($paramList as $name => $value) {
        //    echo '<input type="hidden" name="' . $name . '" value="' . $value . '">';
        //}
        //?>
        <input type="hidden" name="CHECKSUMHASH" value="<?php //echo $checkSum ?>">
        </tbody>
    </table>
    <script type="text/javascript">
        document.f1.submit();
    </script>
</form> -->

<button id="rzp-button1">Pay</button>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?php echo $keyId ?>", // Enter the Key ID generated from the Dashboard
    "amount": "<?php echo $order->amount ?>", // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
    "currency": "INR",
    "name": "Delhi University Fees",
    "description": "Fee Payment",
    "image": "https://www.du.ac.in/ducc/uploads/du/images/logo-du.png",
    "order_id": "<?php echo $order->id ?>", //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
    "callback_url": "<?php echo LibConfigPaytm::getCallBackUrl() ?>",
    "prefill": {
        "name": "Student",
        "email": "<?php echo $trans->email ?>",
        "contact": "<?php echo $trans->mobile ?>"
    },
    "notes": {
        "address": "Razorpay Corporate Office"
    },
    "theme": {
        "color": "#3399cc"
    }
};
var rzp1 = new Razorpay(options);

document.getElementById('rzp-button1').onclick = function(e){
    rzp1.open();
    e.preventDefault();
}
document.getElementById('rzp-button1').style.display="none";
document.getElementById('rzp-button1').click();

</script>
</body>
</html>
