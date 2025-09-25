<?php

namespace App\Common;

use App\Models\Customer;
use App\Models\CampaignListItem;
use App\Common\DripCampaignHandler;

use Auth;

class BigCommerce
{
    protected static $bigCommerceWebhooks = [
        'webhook_50' => [
            'name' => 'Cart Abandoned',
            'scope' => 'store/cart/abandoned',
            'destination' => 'bigCommerce/webhook/50'
        ],
        'webhook_51' => [
            'name' => 'Cart Created',
            'scope' => 'store/cart/created',
            'destination' => 'bigCommerce/webhook/51'
        ],
        'webhook_52' => [
            'name' => 'Customer Created',
            'scope' => 'store/customer/updated',
            'destination' => 'bigCommerce/webhook/52'
        ],
        'webhook_53' => [
            'name' => 'Customer Updated',
            'scope' => 'store/customer/updated',
            'destination' => 'bigCommerce/webhook/53'
        ],
        'webhook_54' => [
            'name' => 'Customer Address Created',
            'scope' => 'store/customer/address/created',
            'destination' => 'bigCommerce/webhook/54'
        ],
        'webhook_55' => [
            'name' => 'Customer Address Updated',
            'scope' => 'store/customer/address/updated',
            'destination' => 'bigCommerce/webhook/55'
        ],
        'webhook_56' => [
            'name' => 'Order Created',
            'scope' => 'store/order/created',
            'destination' => 'bigCommerce/webhook/56'
        ],
        'webhook_57' => [
            'name' => 'Order Updated',
            'scope' => 'store/order/updated',
            'destination' => 'bigCommerce/webhook/57'
        ],
        'webhook_58' => [
            'name' => 'Order Status Updated',
            'scope' => 'store/order/statusUpdated',
            'destination' => 'bigCommerce/webhook/58'
        ],
        'webhook_59' => [
            'name' => 'Shipment Created',
            'scope' => 'store/shipment/created',
            'destination' => 'bigCommerce/webhook/59'
        ]
    ];

    public static function getWebhookDetailsByType($webhookId)
    {
        if (isset(self::$bigCommerceWebhooks['webhook_' . $webhookId])) {
            return self::$bigCommerceWebhooks['webhook_' . $webhookId];
        } else {
            return array();
        }
    }

