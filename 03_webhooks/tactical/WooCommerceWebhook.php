<?php

namespace App\Common;

use App\Models\Customer;
use App\Models\CampaignListItem;
use App\Common\TelnyxHandler;
use App\Common\DripCampaignHandler;
use App\Services\EmailLookUp;

use Auth;

class WooCommerce
{
    protected static $sourceName = 'woocommerce';

    protected static $wooCommerceWebhooks = [
        'webhook_52' => [
            'name' => 'Customer Created',
            'scope' => 'customer.created',
            'destination' => 'wooCommerce/webhook/52'
        ],
        'webhook_53' => [
            'name' => 'Customer Updated',
            'scope' => 'customer.updated',
            'destination' => 'wooCommerce/webhook/53'
        ],
        'webhook_56' => [
            'name' => 'Order Created',
            'scope' => 'order.created',
            'destination' => 'wooCommerce/webhook/56'
        ],
        'webhook_57' => [
            'name' => 'Order Updated',
            'scope' => 'order.updated',
            'destination' => 'wooCommerce/webhook/57'
        ],
        'webhook_58' => [
            'name' => 'Order Updated',
            'scope' => 'order.updated',
            'destination' => 'wooCommerce/webhook/58'
        ]
    ];

    public static function getWebhookDetailsByType($webhookId)
    {
        if (isset(self::$wooCommerceWebhooks['webhook_' . $webhookId])) {
            return self::$wooCommerceWebhooks['webhook_' . $webhookId];
        } else {
            return array();
        }
    }

