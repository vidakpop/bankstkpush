<?php
function Alloworigins()
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }
    if ($_GET['type'] === 'grant') {
        $type = $_GET['type'];
        if ($type == "grant") {
            CreateHostspotUser();
            exit();
        }
        exit();
    }
    if ($_GET['type'] === 'reconect') {
        $type = $_GET['type'];
        if ($type == "reconect") {
            ReconnectUser();
            exit();
        }
        exit();
    }
    if ($_GET['type'] === 'voucher') {
        $type = $_GET['type'];
        if ($type == "voucher") {
            ReconnectVoucher();
            exit();
        }
        exit();
    }
    if ($_GET['type'] === 'query') {
        $type = $_GET['type'];
        if ($type == "query") {
            VerifyHotspot();
            exit();
        }
        exit();
    }
}
function ReconnectUser(){
    header('Content-Type: application/json');

    // Retrieve JSON data from POST request
    $rawData = file_get_contents('php://input');
    $postData = json_decode($rawData, true);

    // Check if required fields are present in postData
    if (!isset($postData['mpesacode'])) {
        echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Missing mpesacode field']);
        return;
    }

    // Extract mpesacode
    $mpesacode = $postData['mpesacode'];

    // Query the database using ORM to find user
    $user = ORM::for_table('tbl_payment_gateway')
        ->where('gateway_trx_id', $mpesacode)
        ->order_by_desc('id')
        ->find_one();

    if (!$user) {
        $data = array(['status' => 'error', "Resultcode" => "1", 'user' => "Not Found", 'message' => 'Invalid Mpesa Transaction code']);
        echo json_encode($data);
        exit();
    }

    // Extract user details
    $paymentstatus = $user['status'];
    $username = $user['username'];

    if ($paymentstatus == 2) {
        $check = ORM::for_table('tbl_user_recharges')
            ->where('username', $username)
            ->where('mpesacode', $mpesacode)
            ->order_by_desc('id')
            ->find_one();

        if ($check) {
            $status = $check['status'];
            if ($status == 'on') {
                $data = array(
                    "Resultcode" => "2",
                    "user" => "Active User",
                    "username" => $username,
                    "tyhK" => "1234",
                    "Message" => "We have verified your transaction under the mpesa Transaction $mpesacode. Please don't leave this page as we are redirecting you.",
                    "Status" => "success"
                );
            } elseif ($status == "off") {
                $data = array(
                    "Resultcode" => "3",
                    "user" => "Expired User",
                    "Message" => "We have verified your transaction under the mpesa Transaction $mpesacode. But your Package is already Expired. Please buy a new Package.",
                    "Status" => "danger"
                );
            } else {
                $data = array(
                    "Message" => "Unexpected status value",
                    "Status" => "error"
                );
            }
        } else {
            $data = array(
                "Message" => "Recharge information not found",
                "Status" => "error"
            );
        }
    } else {
        $data = array(
            "Message" => "Payment status not valid",
            "Status" => "error"
        );
    }

    echo json_encode($data);
    exit();  
}


function ReconnectVoucher() {
    header('Content-Type: application/json');

    // Retrieve JSON data from POST request
    $rawData = file_get_contents('php://input');
    $postData = json_decode($rawData, true);

    // Check if required fields are present in postData
    if (!isset($postData['mac'], $postData['voucherCode'])) {
        echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Missing mac or voucherCode field']);
        return;
    }

    // Extract mac and voucherCode from postData
    $mac = $postData['mac'];
    $voucherCode = $postData['voucherCode'];

    // Query the database using ORM to find voucher
    $voucher = ORM::for_table('tbl_voucher')
        ->where('code', $voucherCode)
        ->where('status', '0')
        ->order_by_desc('id')
        ->find_one();

    if (!$voucher) {
        $data = [
            'status' => 'error',
            'Resultcode' => '1',
            'voucher' => 'Not Found',
            'message' => 'Invalid Voucher code'
        ];
        echo json_encode($data);
        exit();
    }

    // Check if voucher is already used
    if ($voucher['status'] == '1') {
        $data = [
            'status' => 'error',
            'Resultcode' => '3',
            'voucher' => 'Used',
            'message' => 'Voucher code is already used'
        ];
        echo json_encode($data);
        exit();
    }

    // Retrieve plan and router information
    $planId = $voucher['id_plan'];
    $routername = $voucher['routers'];

    $router = ORM::for_table('tbl_routers')
        ->where('name', $routername)
        ->order_by_desc('id')
        ->find_one();

    if (!$router) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Router not found'
        ]);
        exit();
    }

    $routerId = $router['id'];

    // Check if plan and router exist
    if (!ORM::for_table('tbl_plans')->where('id', $planId)->count() > 0 || !ORM::for_table('tbl_routers')->where('id', $routerId)->count() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to process your request, please refresh the page'
        ]);
        exit();
    }

    // Check if user already exists
    $user = ORM::for_table('tbl_customers')->where('username', $mac)->find_one();
    if ($user) {
        // Update user's router ID
        $user->router_id = $routerId;
        $user->save();

        // Recharge user's package using Package::rechargeUser function
        if (Package::rechargeUser($user['id'], $routername, $planId, 'Voucher', $voucherCode)) {
            $data = [
                'status' => 'success',
                'Resultcode' => '2',
                'voucher' => 'activated',
                'message' => 'Voucher code has been activated'
            ];
            echo json_encode($data);
            exit();
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to recharge user package'
            ]);
            exit();
        }
    }

    // Create a new user if not already exists
    try {
        $defpass = '1234';
        $defmail = $mac . '@gmail.com';

        $createUser = ORM::for_table('tbl_customers')->create();
        $createUser->username = $mac;
        $createUser->password = $defpass;
        $createUser->fullname = $mac;
        $createUser->router_id = $routerId;
        $createUser->phonenumber = $mac;
        $createUser->pppoe_password = $defpass;
        $createUser->address = '';
        $createUser->email = $defmail;
        $createUser->service_type = 'Hotspot';

        $createUser->save();

        // Recharge user's package using Package::rechargeUser function
        if (Package::rechargeUser($createUser->id, $routername, $planId, 'Voucher', $voucherCode)) {
            $data = [
                'status' => 'success',
                'Resultcode' => '2',
                'voucher' => 'activated',
                'message' => 'Voucher code has been activated'
            ];
            echo json_encode($data);
            exit();
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to recharge user package'
            ]);
            exit();
        }
    } catch (Exception $e) {
        // Handle exceptions if any
        echo json_encode([
            'status' => 'error',
            'message' => 'Error processing request: ' . $e->getMessage()
        ]);
        exit();
    }
}