    private static function curl($method = 'GET', $accessToken)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json",
                "X-Auth-Token: " . $accessToken
            ],
        ]);

        return $curl;
    }

    public static function createBigCommerceWebhook($webhookId, $campaignListId, $bigCommerceStore)
    {

        if (isset(self::$bigCommerceWebhooks['webhook_' . $webhookId])) {
            $webhookDetails = self::$bigCommerceWebhooks['webhook_' . $webhookId];
            try {
                $payload = [
                    'scope' => $webhookDetails['scope'],
                    // 'destination' => 'https://app.ottertext.com/dsaasaassdsdaasdsasadss',
                    'destination' => url('/') . '/' . $webhookDetails['destination'] . '/' . Auth::user()->client_id . '/' . $campaignListId,
                    'is_active' => true,
                    'events_history_enabled' => true,
                    'headers' => [
                        // to be used for authentication or validation of the incoming request
                        'secret' => config('app.BC_API_WEBHOOK_SECRET')
                    ],
                ];

                $curl = self::curl('POST', $bigCommerceStore->api_access_token);

                curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/" . $bigCommerceStore->store_hash . "/v3/hooks");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));

                $response = curl_exec($curl);
                $error = curl_error($curl);

                curl_close($curl);

                if ($error) {
                    return ['status' => 'error', 'message' => $error, 'data' => []];
                } else {
                    $response = json_decode($response, true);
                    if (isset($response['data']['id'])) {
                        return ['status' => 'success', 'message' => 'success', 'data' => $response['data']];
                    } else {
                        $finalError = '';
                        if (empty($response['errors'])) {
                            $finalError = $response['title'] . '. ';
                        }
                        foreach ($response['errors'] as $error) {
                            $finalError .= $error . '. ';
                        }
                        return ['status' => 'error', 'message' => $finalError, 'data' => []];
                    }
                }
            } catch (\Exception $e) {
                return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
            }
        } else {
            return ['status' => 'error', 'message' => 'Webhook topic not', 'data' => []];
        }
    }

    public static function deleteBigCommerceWebhook($webhookId, $bigCommerceStore)
    {
        try {
            $curl = self::curl('DELETE', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/" . $bigCommerceStore->store_hash . "/v3/hooks/{$webhookId}");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return ['status' => 'error', 'message' => $error, 'data' => []];
            } else {
                $response = json_decode($response, true);
                return ['status' => 'success', 'message' => 'success', 'data' => $response];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
        }
    }

    //store/cart/created
    /**
     * {"producer":"stores\/ytoxrhskys","hash":"169fe32e2ae0fd9f73105aedb19acedf438942e4","created_at":1714979815,"store_id":"1003163076","scope":"store\/cart\/created","data":{"type":"cart","id":"82fa182e-5da0-453e-920d-f272d85f8d65"}}  
     */
    public static function getCartDetails($data, $bigCommerceStore)
    {
        if ($data) {
            //get the cart id
            $object_id = $data['id'] ?? null;
            $object_type = $data['type'] ?? null;

            if ($object_id == null || $object_type == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the cart details
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v3/carts/$object_id");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response['data']['id'])) {
                    $response = $response['data'];
                    $customerInfo = NULL;
                    $customer = self::getCustomerDetails(array('id' => $response['customer_id'], 'type' => 'customer'), $bigCommerceStore);
                    if ($customer['status'] == 'success') {
                        if (isset($customer['customer']['id'])) {
                            $customerInfo = $customer['customer'];
                        }
                    }

                    $products = NULL;
                    if (isset($response['line_items']['physical_items'][0]['id']) && $response['line_items']['physical_items'][0]['id'] != '') {
                        $products = $response['line_items']['physical_items'];
                    }

                    if ($customerInfo == NULL) {
                        $response['billing_address']['id'] = 0;
                        $customerInfo = $response['billing_address'];
                    }

                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $customerInfo, 'order' => NULL, 'products' => $products, 'cart' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
                /*
                    {"data":{"id":"82fa182e-5da0-453e-920d-f272d85f8d65","customer_id":0,"channel_id":1,"email":"","currency":{"code":"PKR"},"tax_included":false,"base_amount":3500,"discount_amount":0,"manual_discount_amount":0,"cart_amount":3605,"coupons":[],"discounts":[{"id":"1114af8c-6486-4b82-9b0e-0494f4d7fdce","discounted_amount":0}],"line_items":{"physical_items":[{"id":"1114af8c-6486-4b82-9b0e-0494f4d7fdce","parent_id":null,"variant_id":77,"product_id":112,"sku":"SAM-001","name":"Sample Product","url":"https:\/\/amplicious.mybigcommerce.com\/sample-product\/","quantity":1,"taxable":true,"image_url":"https:\/\/cdn11.bigcommerce.com\/r-4b20dad619e29ebf3490f7f35369a8220637ce48\/themes\/ClassicNext\/images\/ProductDefault.gif","discounts":[],"coupons":[],"discount_amount":0,"coupon_amount":0,"original_price":3500,"list_price":3500,"sale_price":3500,"extended_list_price":3500,"extended_sale_price":3500,"is_require_shipping":true,"is_mutable":true}],"digital_items":[],"gift_certificates":[],"custom_items":[]},"created_time":"2024-05-06T07:16:55+00:00","updated_time":"2024-05-06T07:16:55+00:00","locale":"en"},"meta":[]}
                */
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    //store/order/created
    /**
     * {"producer":"stores\/ytoxrhskys","hash":"ad55b1f97565935ca6a530d4e30baba5c73698b2","created_at":1714980425,"store_id":"1003163076","scope":"store\/order\/created","data":{"type":"order","id":112}}
     */
    public static function getOrderDetails($data, $bigCommerceStore)
    {
        if ($data) {
            //get the order id
            $object_id = $data['id'] ?? null;
            $object_type = $data['type'] ?? null;

            if ($object_id == null || $object_type == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the order details
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v2/orders/$object_id");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response['id'])) {
                    $customerInfo = NULL;
                    $customer = self::getCustomerDetails(array('id' => $response['customer_id'], 'type' => 'customer'), $bigCommerceStore);
                    if ($customer['status'] == 'success') {
                        if (isset($customer['customer']['id'])) {
                            $customerInfo = $customer['customer'];
                        }
                    }

                    $products = NULL;
                    $products = self::getOrderProducts(array('id' => $response['id'], 'type' => 'products'), $bigCommerceStore);
                    if ($products['status'] == 'success') {
                        if (isset($products['products'][0]['id'])) {
                            $products = $products['products'];
                        }
                    }

                    if ($customerInfo == NULL) {
                        $response['billing_address']['id'] = 0;
                        $customerInfo = $response['billing_address'];
                    }

                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $customerInfo, 'order' => $response, 'products' => $products);
                } else {
                    return array('status' => 'error', 'message' => 'No Data', 'customer' => NULL, 'order' => NULL);
                }
                /*
                    {"id":112,"customer_id":0,"date_created":"Mon, 06 May 2024 07:27:05 +0000","date_modified":"Mon, 06 May 2024 07:27:06 +0000","date_shipped":"","status_id":7,"status":"Awaiting Payment","subtotal_ex_tax":"119.9500","subtotal_inc_tax":"123.5500","subtotal_tax":"3.6000","base_shipping_cost":"10.0000","shipping_cost_ex_tax":"10.0000","shipping_cost_inc_tax":"10.1000","shipping_cost_tax":"0.1000","shipping_cost_tax_class_id":2,"base_handling_cost":"0.0000","handling_cost_ex_tax":"0.0000","handling_cost_inc_tax":"0.0000","handling_cost_tax":"0.0000","handling_cost_tax_class_id":2,"base_wrapping_cost":"0.0000","wrapping_cost_ex_tax":"0.0000","wrapping_cost_inc_tax":"0.0000","wrapping_cost_tax":"0.0000","wrapping_cost_tax_class_id":3,"total_ex_tax":"129.9500","total_inc_tax":"133.6500","total_tax":"3.7000","items_total":1,"items_shipped":0,"payment_method":"Cash on Delivery","payment_provider_id":null,"payment_status":"","refunded_amount":"0.0000","order_is_digital":false,"store_credit_amount":"0.0000","gift_certificate_amount":"0.0000","ip_address":"39.42.27.1","ip_address_v6":"","geoip_country":"Pakistan","geoip_country_iso2":"PK","currency_id":1,"currency_code":"PKR","currency_exchange_rate":"1.0000000000","default_currency_id":1,"default_currency_code":"PKR","staff_notes":"","customer_message":"Consectetur laudantium est deserunt ea quos","discount_amount":"0.0000","coupon_discount":"0.0000","shipping_address_count":1,"is_deleted":false,"ebay_order_id":"0","cart_id":"b0b745f3-5c7d-45e0-a67e-4603e3169516","billing_address":{"first_name":"Ivory","last_name":"Keller","company":"Valenzuela Townsend Plc","street_1":"11 New Avenue","street_2":"Esse omnis cum porr","city":"Magni et voluptatem ","state":"Quis sed corrupti e","zip":"Sit dolores volupta","country":"Pakistan","country_iso2":"PK","phone":"+1 (752) 337-4022","email":"muro@mailinator.com","form_fields":[]},"is_email_opt_in":false,"credit_card_type":null,"order_source":"www","channel_id":1,"external_source":"","consignments":{"url":"https:\/\/api.bigcommerce.com\/stores\/ytoxrhskys\/v2\/orders\/112\/consignments","resource":"\/orders\/112\/consignments"},"products":{"url":"https:\/\/api.bigcommerce.com\/stores\/ytoxrhskys\/v2\/orders\/112\/products","resource":"\/orders\/112\/products"},"shipping_addresses":{"url":"https:\/\/api.bigcommerce.com\/stores\/ytoxrhskys\/v2\/orders\/112\/shipping_addresses","resource":"\/orders\/112\/shipping_addresses"},"coupons":{"url":"https:\/\/api.bigcommerce.com\/stores\/ytoxrhskys\/v2\/orders\/112\/coupons","resource":"\/orders\/112\/coupons"},"external_id":null,"external_merchant_id":null,"tax_provider_id":"BasicTaxProvider","customer_locale":"en","external_order_id":"","store_default_currency_code":"PKR","store_default_to_transactional_exchange_rate":"1.0000000000","custom_status":"Awaiting Payment"}
                */
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    //store/shipment/created
    /**
     * {"producer":"stores\/ytoxrhskys","hash":"bf374490b4434b3fd663da03730c0ed11aae701e","created_at":1714981515,"store_id":"1003163076","scope":"store\/shipment\/created","data":{"type":"shipment","id":1,"orderId":112}}
     */
    public static function getOrderShipmentDetails($data, $bigCommerceStore)
    {
        if ($data) {
            //get the shipment id
            $object_id = $data['id'] ?? null;
            $object_type = $data['type'] ?? null;
            $orderID = $data['orderId'] ?? null;

            if ($object_id == null || $object_type == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the shipment details
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v2/orders/$orderID/shipments/$object_id");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response['id'])) {
                    $customerInfo = NULL;
                    $orderInfo = NULL;
                    $products = NULL;

                    if (!empty($response['shipping_address'])) {
                        foreach ($response['shipping_address'] as $key => $val) {
                            $response['shipping_address_' . $key] = $val;
                        }
                    }

                    $shipedItems = array();
                    // \Log::info(json_encode($response['items']));
                    // \Log::info(json_encode($response['items'][0]));
                    if (isset($response['items'][0]['order_product_id'])) {
                        foreach ($response['items'] as $shipedItem) {
                            $shipedItems[] = $shipedItem['order_product_id'];
                        }
                    }

                    $order = self::getOrderDetails(array('id' => $response['order_id'], 'type' => 'order'), $bigCommerceStore);
                    if ($order['status'] == 'success') {
                        if (isset($order['order']['id'])) {
                            $orderInfo = $order['order'];
                        }
                        if (isset($order['customer']['id'])) {
                            $customerInfo = $order['customer'];
                        }
                        // \Log::info(json_encode($shipedItems));
                        if (isset($order['products'][0]['id'])) {
                            if (!empty($shipedItems)) {
                                foreach ($order['products'] as $orderProduct) {
                                    // \Log::info($orderProduct['id']);
                                    if (in_array($orderProduct['id'], $shipedItems)) {
                                        $products[] = $orderProduct;
                                    }
                                }
                            }
                        }
                    }
                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $customerInfo, 'order' => $orderInfo, 'products' => $products, 'orderShipment' => $response);
                }
                /*
                    {"id":1,"order_id":112,"customer_id":5,"order_address_id":13,"date_created":"Mon, 06 May 2024 07:45:15 +0000","tracking_number":"365465464654654","merchant_shipping_cost":"0.0000","shipping_method":"Flat rate","comments":"","shipping_provider":"","tracking_carrier":"","tracking_link":"","billing_address":{"first_name":"Ivory","last_name":"Keller","company":"Valenzuela Townsend Plc","street_1":"11 New Avenue","street_2":"Esse omnis cum porr","city":"Magni et voluptatem ","state":"Quis sed corrupti e","zip":"Sit dolores volupta","country":"Pakistan","country_iso2":"PK","phone":"+1 (752) 337-4022","email":"muro@mailinator.com"},"shipping_address":{"first_name":"Ivory","last_name":"Keller","company":"Valenzuela Townsend Plc","street_1":"11 New Avenue","street_2":"Esse omnis cum porr","city":"Magni et voluptatem ","state":"Quis sed corrupti e","zip":"Sit dolores volupta","country":"Pakistan","country_iso2":"PK","phone":"+1 (752) 337-4022","email":"muro@mailinator.com"},"items":[{"order_product_id":14,"product_id":97,"quantity":1}],"generated_tracking_link":"","shipping_provider_display_name":"Other"}
                */
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    //store/customer/created
    /**
     * {"producer":"stores\/ytoxrhskys","hash":"1a7511086923ccd73f01c5fad25a604bad04bfb9","created_at":1714980857,"store_id":"1003163076","scope":"store\/customer\/created","data":{"type":"customer","id":5,"origin_channel_id":1,"channel_ids":[1]}}
     */
    public static function getCustomerDetails($data, $bigCommerceStore)
    {
        if ($data) {
            //get the customer id
            $object_id = $data['id'] ?? null;
            $object_type = $data['type'] ?? null;

            if ($object_id == null || $object_type == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the customer details
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v2/customers/$object_id");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response['id'])) {
                    $address = self::getCustomerAddressDetails(array('id' => $response['id'], 'type' => 'address'), $bigCommerceStore);
                    if ($address['status'] == 'success') {
                        if (isset($address['customer']['id'])) {
                            foreach ($address['customer'] as $key => $val) {
                                if ($key != 'id') {
                                    $response[$key] = $val;
                                }
                            }
                        }
                    }
                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
                /*
                    {"id":5,"company":"Valenzuela Townsend Plc","first_name":"Ivory","last_name":"Keller","email":"muro@mailinator.com","phone":"+1 (752) 337-4022","form_fields":null,"date_created":"Mon, 06 May 2024 07:34:10 +0000","date_modified":"Mon, 06 May 2024 07:34:10 +0000","store_credit":"0.0000","registration_ip_address":"39.42.27.1","customer_group_id":0,"notes":"","tax_exempt_category":"","reset_pass_on_login":false,"accepts_marketing":false,"addresses":{"url":"https:\/\/api.bigcommerce.com\/stores\/ytoxrhskys\/v2\/customers\/5\/addresses","resource":"\/customers\/5\/addresses"}}
                */
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    //store/customer/address/created
    /**
     * {"producer":"stores\/ytoxrhskys","hash":"850743b0e9517dd64b3255dfddac20c8d1238e96","created_at":1714980856,"store_id":"1003163076","scope":"store\/customer\/address\/created","data":{"type":"customer","id":8,"address":{"customer_id":5}}}
     */
    public static function getCustomerAddressDetails($data, $bigCommerceStore)
    {
        if ($data) {
            //get the address id
            $object_id = $data['id'] ?? null;
            $object_type = $data['type'] ?? null;

            if ($object_id == null || $object_type == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the address details
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v2/customers/$object_id/addresses");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response[0]['id'])) {
                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $response[0]);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
                /*
                    [{"id":8,"customer_id":5,"first_name":"Ivory","last_name":"Keller","company":"Valenzuela Townsend Plc","street_1":"11 New Avenue","street_2":"Esse omnis cum porr","city":"Magni et voluptatem ","state":"Quis sed corrupti e","zip":"Sit dolores volupta","country":"Pakistan","country_iso2":"PK","phone":"+1 (752) 337-4022","address_type":"residential","form_fields":null}]
                */
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }


    public static function getOrderProducts($data, $bigCommerceStore)
    {
        if ($data) {
            //get the order id
            $object_id = $data['id'] ?? null;
            $object_type = $data['type'] ?? null;

            if ($object_id == null || $object_type == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the order details
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);

            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v2/orders/" . $object_id . "/products");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response[0]['id'])) {
                    return array('status' => 'success', 'message' => 'Data Found', 'products' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data', 'customer' => NULL, 'order' => NULL);
                }
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    public static function getCustomersList($data, $bigCommerceStore)
    {
        if ($data) {
            //get the customer list
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v3/customers?page=" . $data['page'] . "&limit=" . $data['limit'] . "");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                // \Log::info($response);
                if (isset($response['data'][0]['id'])) {
                    return array('status' => 'success', 'message' => 'Data Found', 'customers' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    public static function getOrdersList($data, $bigCommerceStore)
    {
        if ($data) {
            //get the customer list
            $curl = self::curl('GET', $bigCommerceStore->api_access_token);
            curl_setopt($curl, CURLOPT_URL, "https://api.bigcommerce.com/stores/$bigCommerceStore->store_hash/v2/orders?page=" . $data['page'] . "&limit=" . $data['limit'] . "&customer_id=" . $data['customer_id'] . "");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                // \Log::info($response);
                if (isset($response[0]['id'])) {
                    return array('status' => 'success', 'message' => 'Data Found', 'orders' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    public static function addOrUpdateCustomer($client, $customerData, $campaignList)
    {
        $returnArray = array();

        $phone = "+1" . preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $customerData['cellPhone']);
        $phone = str_replace(" ", "", $phone);

        if (strlen($phone) == 12 && substr($phone, 0, 2) == '+1') {

            $customer = Customer::where('phone', $phone)->where('client_id', $client->id)->first();

            if (!isset($customer->id)) {
                $customer = new Customer();
                $customer->optincheck = 1;
                $existingUser = false;
            } else {
                $existingUser = true;
            }

            if (isset($customerData['first_name']) && $customerData['first_name'] != '') {
                $customer->first_name = $customerData['first_name'];
            }

            if (isset($customerData['last_name']) && $customerData['last_name'] != '') {
                $customer->last_name = $customerData['last_name'];
            }

            if (isset($customerData['email']) && $customerData['email'] != '') {
                $customer->email = $customerData['email'];
            }

            if (isset($customerData['company']) && $customerData['company'] != '') {
                $customer->company = $customerData['company'];
            }

            if (isset($customerData['street_1']) && $customerData['street_1'] != '') {
                $customer->address = $customerData['street_1'];
            }

            // new

            if (isset($customerData['street_2']) && $customerData['street_2'] != '') {
                $customer->street_2 = $customerData['street_2'];
            }

            if (isset($customerData['city']) && $customerData['city'] != '') {
                $customer->city = $customerData['city'];
            }

            if (isset($customerData['state']) && $customerData['state'] != '') {
                $customer->state = $customerData['state'];
            }

            if (isset($customerData['zip']) && $customerData['zip'] != '') {
                $customer->zip = $customerData['zip'];
            }

            if (isset($customerData['country']) && $customerData['country'] != '') {
                $customer->country = $customerData['country'];
            }

            if (isset($customerData['registration_ip_address']) && $customerData['registration_ip_address'] != '') {
                $customer->registration_ip_address = $customerData['registration_ip_address'];
            }

            $customer->phone = $phone;
            $customer->client_id = $client->id;
            // echo 'here';exit;
            $customer->save();

            if ($existingUser) {
                $campaignListItem = CampaignListItem::where('customer_id', $customer->id)->where('campaign_list_id', $campaignList->id)->first();
                if (!isset($campaignListItem->id)) {
                    self::addToCampaignList($client, $customer, $campaignList);
                }
            } else {
                self::addToCampaignList($client, $customer, $campaignList);
            }

            $returnArray = array('existingUser' => $existingUser, 'customer' => $customer);
        } else {
            $returnArray = array('existingUser' => false, 'customer' => array());
        }

        return $returnArray;
    }

    public static function addToCampaignList($client, $customer, $campaignList)
    {
        $campaignListItemNew = new CampaignListItem();
        $campaignListItemNew->client_id = $client->id;
        $campaignListItemNew->customer_id = $customer->id;
        $campaignListItemNew->campaign_list_id = $campaignList->id;
        $campaignListItemNew->first_name = $customer->first_name;
        $campaignListItemNew->last_name = $customer->last_name;
        $campaignListItemNew->email = $customer->email;
        $campaignListItemNew->phone = $customer->phone;
        $campaignListItemNew->dob = $customer->dob;
        $campaignListItemNew->optincheck = $customer->optincheck;

        if($campaignList->campaign_id != '') {
            $campaignListItemNew->campaign_id = $campaignList->campaign_id;
        }

        $campaignListItemNew->save();

        // Check and add Drip message
        if($campaignList->campaign_id != '') {
            DripCampaignHandler::addFirstDripMessage($campaignList->campaign_id, $campaignListItemNew->id, $client, $customer);
        }
    }
}
