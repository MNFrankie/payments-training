<?php

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

require_once('connect.php');

//recieve the phone number
$phone= $_POST['phone_number'];
//Recieve the amount
$amount=$_POST['amount'];

//appending 254 to the phone number
$phone = '+254'.substr($phone,-9);

//appending KES to the amount
//$amount='KES '.$amount;

/*check whether the user exists, if the user exits get the user_id, if not create the user
and get the user id */
$user_id = createUser($phone);

//we need to store the order of the airtime
//create order

createOrder($user_id,$amount);




//building the recipients array
$recipients = array();

$data['phoneNumber']=$phone;
$data['amount'] = $amount;

array_push($recipients,$data);



//sending the airtime
sendAirtime($recipients);

function createUser($phone)
{
    //check if the user exists
    $query = mysql_query("SELECT id,phone FROM users WHERE phone='$phone'");
    if (mysql_num_rows($query) > 0) {
        $row = mysql_fetch_array($query);
        $id = $row['id'];
        return $id;
    } else {
        //create the user
        $result = mysql_query("INSERT INTO users (phone) VALUES ('$phone')");

        if ($result) {
            $query = mysql_query("SELECT id,phone FROM users WHERE phone='$phone'");
            $row = mysql_fetch_array($query);
            $id = $row['id'];
            return $id;
        }
    }
}


function createOrder($user_id,$amount_ordered){
    $query = mysql_query("INSERT INTO airtime_orders (user_id,amount_ordered) VALUES ('$user_id','$amount_ordered')");

    return $query;
}



function sendAirtime($recipients){

    require_once "AfricasTalkingGateway.php";

    //Specify your credentials
    $username = "username";
    $apiKey   = "apikey";

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
