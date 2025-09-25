<?php

namespace App\Console\Commands;

// use App\Models\BillingAddress;
// use App\Models\ResourceTracker;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
// use App\Models\Source;
use App\Models\Store;
use App\Models\Client;
use App\Models\CampaignListItem;
use App\Common\BigCommerce;
use App\Common\TelnyxHandler;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BigCommerceInitialFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'bigcommerceinitialfetch:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch customers their orders and order items from BigCommerce API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stores = Store::where('initial_fetch', 'pending')->where('status', 1)->where('store_type', 'BigCommerce')->get();
        if (!empty($stores)) {
            foreach ($stores as $bigCommerceStore) {
                $client = Client::where('id', $bigCommerceStore->client_id)->with(['campaignlists' => function ($query) {
                    $query->where('list_type', 7);
                }])->first();

                if (isset($client->timezone)) {
                    date_default_timezone_set($client->timezone);
                }

                $campaignList = $client->campaignlists[0];
                if (isset($campaignList->id)) {
                    $data = [
                        'page' => (int) $bigCommerceStore->intial_fetch_current_page,
                        'limit' => 100
                    ];
                    $customerList = BigCommerce::getCustomersList($data, $bigCommerceStore);
                    if ($customerList['status'] == 'error' || (isset($customerList['meta']) && $customerList['meta']['pagination']['total_pages'] < $data['page'])) {
                        $bigCommerceStore->initial_fetch = 'completed';
                        $bigCommerceStore->save();

                        $details = array(
                            'client' => $client,
                            'type' => 'WooCommerce'
                        );

                        \Mail::to($client->email)->send(new \App\Mail\EcommerceFetchDone("BigCommerce Sync completed on OtterText", $details));
                    } else {
                        $bigCommerceStore->intial_fetch_current_page = $bigCommerceStore->intial_fetch_current_page + 1;
                        $bigCommerceStore->save();

                        $this->populate($client, $campaignList, $customerList, $bigCommerceStore);
                    }
                }
            }
        }
    }

    private function populate($client, $campaignList, $response, $bigCommerceStore)
    {
        $data = $response['customers']['data'] ?? [];
        foreach ($data as $_customer) {
            if (isset($_customer['phone']) && $_customer['phone'] != '') {
                $phone = "+1" . preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $_customer['phone']);
                $phone = str_replace(" ", "", $phone);
                // if(strlen($phone) == 12 && substr($phone, 0, 2) == '+1') {
                if (strlen($phone) == 12) {
                    $customerProcess = $this->addOrUpdateCustomer($client, $_customer, $campaignList);
                    $customer = $customerProcess['customer'];
                    $existingUser = $customerProcess['existingUser'];

                    if (isset($customer->id) && isset($customer->optincheck) && $customer->optincheck != 5) {
                        $this->handleOrdersAndProducts($customer, $client, $campaignList, $bigCommerceStore, $_customer['id']);
                    }
                }
            }
        }
    }

    private function handleOrdersAndProducts($customer, $client, $campaignList, $bigCommerceStore, $customer_id = null)
    {
        if ($customer_id == null) {
            return;
        }

        $data = [
            'page' => 1,
            'limit' => 250,
            'customer_id' => $customer_id
        ];

        $orderList = BigCommerce::getOrdersList($data, $bigCommerceStore);
        if ($orderList['status'] == 'success') {
            if (isset($orderList['orders'][0]['id'])) {
                foreach ($orderList['orders'] as $_order) {

                    $order = Order::where('store_order_id', $_order['id'])->where('client_id', $client->id)->first();
                    if (!isset($order->id)) {
                        $order = new Order();
                        $order->fill([
                            'client_id' => $client->id,
                            'customer_id' => $customer->id,
                            'store_id' => $bigCommerceStore->id,
                            'store_order_id' => $_order['id'],
                            'date_shipped' => Carbon::parse($_order['date_shipped'])->format('Y-m-d H:i:s'),
                            'status_id' => $_order['status_id'],
                            'status' => $_order['status'],
                            'subtotal_ex_tax' => $_order['subtotal_ex_tax'],
                            'subtotal_inc_tax' => $_order['subtotal_inc_tax'],
                            'subtotal_tax' => $_order['subtotal_tax'],
                            'base_shipping_cost' => $_order['base_shipping_cost'],
                            'shipping_cost_ex_tax' => $_order['shipping_cost_ex_tax'],
                            'shipping_cost_inc_tax' => $_order['shipping_cost_inc_tax'],
                            'shipping_cost_tax' => $_order['shipping_cost_tax'],
                            'shipping_cost_tax_class_id' => $_order['shipping_cost_tax_class_id'],
                            'base_handling_cost' => $_order['base_handling_cost'],
                            'handling_cost_ex_tax' => $_order['handling_cost_ex_tax'],
                            'handling_cost_inc_tax' => $_order['handling_cost_inc_tax'],
                            'handling_cost_tax' => $_order['handling_cost_tax'],
                            'handling_cost_tax_class_id' => $_order['handling_cost_tax_class_id'],
                            'base_wrapping_cost' => $_order['base_wrapping_cost'],
                            'wrapping_cost_ex_tax' => $_order['wrapping_cost_ex_tax'],
                            'wrapping_cost_inc_tax' => $_order['wrapping_cost_inc_tax'],
                            'wrapping_cost_tax' => $_order['wrapping_cost_tax'],
                            'wrapping_cost_tax_class_id' => $_order['wrapping_cost_tax_class_id'],
                            'total_ex_tax' => $_order['total_ex_tax'],
                            'total_inc_tax' => $_order['total_inc_tax'],
                            'total_tax' => $_order['total_tax'],
                            'items_total' => $_order['items_total'],
                            'items_shipped' => $_order['items_shipped'],
                            'payment_method' => $_order['payment_method'],
                            'payment_provider_id' => $_order['payment_provider_id'],
                            'payment_status' => $_order['payment_status'],
                            'refunded_amount' => $_order['refunded_amount'],
                            'order_is_digital' => $_order['order_is_digital'],
                            'store_credit_amount' => $_order['store_credit_amount'],
                            'gift_certificate_amount' => $_order['gift_certificate_amount'],
                            'ip_address' => $_order['ip_address'],
                            'ip_address_v6' => $_order['ip_address_v6'],
                            'geoip_country' => $_order['geoip_country'],
                            'geoip_country_iso2' => $_order['geoip_country_iso2'],
                            'currency_id' => $_order['currency_id'],
                            'currency_code' => $_order['currency_code'],
                            'currency_exchange_rate' => $_order['currency_exchange_rate'],
                            'default_currency_id' => $_order['default_currency_id'],
                            'default_currency_code' => $_order['default_currency_code'],
                            'staff_notes' => $_order['staff_notes'],
                            'customer_message' => $_order['customer_message'],
                            'discount_amount' => $_order['discount_amount'],
                            'coupon_discount' => $_order['coupon_discount'],
                            'shipping_address_count' => $_order['shipping_address_count'],
                            'is_deleted' => $_order['is_deleted'],
                            'ebay_order_id' => $_order['ebay_order_id'],
                            'cart_id' => $_order['cart_id'],
                            'is_email_opt_in' => $_order['is_email_opt_in'],
                            'credit_card_type' => $_order['credit_card_type'],
                            'order_source' => $_order['order_source'],
                            'channel_id' => $_order['channel_id'],
                            'external_source' => $_order['external_source'],
                            'external_id' => $_order['external_id'],
                            'external_merchant_id' => $_order['external_merchant_id'],
                            'tax_provider_id' => $_order['tax_provider_id'],
                            'customer_locale' => $_order['customer_locale'],
                            'external_order_id' => $_order['external_order_id'],
                            'store_default_currency_code' => $_order['store_default_currency_code'],
                            'store_default_to_transactional_exchange_rate' => $_order['store_default_to_transactional_exchange_rate'] ?? null,
                            'custom_status' => $_order['custom_status'],
                            'trigger_type' => 'Initial Sync'
                        ]);

                        $order->save();
                        if (isset($order->id)) {
                            $productList = BigCommerce::getOrderProducts(array('id' => $_order['id'], 'type' => 'products'), $bigCommerceStore);
                            if ($productList['status'] == 'success') {
                                if (isset($productList['products'][0]['id'])) {
                                    foreach ($productList['products'] as $_product) {
                                        $product = OrderProduct::where('order_id', $order->id)->where('store_product_id', $_product['id'])->where('client_id', $client->id)->first();
                                        if (!isset($product->id)) {
                                            $product = new OrderProduct();
                                            $product->fill([
                                                'client_id' => $client->id,
                                                'customer_id' => $customer->id,
                                                'store_id' => $bigCommerceStore->id,
                                                'order_id' => $order->id,
                                                'store_product_id' => $_product['id'],
                                                'variant_id' => $_product['variant_id'],
                                                'order_pickup_method_id' => $_product['order_pickup_method_id'],
                                                'order_address_id' => $_product['order_address_id'],
                                                'name' => $_product['name'],
                                                'name_customer' => $_product['name_customer'],
                                                'name_merchant' => $_product['name_merchant'],
                                                'sku' => $_product['sku'],
                                                'upc' => $_product['upc'],
                                                'type' => $_product['type'],
                                                'base_price' => $_product['base_price'],
                                                'price_ex_tax' => $_product['price_ex_tax'],
                                                'price_inc_tax' => $_product['price_inc_tax'],
                                                'price_tax' => $_product['price_tax'],
                                                'base_total' => $_product['base_total'],
                                                'total_ex_tax' => $_product['total_ex_tax'],
                                                'total_inc_tax' => $_product['total_inc_tax'],
                                                'total_tax' => $_product['total_tax'],
                                                'weight' => $_product['weight'],
                                                'width' => $_product['width'],
                                                'height' => $_product['height'],
                                                'depth' => $_product['depth'],
                                                'quantity' => $_product['quantity'],
                                                'base_cost_price' => $_product['base_cost_price'],
                                                'cost_price_inc_tax' => $_product['cost_price_inc_tax'],
                                                'cost_price_ex_tax' => $_product['cost_price_ex_tax'],
                                                'cost_price_tax' => $_product['cost_price_tax'],
                                                'is_refunded' => $_product['is_refunded'],
                                                'quantity_refunded' => $_product['quantity_refunded'],
                                                'refund_amount' => $_product['refund_amount'],
                                                'return_id' => $_product['return_id'],
                                                'wrapping_id' => $_product['wrapping_id'],
                                                'wrapping_name' => $_product['wrapping_name'],
                                                'base_wrapping_cost' => $_product['base_wrapping_cost'],
                                                'wrapping_cost_ex_tax' => $_product['wrapping_cost_ex_tax'],
                                                'wrapping_cost_inc_tax' => $_product['wrapping_cost_inc_tax'],
                                                'wrapping_cost_tax' => $_product['wrapping_cost_tax'],
                                                'wrapping_message' => $_product['wrapping_message'],
                                                'quantity_shipped' => $_product['quantity_shipped'],
                                                'event_name' => $_product['event_name'],
                                                'event_date' => $_product['event_date'],
                                                'fixed_shipping_cost' => $_product['fixed_shipping_cost'],
                                                'ebay_item_id' => $_product['ebay_item_id'],
                                                'ebay_transaction_id' => $_product['ebay_transaction_id'],
                                                'option_set_id' => $_product['option_set_id'],
                                                'parent_order_product_id' => $_product['parent_order_product_id'],
                                                'is_bundled_product' => $_product['is_bundled_product'],
                                                'bin_picking_number' => $_product['bin_picking_number'],
                                                'external_id' => $_product['external_id'],
                                                'fulfillment_source' => $_product['fulfillment_source'],
                                                'brand' => $_product['brand'],
                                                'gift_certificate_id' => $_product['gift_certificate_id'],
                                                'applied_discounts' => json_encode($_product['applied_discounts']),
                                                'product_options' => json_encode($_product['product_options']),
                                                'configurable_fields' => json_encode($_product['configurable_fields']),
                                                'discounted_total_inc_tax' => $_product['discounted_total_inc_tax'],
                                            ]);
                                            $product->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function addOrUpdateCustomer($client, $customerData, $campaignList)
    {
        $returnArray = array();

        $phone = "+1" . preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $customerData['phone']);
        $phone = str_replace(" ", "", $phone);

        if (strlen($phone) == 12 && substr($phone, 0, 2) == '+1') {

            $customer = Customer::where('phone', $phone)->where('client_id', $client->id)->first();

            if (!isset($customer->id)) {
                $customer = new Customer();

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

                $customer->phone = $phone;
                $customer->client_id = $client->id;
				
				$lookupCost = TelnyxHandler::getLookupCost($client);

                $lookupData = array(
                    'phone_carrier' => NULL,
                    'lookup_cost' => $lookupCost,
                    'lookup_date' => date('Y-m-d H:i:s'),
                    'optincheck' => 1
                );
                $numberLookup = TelnyxHandler::numberLookup($customer->phone, $client->id, 'yes', $lookupCost);
    
                if ($numberLookup['status'] == 'success' && isset($numberLookup['data']['carrier']['name']) && $numberLookup['data']['carrier']['name'] != '') {
                    $lookupData['phone_carrier'] = $numberLookup['data']['carrier']['name'];
					$lookupData['normalized_carrier'] = $numberLookup['normalizedCarrier'];
					$customer->normalized_carrier = $numberLookup['normalizedCarrier'];
                } else {
                    $lookupData['optincheck'] = 5;
                }
    
                $customer->optincheck = $lookupData['optincheck'];
                $customer->phone_carrier = $lookupData['phone_carrier'];
                $customer->lookup_cost = $lookupData['lookup_cost'];
                $customer->lookup_date = $lookupData['lookup_date'];

                $customer->save();

                $this->addToCampaignList($client, $customer, $campaignList, $lookupData);

                $returnArray = array('existingUser' => false, 'customer' => $customer);
            } else {
                $campaignListItem = CampaignListItem::where('customer_id', $customer->id)->where('campaign_list_id', $campaignList->id)->first();
                if (!isset($campaignListItem->id)) {
                    $this->addToCampaignList($client, $customer, $campaignList);
                }
                $returnArray = array('existingUser' => true, 'customer' => $customer);
            }
        } else {
            $returnArray = array('existingUser' => false, 'customer' => array());
        }

        return $returnArray;
    }

    public function addToCampaignList($client, $customer, $campaignList, $lookupData = NULL)
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

        if(isset($lookupData['optincheck'])) {
            $campaignListItemNew->optincheck = $lookupData['optincheck'];
            $campaignListItemNew->lookup_cost = $lookupData['lookup_cost'];
            $campaignListItemNew->lookup_date = $lookupData['lookup_date'];
        }else {
            $campaignListItemNew->optincheck = $customer->optincheck;
        }

        $campaignListItemNew->save();
    }
}
