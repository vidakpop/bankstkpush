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
        if ($type == "verify") {
            VerifyHotspot();
            exit();
        } elseif ($type == "grant") {
            CreateHostspotUser();
            exit();
        }
        exit();
    }
}


function VerifyHotspot()
{
    $phone = $_POST['phone_number'];
    $user = ORM::for_table('tbl_payment_gateway')
        ->where('username', $phone)
        ->order_by_desc('id')
        ->find_one();
    if ($user) {
        $status = $user->status;
        $mpesacode = $user->gateway_trx_id;
        $res = $user->pg_paid_response;
        if ($status == 2 && !empty($mpesacode)) {
            $data = array(
                "Resultcode" => "3",
                "phone" => $phone,
                "tyhK" => "1234",
                "Message" => "We have received your transation under the mpesa Transaction $mpesacode,Please don't leave this page as we are redirecting you",
                "Status" => "success"
            );
            echo json_encode($data);
            exit();
        }
        if ($res == "Not enough balance") {
            $data = array(
                "Resultcode" => "2",
                "Message1" => "Insuficient Balance for the transaction",
                "Status" => "danger",
                "Redirect" => "Insuficient balance"

            );
            echo    $message = json_encode($data);
            exit();
        }
        if ($res == "Wrong Mpesa pin") {
            $data = array(
                "Resultcode" => "2",
                "Message" => " You entered Wrong Mpesa pin, please resubmit",
                "Status" => "danger",
                "Redirect" => "Wrong Mpesa pin"

            );
            echo    $message = json_encode($data);
            exit();
        }
        if ($status == 4) {
            $data = array(
                "Resultcode" => "2",
                "Message" => "You cancelled the transation, you can enter phone number again to activate",
                "Status" => "info",
                "Redirect" => "Transaction Cancelled"
            );
            echo $message = json_encode($data);
            exit();
        }


        if (empty($mpesacode)) {
            $data = array(
                "Resultcode" => "1",
                "Message" => "A payment pop up has been sent to $phone, Please enter pin to continue(Please do not leave  or reload the page untill redirected)",
                "Status" => "primary"
            );
            echo $message = json_encode($data);
            exit();
        }
    }
}



function CreateHostspotUser()
{ 
    header('Content-Type: application/json');
    $rawData = file_get_contents('php://input');
    $postData = json_decode($rawData, true);
    if (!isset($postData['phone_number'], $postData['plan_id'], $postData['router_id'])) {
        echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'missing required fields']);
        exit;
    }
   
   

 
    $phone = $postData['phone_number'];
    $planId = $postData['plan_id'];
     $routerId = $postData['router_id'];

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
        $Userexist = ORM::for_table('tbl_customers')->where('username', $phone)->find_one();
        if ($Userexist) {
            $Userexist->router_id = $routerId;
            $Userexist->save();
            InitiateStkpush($phone, $planId, $routerId, $mac_address);
            exit();
        }
  
    

try {
    $defpass = '1234';
    $defaddr = $mac_address;
    $defmail = $phone . '@gmail.com';

    $createUser = ORM::for_table('tbl_customers')->create();
    $createUser->username = $phone;
    $createUser->password = $defpass;
    $createUser->fullname = $phone;
    $createUser->router_id = $routerId;
    $createUser->phonenumber = $phone;
    $createUser->pppoe_password = $defpass;
    $createUser->address = $defaddr;
    $createUser->email = $defmail;
    $createUser->service_type = 'Hotspot';

    $createUser->save();
} catch (Exception $e) {
    // Handle the exception
    echo 'Error: ' . $e->getMessage();
}


        if ($createUser->save()) {
            InitiateStkpush($phone, $planId, $routerId, $mac_address);
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "There was a system error when registering user, please contact support"]);
            exit();
        }
    }
}

function InitiateStkpush($phone, $planId, $routerId, $mac_address)
{

    $gateway = ORM::for_table('tbl_appconfig')
        ->where('setting', 'payment_gateway')
        ->find_one();

      


    $gateway = ($gateway) ? $gateway->value : null;
    if ($gateway == "MpesatillStk") {
        $url = "https://isp.speedcomwifi.co.ke/plugin/initiatetillstk";
    } elseif ($gateway == "BankStkPush") {
              
        $url = $url = (U . "plugin/initiatebankstk");

    } elseif ($gateway == "MpesaPaybill") {
        $url = "https://isp.speedcomwifi.co.ke/plugin/initiatePaybillStk";
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
        ->where('username', $phone)
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
        $d->username = $phone;
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
    SendSTKcred($phone, $url);
}

function SendSTKcred($phone, $url)
{
    $link = $url;
    $fields = array(
        'username' => $phone,
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