function VerifyHotspot()
{
    header('Content-Type: application/json');
    
    // Retrieve JSON data from POST request
    $rawData = file_get_contents('php://input');
    $postData = json_decode($rawData, true);
    
    // Check if required fields are present in postData
    if (!isset($postData['username'], $postData['CheckoutRequestID'])) {
        echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Missing phone_number or CheckoutRequestID fields']);
        return;
    }
    
    // Extract phone_number and CheckoutRequestID from postData
    $username = $postData['username'];
    $checkout = $postData['CheckoutRequestID'];
    
    // Query the database using ORM to find user
    $user = ORM::for_table('tbl_payment_gateway')
        ->where('username', $username)
        ->where('checkout', $checkout)
        ->order_by_desc('id')
        ->find_one();

    // Handle different scenarios based on user data
    if ($user) {
        $status = $user->status;
        $mpesacode = $user->gateway_trx_id;
        $res = $user->pg_paid_response;
        $username = $user->username;
        if ($status == 2 && !empty($mpesacode)) {
            // Case: Transaction successful with valid mpesacode
            $data = array(
                "Resultcode" => "3",
                "username" => $username,
                "tyhK" => "1234", // Example placeholder
                "Message" => "We have received your transaction under the mpesa Transaction $mpesacode. Please don't leave this page as we are redirecting you.",
                "Status" => "success"
            );
        } elseif ($res == "Not enough balance") {
            // Case: Insufficient balance
            $data = array(
                "Resultcode" => "2",
                "Message1" => "Insufficient Balance for the transaction",
                "Status" => "danger",
                "Redirect" => "Insufficient balance"
            );
        } elseif ($res == "Wrong Mpesa pin") {
            // Case: Wrong Mpesa pin entered
            $data = array(
                "Resultcode" => "2",
                "Message" => "You entered Wrong Mpesa pin, please resubmit",
                "Status" => "danger",
                "Redirect" => "Wrong Mpesa pin"
            );
        } elseif ($status == 4) {
            // Case: Transaction was cancelled
            $data = array(
                "Resultcode" => "2",
                "Message" => "You cancelled the transaction, you can enter phone number again to activate",
                "Status" => "info",
                "Redirect" => "Transaction Cancelled"
            );
        } elseif (empty($mpesacode)) {
            // Case: Waiting for pin input
            $data = array(
                "Resultcode" => "1",
                "Message" => "A payment popup has been sent to $phone. Please enter pin to continue (Please do not leave or reload the page until redirected)",
                "Status" => "primary"
            );
        }
        
        echo json_encode($data);
        exit();
    } else {
        // Case: User not found
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit();
    }
}