    private static function curl($method = 'GET', $consumerKey, $consumerSecret)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: Basic " . base64_encode($consumerKey . ":" . $consumerSecret)
            ],
        ]);
        return $curl;
    }

    public static function createWooCommerceWebhook($webhookId, $campaignListId, $wooCommerceStore)
    {
        if (isset(self::$wooCommerceWebhooks['webhook_' . $webhookId])) {
            $webhookDetails = self::$wooCommerceWebhooks['webhook_' . $webhookId];
            try {
                $payload = [
                    'name' => $webhookDetails['scope'],
                    'topic' => $webhookDetails['scope'],
                    'delivery_url' => url('/') . '/' . $webhookDetails['destination'] . '/' . Auth::user()->client_id . '/' . $campaignListId,
                ];

                $curl = self::curl('POST', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);

                curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/webhooks");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));

                $response = curl_exec($curl);
                $error = curl_error($curl);

                curl_close($curl);
                \Log::info('createWooCommerceWebhook');
                \Log::info($response);
                \Log::info($error);
                if ($error) {
                    return ['status' => 'error', 'message' => $error, 'data' => []];
                } else {
                    $response = json_decode($response, true);
                    if (isset($response['id'])) {
                        return ['status' => 'success', 'message' => 'success', 'data' => $response];
                    } else {
                        return ['status' => 'error', 'message' => @$response['message'], 'data' => []];
                    }
                }
            } catch (\Exception $e) {
                return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
            }
        } else {
            return ['status' => 'error', 'message' => 'Webhook topic not', 'data' => []];
        }
    }

    public static function updateWooCommerceWebhook($wooCommerceStore, $webhookId)
    {
        try {
            $payload = [
                'status' => 'active'
            ];

            $curl = self::curl('PUT', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);

            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/webhooks/$webhookId");
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($curl);
            \Log::info($response);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return ['status' => 'error', 'message' => $error, 'data' => []];
            } else {
                $response = json_decode($response, true);
                if (isset($response['id'])) {
                    return ['status' => 'success', 'message' => 'success', 'data' => $response];
                } else {
                    return ['status' => 'error', 'message' => @$response['message'], 'data' => []];
                }
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public static function listWooCommerceWebhook($wooCommerceStore)
    {
        try {

            $curl = self::curl('GET', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);

            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/webhooks");

            $response = curl_exec($curl);
            $error = curl_error($curl);
            // \Log::info($response);
            curl_close($curl);

            if ($error) {
                return ['status' => 'error', 'message' => $error, 'data' => []];
            } else {
                $response = json_decode($response, true);
                if (isset($response[0]['id'])) {
                    return ['status' => 'success', 'message' => 'success', 'data' => $response];
                } else {
                    return ['status' => 'error', 'message' => @$response['message'], 'data' => []];
                }
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public static function deleteWooCommerceWebhook($webhookId, $wooCommerceStore)
    {
        try {
            $payload = [
                'force' => true
            ];

            $curl = self::curl('DELETE', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);
            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/webhooks/{$webhookId}");
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));

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

    public static function getOrderDetails($data, $wooCommerceStore)
    {
        if ($data) {
            //get the order id
            $object_id = $data['id'] ?? null;

            if ($object_id == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the order details
            $curl = self::curl('GET', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);
            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/orders/{$object_id}");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response['id'])) {
                    $customerInfo = NULL;
                    $customer = self::getCustomerDetails(array('id' => $response['customer_id'], 'type' => 'customer'), $wooCommerceStore);
                    if ($customer['status'] == 'success') {
                        if (isset($customer['customer']['id'])) {
                            $customerInfo = $customer['customer'];
                        }
                    }

                    if ($customerInfo == NULL) {
                        $response['billing_address']['id'] = 0;
                        $customerInfo = $response['billing'];
                    }

                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $customerInfo, 'order' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data', 'customer' => NULL, 'order' => NULL);
                }
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    public static function getCustomerDetails($data, $wooCommerceStore)
    {
        if ($data) {
            //get the customer id
            $object_id = $data['id'] ?? null;

            if ($object_id == null) {
                return array('status' => 'error', 'message' => 'No Data');
            }
            //get the customer details
            $curl = self::curl('GET', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);
            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/customers/{$object_id}");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response['id'])) {
                    foreach ($response['billing'] as $key => $val) {
                        if ($key != 'id') {
                            $response[$key] = $val;
                        }
                    }
                    return array('status' => 'success', 'message' => 'Data Found', 'customer' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    public static function getCustomersList($data, $wooCommerceStore)
    {
        if ($data) {
            //get the customer list
            $curl = self::curl('GET', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);
            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/customers?page=" . $data['page'] . "&per_page=" . $data['limit'] . "&role=customer");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
                if (isset($response[0]['id'])) {
                    return array('status' => 'success', 'message' => 'Data Found', 'customers' => $response);
                } else {
                    return array('status' => 'error', 'message' => 'No Data');
                }
            }
        } else {
            return array('status' => 'error', 'message' => 'No Data');
        }
    }

    public static function getOrdersList($data, $wooCommerceStore)
    {
        if ($data) {
            //get the customer list
            $curl = self::curl('GET', $wooCommerceStore->api_client_id, $wooCommerceStore->api_client_secret);
            curl_setopt($curl, CURLOPT_URL, rtrim($wooCommerceStore->store_hash, '/') . "/wp-json/wc/v3/orders?page=" . $data['page'] . "&per_page=" . $data['limit'] . "&customer=" . $data['customer_id'] . "");

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                return array('status' => 'error', 'message' => $error);
            } else {
                $response = json_decode($response, true);
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

        $phone = "+1" . preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $customerData['phone']);
        $phone = str_replace(" ", "", $phone);
        $phone = (strlen($phone) == 12 && substr($phone, 0, 2) == '+1') ? $phone : null;

        $email = trim($customerData['email'] ?? '') ?: null;

        if ($phone || $email) {

            $customer = null;
            if ($phone != '') {
                $customer = Customer::where('phone', $phone)->where('client_id', $client->id)->first();
            } else if ($email) {
                $customer = Customer::where('email', $email)->where('client_id', $client->id)->first();
            }

            if ($customer && isset($customer->id)) {
                $campaignListItem = CampaignListItem::where('customer_id', $customer->id)->where('campaign_list_id', $campaignList->id)->first();
                if (!isset($campaignListItem->id)) {
                    self::addToCampaignList($client, $customer, $campaignList);
                }
                $returnArray = array('existingUser' => true, 'customer' => $customer);
            } else {
                $customer = new Customer();
                $customer->tcpa_source = self::$sourceName;

                if (isset($customerData['first_name']) && $customerData['first_name'] != '') {
                    $customer->first_name = $customerData['first_name'];
                }

                if (isset($customerData['last_name']) && $customerData['last_name'] != '') {
                    $customer->last_name = $customerData['last_name'];
                }

                if (isset($customerData['company']) && $customerData['company'] != '') {
                    $customer->company = $customerData['company'];
                }

                if (isset($customerData['address_1']) && $customerData['address_1'] != '') {
                    $customer->address = $customerData['address_1'];
                }

                // new

                if (isset($customerData['address_2']) && $customerData['address_2'] != '') {
                    $customer->street_2 = $customerData['address_2'];
                }

                if (isset($customerData['city']) && $customerData['city'] != '') {
                    $customer->city = $customerData['city'];
                }

                if (isset($customerData['state']) && $customerData['state'] != '') {
                    $customer->state = $customerData['state'];
                }

                if (isset($customerData['postcode']) && $customerData['postcode'] != '') {
                    $customer->zip = $customerData['postcode'];
                }

                if (isset($customerData['country']) && $customerData['country'] != '') {
                    $customer->country = $customerData['country'];
                }

                if (isset($customerData['role']) && $customerData['role'] != '') {
                    $customer->cms_role = $customerData['role'];
                }

                $customer->client_id = $client->id;

                $customer->phone = $phone;
                $customer->email = $email;
                $customer->optincheck = 1;

                $optincheck = 3;

                if ($client->is_firearms == 2) {
                    $optincheck = 1;
                }

                if ($phone) {
                    $lookupCost = TelnyxHandler::getLookupCost($client);

                    $lookupData = array(
                        'phone_carrier' => NULL,
                        'lookup_cost' => $lookupCost,
                        'lookup_date' => date('Y-m-d H:i:s'),
                        'optincheck' => $optincheck
                    );
                    $numberLookup = TelnyxHandler::numberLookup($customer->phone, $client->id, 'yes', $lookupCost);

                    if ($numberLookup['status'] == 'success' && isset($numberLookup['data']['carrier']['name']) && $numberLookup['data']['carrier']['name'] != '') {
                        $lookupData['phone_carrier'] = $numberLookup['data']['carrier']['name'];
                        $lookupData['normalized_carrier'] = $numberLookup['normalizedCarrier'];
                        $customer->normalized_carrier = $numberLookup['normalizedCarrier'];
                    } else {
                        $lookupData['optincheck'] = 5;
                        $customer->tcpa_status = 'optin_failed';
                        $customer->tcpa_optin_failed_reason = 'failed_lookup';
                    }

                    $customer->optincheck = $lookupData['optincheck'];
                    $customer->phone_carrier = $lookupData['phone_carrier'];
                    $customer->lookup_cost = $lookupData['lookup_cost'];
                    $customer->lookup_date = $lookupData['lookup_date'];
                }

                if ($email) {
                    $customer = EmailLookUp::singleCheckCustomer($customer);
                }

                $customer->save();

                self::addToCampaignList($client, $customer, $campaignList, $lookupData ?? null);

                $returnArray = array('existingUser' => false, 'customer' => $customer);
            }
        } else {
            $returnArray = array('existingUser' => false, 'customer' => array());
        }

        return $returnArray;
    }

    public static function addToCampaignList($client, $customer, $campaignList, $lookupData = NULL)
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

        if (isset($lookupData['optincheck'])) {
            $campaignListItemNew->optincheck = $lookupData['optincheck'];
            $campaignListItemNew->lookup_cost = $lookupData['lookup_cost'];
            $campaignListItemNew->lookup_date = $lookupData['lookup_date'];
        } else {
            $campaignListItemNew->optincheck = $customer->optincheck;
        }

        if ($campaignList->campaign_id != '') {
            $campaignListItemNew->campaign_id = $campaignList->campaign_id;
        }

        $campaignListItemNew->save();

        // Check and add Drip message
        if ($campaignList->campaign_id != '') {
            DripCampaignHandler::addFirstDripMessage($campaignList->campaign_id, $campaignListItemNew->id, $client, $customer);
        }
    }
}