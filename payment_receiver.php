<?php
//this is just for error reporting purposes. Comment this out when you deploy your code
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
//include your connection. This is where we start.
require_once('connect.php');
$details = json_decode($_REQUEST);
//test JSON
//$details = json_decode('{"type":"21","receipt":"JG30NO3O2Y","time":"1435934580","phonenumber":"0728355429","name":"LEONARD KORIR","account":"","amount":"1000","postbalance":"50900","transactioncost":"0","note":"","secret":"12345"}');
// print_r($details);
// exit;
//get the phone from which the payment is coming from
$phone = $details->phonenumber;
//get the amount of the payment
$amount = substr($details->amount, 0, -2);
//This was a test to get the JSON Data
$result =  mysql_query("INSERT INTO post_details (details) VALUES ('$details')");
//get the use with this phone number
$phone = '+254'.substr($phone,-9);
//get user by phone number
$user = getUser($phone);
// print_r($user['id']);
// exit;
//$u;
//update user balance
// print_r($user);
// exit;
$new_balance = $user['balance'] + $amount;
updateUserBalance($user['id'], $new_balance);
//get the latest order by the current user and compare with the amount sent
$order = getOrder($user['id']);
$latest_order_amount = $order['amount_ordered'];
//check if the user new balance is greater than the ordered airtime
if($new_balance + 1 > $latest_order_amount){
    //send airtime
    //building the recipients array
    $recipients = array();
    $data = array();
    $data['phoneNumber']=$phone;
    $data['amount'] = "KES ".$latest_order_amount;
    array_push($recipients,$data);
//sending the airtime
    sendAirtime($recipients);
//reduce the user balance by the sent airtime amount
    $balance = $new_balance - $latest_order_amount;
// and update user balance
    updateUserBalance($user['id'], $balance);
    updateOrderStatus($order['id']);
    echo "your airtime is on its way";
    exit;
}else{
    //send SMS telling the user that he doesn't have sufficient money for the order
    $message = "Hey you, your have insufficient funds to proceed with this transaction";
    sendSMS($phone, $message);
    echo "Check your phone for an SMS";
    exit;
}
//top up the users account
//from the users account, remove amount equal to the airtime ordered
///send airtime
//nouns orders, users, account, airtime,
//verbs top up, store, make payments, send airtime
//users - phone, balance,
//order - user_id, amount, status
//transaction_log
//update order status
function updateOrderStatus($order_id){
    return mysql_query("UPDATE airtime_orders SET status=1 WHERE id='$order_id'");
}
function getOrder($user_id){
    // print_r($user_id);
    // exit;
    $query = mysql_query("SELECT id,amount_ordered FROM airtime_orders WHERE user_id='$user_id' AND status=0 ORDER BY id DESC LIMIT 1");
    if (mysql_num_rows($query) > 0) {
        $row = mysql_fetch_array($query);
        return $row;
    }else{
        return 0;
    }
}
function getUser($phone)
{
    //check if the user exists
    $query = mysql_query("SELECT id,phone,balance FROM users WHERE phone='$phone'");
    if (mysql_num_rows($query) > 0) {
        $row = mysql_fetch_array($query);
        return $row;
    } else {
        //create the user
        $result = mysql_query("INSERT INTO users (phone) VALUES ('$phone')");
        if ($result) {
            $query = mysql_query("SELECT id,phone,balance FROM users WHERE phone='$phone'");
            $row = mysql_fetch_array($query);
            return $row;
        }
    }
}
function updateUserBalance($user_id,$balance){
    return mysql_query("UPDATE users SET balance='$balance' WHERE user_id='$user_id'");
}
function sendAirtime($recipients){
    require_once "AfricasTalkingGateway.php";
    //Specify your credentials
    $username = "YourUsername";
    $apiKey   = "YourAPIKey";
    $recipientStringFormat = json_encode($recipients);
    //Create an instance of our awesome gateway class and pass your credentials
    $gateway = new AfricasTalkingGateway($username, $apiKey);
    try {
        $results = $gateway->sendAirtime($recipientStringFormat);
        foreach($results as $result) {
            echo $result->status;
            echo $result->amount;
            echo $result->phoneNumber;
            echo $result->discount;
            echo $result->requestId;
            //Error message is important when the status is not Success
            echo $result->errorMessage;
        }
    }
    catch(AfricasTalkingGatewayException $e){
        echo $e->getMessage();
    }
}
function sendSMS($recipient, $message){
    require_once('AfricasTalkingGateway.php');
// Specify your login credentials
    $username   = "lenykoskey";
    $apikey     = "";
    $recipients = $recipient;
    $gateway    = new AfricasTalkingGateway($username, $apikey);
    try
    {
        // Thats it, hit send and we'll take care of the rest.
        $results = $gateway->sendMessage($recipients, $message);
        foreach($results as $result) {
            // status is either "Success" or "error message"
            echo " Number: " .$result->number;
            echo " Status: " .$result->status;
            echo " MessageId: " .$result->messageId;
            echo " Cost: "   .$result->cost."\n";
        }
    }
    catch ( AfricasTalkingGatewayException $e )
    {
        echo "Encountered an error while sending: ".$e->getMessage();
    }
}
?>