function CreateHostspotUser()
{ 
    header('Content-Type: application/json');
    $rawData = file_get_contents('php://input');
    $postData = json_decode($rawData, true);
    if (!isset($postData['phone_number'], $postData['plan_id'], $postData['router_id'], $postData['mac'])) {
        echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'missing required fields']);
        exit;
    }
   
   

 
    $phone = $postData['phone_number'];
    $planId = $postData['plan_id'];
    $routerId = $postData['router_id'];
    $mac = $postData['mac'];

    // echo json_encode(["status" => "error", "message" => $routerId]);
    // exit();
   
    $phone = (substr($phone, 0, 1) == '+') ? str_replace('+', '', $phone) : $phone;
    $phone = (substr($phone, 0, 1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;
    $phone = (substr($phone, 0, 1) == '7') ? preg_replace('/^7/', '2547', $phone) : $phone; //cater for phone number prefix 2547XXXX
    $phone = (substr($phone, 0, 1) == '1') ? preg_replace('/^1/', '2541', $phone) : $phone; //cater for phone number prefix 2541XXXX
    $phone = (substr($phone, 0, 1) == '0') ? preg_replace('/^01/', '2541', $phone) : $phone;
    $phone = (substr($phone, 0, 1) == '0') ? preg_replace('/^07/', '2547', $phone) : $phone;
    if (strlen($phone) !== 12) {
        echo json_encode(['status' => 'error', 'code' => 1, 'message' => 'Phone number ' . $phone . ' is invalid please confirm']);
        exit();
    }
    if (strlen($phone) == 12 && !empty($planId) && !empty($routerId)) {
        $PlanExist = ORM::for_table('tbl_plans')->where('id', $planId)->count() > 0;
        $RouterExist = ORM::for_table('tbl_routers')->where('id', $routerId)->count() > 0;
        if (!$PlanExist && !$RouterExist) {
            echo json_encode(["status" => "error", "message" => "Unable to precoess your request, please refresh the page"]);
            exit();
        }
        $Userexist = ORM::for_table('tbl_customers')->where('username', $mac)->find_one();
        if ($Userexist) {
            $Userexist->router_id = $routerId;
            $Userexist->save();
            InitiateStkpush($phone, $planId, $routerId, $mac);
            exit();
        }
  
    

        try {
            $defpass = '1234';
            $defmail = $phone . '@gmail.com';
        
            $createUser = ORM::for_table('tbl_customers')->create();
            $createUser->username = $mac;
            $createUser->password = $defpass;
            $createUser->fullname = $phone;
            $createUser->router_id = $routerId;
            $createUser->phonenumber = $phone;
            $createUser->pppoe_password = $defpass;
            $createUser->address = '';
            $createUser->email = $defmail;
            $createUser->service_type = 'Hotspot';
        
            $createUser->save();
        } catch (Exception $e) {
            // Handle the exception
            echo 'Error: ' . $e->getMessage();
        }
        if ($createUser->save()) {
            InitiateStkpush($phone, $planId, $routerId, $mac);
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "There was a system error when registering user, please contact support"]);
            exit();
        }
    }
}

function InitiateStkpush($phone, $planId, $routerId, $mac)
{

    $gateway = ORM::for_table('tbl_appconfig')
        ->where('setting', 'payment_gateway')
        ->find_one();

      


    $gateway = ($gateway) ? $gateway->value : null;
    if ($gateway == "MpesatillStk") {
        $url = (U . "plugin/initiatetillstk");
    } elseif ($gateway == "BankStkPush") {
              
        $url = $url = (U . "plugin/initiatebankstk");

    } elseif ($gateway == "MpesaPaybill") {
        $url = (U . "plugin/initiatePaybillStk");
    }
    $Planname = ORM::for_table('tbl_plans')
        ->where('id', $planId)
        ->order_by_desc('id')
        ->find_one();
    $Findrouter = ORM::for_table('tbl_routers')
        ->where('id', $routerId)
        ->order_by_desc('id')
        ->find_one();
    $rname = $Findrouter->name;
    $price = $Planname->price;
    $Planname = $Planname->name_plan;
    $Checkorders = ORM::for_table('tbl_payment_gateway')
        ->where('username', $mac)
        ->where('status', 1)
        ->order_by_desc('id')
        ->find_many();
    if ($Checkorders) {
        foreach ($Checkorders as $Dorder) {
            $Dorder->delete();
        }
    }

    
    try {
        $d = ORM::for_table('tbl_payment_gateway')->create();
        $d->username = $mac;
        $d->gateway = $gateway;
        $d->plan_id = $planId;
        $d->plan_name = $Planname;
        $d->routers_id = $routerId;
        $d->routers = $rname;
        $d->price = $price;
        $d->payment_method = $gateway;
        $d->payment_channel = $gateway;
        $d->created_date = date('Y-m-d H:i:s');
        $d->paid_date = date('Y-m-d H:i:s');
        $d->expired_date = date('Y-m-d H:i:s');
        $d->pg_url_payment = $url;
        $d->status = 1;
        $d->save();
    } catch (Exception $e) {
        // Handle the error, for example, log it or display a message
        error_log('Error saving payment gateway record: ' . $e->getMessage());
        // Optionally, you can rethrow the exception or handle it in another way
        throw $e;
    }
     


    // echo json_encode(["status" => "success", "phone" => $phone, "message" => "Registration complete,Please enter Mpesa Pin to activate the package"]);
    SendSTKcred($phone, $url, $mac);
}

function SendSTKcred($phone, $url, $mac)
{
    $link = $url;
    $fields = array(
        'username' => $mac,
        'phone' => $phone,
        'channel' => 'Yes',
    );
    $postvars = http_build_query($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    $result = curl_exec($ch);
}

Alloworigins();

