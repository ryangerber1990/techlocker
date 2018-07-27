<?php
class Webtonic_Salesflow_Model_Observer
{


    protected  $startDate;
    //API CALL
    public function createsubscription($observer) {
        //TODO: Following Fields need to be added for API call
        //Title, Id number, OptIns, CCard Token, PaymentMethodID, VoucherDetails
        $email = $_POST['billing']['email'];
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $customer->loadByEmail($email);
        if(!$customer->getData()){

            //Load customer Details
            $firstname = $_POST['billing']['firstname'];
            $lastname  = $_POST['billing']['lastname'];
            $telephone = $_POST['billing']['telephone'];
            $title = null;
            $Id = null;
            $OptIns = null;
            $CCardToken = null;
            $PaymentMethodID = null;
            $VoucherDetails = null;
            //Load Cart Items
            $cart =  Mage::getModel('checkout/cart')->getQuote();
            $deals = array();
            foreach ($cart->getAllItems() as $item) {

                $productName = $item->getProduct()->getName();
                $productPrice = $item->getProduct()->getPrice();
                $productSKU = $item->getProduct()->getSKU();
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $productSKU)->getData();
                $productDescription = $product['description'];
                array_push($deals,array('Barcode'=>$productSKU,'Description'=>$productDescription,'Price'=>$productPrice));
            }

        }

        //TODO: Load Order Number
        $orderNumber = Mage::getSingleton( 'checkout/session' )->getQuote()->getData('entity_id'); //= $observer->getQuote()->getData('entity_id');
        //TODO: Where will this come from?
        $OptIns = array(array('ID'=>null,'Value'=>null), array('ID'=>null,'Value'=>null));

        //TODO: Where will these vouchers come from?
        $voucherDetails = array(array('Code'=>null),array('Code'=>null));
        $data = array('PersonalDetails'=>array('MSISDN'=>$telephone,'Title'=>null,'FirstName'=>$firstname,'LastName'=>$lastname,'EmailAddress'=>$email,
            'IDNumber'=>null, 'OptIns'=>$OptIns),'PaymentDetails'=>array('CreditCardToken'=>null,'PaymentMethodID'=>null,'VoucherDetails'=>$voucherDetails),
            'ProductDetails'=>array('OrderNumber'=>$orderNumber,'Deals'=>$deals));
        $url = 'http://silversurfer.ignition.co.za/ecommerce/techlockerweb/createsubscription';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);

        if($output === FALSE){
            echo 'cURL Error: '.curl_error($ch);
        }
        curl_close($ch);

    }

    //API CALL
    public function validatevouchers($observer){
        $data = $_POST['coupon_code'];
        $url = 'http://silversurfer.ignition.co.za/ecommerce/techlockerweb/validatevoucher';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);

        if($output === FALSE){
            echo 'cURL Error: '.curl_error($ch);
        }
        curl_close($ch);
    }

    //LOGIC CALL
    public  function checkamounts($observer){
        $quote =  Mage::getModel('checkout/session')->getQuote();
        $quoteData =  $quote->getData();
        //With discount
        $subTotal = $quoteData['subtotal'];

        $orderId = Mage::getSingleton('checkout/session')->getQuoteId();
        $order = Mage::getModel('sales/order')->load($orderId);
        $quote = Mage::getModel("sales/quote")->load($orderId);

        //TODO: IF Statement -- Update Payment method Will the custom ID's for the payment method be a custom attribute?
        if($subTotal<=0){
            //Voucher
            $PaymentMethodID = 5;
        }else{
            //Credit Card
            $PaymentMethodID = 2;
        }
    }


    //API CALL
    public function setshipped($observer){
        //TODO: Find a better way of getting order number
        $orderNumber = null;  // $observer->getOrder()->getData('entity_id');
        $OrderStatusDetailID = 85; //OrderStatusDetailID as per API Docs

        $url = 'http://silversurfer.ignition.co.za/ecommerce/techlockerweb/changeorderstatus';
        $data = array('OrderNumber'=>$orderNumber,'OrderStatusDetailID'=>$OrderStatusDetailID);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);

        if($output === FALSE){
            echo 'cURL Error: '.curl_error($ch);
        }
        curl_close($ch);


    }

    public function setinvoiced($observer)
    {

    }
    public function setDelievered($observer){
        $userID = null; //Replace with real value
        $logonToken = null; //must be generated
        $startDT = null; //start date - replace with real value
        $endDT = date("Y-m-d"); //Today
        $url = 'http://services.ramgroup.co.za/ramconnectv2/Tracking/TrackingWS.asmx?op=RAMShipperDelivered';
        $data = array('userID'=>$userID,'logonToken'=>$logonToken,'startDT'=>$startDT,'endDT'=>$endDT);
        //cURL For RAM here


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);

        if($output === FALSE){
            echo 'cURL Error: '.curl_error($ch);
        }
        curl_close($ch);

        unset($ch);
        unset($url);
        $url = 'http://silversurfer.ignition.co.za/ecommerce/techlockerweb/changeorderstatus';
        $OrderNumber = $observer->getOrder()->getData('entity_id');
        $OrderStatusDetailID = 29; //OrderStatusDetailID as per API Docs
        unset($data);
        $data = array('OrderNumber'=>$OrderNumber,'OrderStatusDetailID' => $OrderStatusDetailID);
        //cURL for IG here

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);

        if($output === FALSE){
            echo 'cURL Error: '.curl_error($ch);
        }
        curl_close($ch);

    }

    //API Authenticantion
    public function AuthenticateIG(){}
    public function AuthenticateRAM($userID,$password){

        $url = 'http://services.ramgroup.co.za/ramconnectv2/Tracking/TrackingWS.asmx?op=Logon';
        $data = array('userID'=>$userID,'password'=>$password);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_GET, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);

        if($output === FALSE){
            echo 'cURL Error: '.curl_error($ch);
        }
        curl_close($ch);

        return $output;

    }



}