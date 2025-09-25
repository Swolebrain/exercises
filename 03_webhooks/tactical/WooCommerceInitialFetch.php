<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Store;
use App\Models\Client;
use App\Common\WooCommerce;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WooCommerceInitialFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'woocommerceinitialfetch:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch customers their orders and order items from WooCommerce API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stores = Store::where('initial_fetch', 'pending')->where('status', 1)->where('store_type', 'WooCommerce')->get();
        if (!empty($stores)) {
            foreach ($stores as $wooCommerceStore) {
                $client = Client::where('id', $wooCommerceStore->client_id)->with(['campaignlists' => function ($query) {
                    $query->where('list_type', 7);
                }])->first();

                if (isset($client->timezone)) {
                    date_default_timezone_set($client->timezone);
                }

                $campaignList = $client->campaignlists[0];
                if (isset($campaignList->id)) {
                    // Woocommerce per page limit is 100 so cannot exceed that
                    $data = [
                        'page' => (int) $wooCommerceStore->intial_fetch_current_page,
                        'limit' => 100
                    ];
                    $customerList = WooCommerce::getCustomersList($data, $wooCommerceStore);
                    if ($customerList['status'] == 'error' || empty($customerList['customers']) || !isset($customerList['customers'][0]['id'])) {
                        $wooCommerceStore->initial_fetch = 'completed';
                        $wooCommerceStore->save();

                        $details = array(
                            'client' => $client,
                            'type' => 'WooCommerce'
                        );

                        \Mail::to($client->email)->send(new \App\Mail\EcommerceFetchDone("WooCommerce Sync completed on OtterText", $details));
                    } else {
                        $wooCommerceStore->intial_fetch_current_page = $wooCommerceStore->intial_fetch_current_page + 1;
                        $wooCommerceStore->save();

                        $this->populate($client, $campaignList, $customerList, $wooCommerceStore);
                    }
                }
            }
        }
    }

    private function populate($client, $campaignList, $response, $wooCommerceStore)
    {
        $data = $response['customers'] ?? [];
        foreach ($data as $_customer) {
            $customerEcommerceId = $_customer['id'];
            if(isset($_customer['billing'])) {
                $_customer = $_customer['billing'];
                $_customer['id'] = $customerEcommerceId;
            }
            if (isset($_customer['phone']) && $_customer['phone'] != '') {
                $phone = "+1" . preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $_customer['phone']);
                $phone = str_replace(" ", "", $phone);
                // if(strlen($phone) == 12 && substr($phone, 0, 2) == '+1') {
                if (strlen($phone) == 12) {
                    $customerProcess = WooCommerce::addOrUpdateCustomer($client, $_customer, $campaignList);
                    $customer = $customerProcess['customer'];
                    $existingUser = $customerProcess['existingUser'];

                    if (isset($customer->id) && isset($customer->optincheck) && $customer->optincheck != 5) {
                        $this->handleOrdersAndProducts($customerEcommerceId, $customer, $client, $campaignList, $wooCommerceStore);
                    }
                }
            }
        }
    }

    private function handleOrdersAndProducts($customer_id = null, $customer, $client, $campaignList, $wooCommerceStore)
    {
        if ($customer_id == null) {
            return;
        }
        // Woocommerce per page limit is 100 so cannot exceed that
        $data = [
            'page' => 1,
            'limit' => 100,
            'customer_id' => $customer_id
        ];

        $orderList = WooCommerce::getOrdersList($data, $wooCommerceStore);
        if ($orderList['status'] == 'success') {
            if (isset($orderList['orders'][0]['id'])) {
                foreach ($orderList['orders'] as $_order) {
                    if(@$_order['status'] != 'failed' && @$_order['status'] != 'cancelled') {
                        $order = Order::where('store_order_id', $_order['id'])->where('client_id', $client->id)->first();
                        if (!isset($order->id)) {
                            $order = new Order();


                            $dateShipped = NULL;
                            if (isset($_order['date_shipped'])) {
                                $dateShipped = Carbon::parse($_order['date_shipped'])->format('Y-m-d H:i:s');
                            }
                            
                            $order->fill([
                                'client_id' => $client->id,
                                'customer_id' => $customer->id,
                                'store_id' => $wooCommerceStore->id,
                                'store_customer_id' => $customer_id,
                                'store_order_id' => $_order['id'],
                                'date_shipped' => $dateShipped,
                                'date_created' => Carbon::parse($_order['date_created'])->format('Y-m-d H:i:s'),
                                'status' => $_order['status'],
                                'subtotal_inc_tax' => $_order['total'],
                                'subtotal_tax' => $_order['total_tax'],
                                'base_shipping_cost' => $_order['shipping_total'],
                                'shipping_cost_tax' => $_order['shipping_tax'],
                                'total_tax' => $_order['total_tax'],
                                'items_total' => $_order['total'],
                                'items_shipped' => @$_order['items_shipped'],
                                'payment_method' => $_order['payment_method'],
                                'payment_provider_id' => $_order['payment_method_title'],
                                'ip_address' => $_order['customer_ip_address'],
                                'ip_address_v6' => $_order['customer_ip_address'],
                                'currency_id' => $_order['currency'],
                                'currency_code' => $_order['currency'],
                                'customer_message' => $_order['customer_note'],
                                'discount_amount' => $_order['discount_total'],
                                'cart_id' => $_order['cart_hash'],
                                'trigger_type' => 'Initial Sync'
                            ]);

                            $order->save();
                            if (isset($order->id)) {
                                if (isset($_order['line_items']) && isset($_order['line_items'][0]['id']) && $_order['line_items'][0]['id'] != '') {
                                    foreach ($_order['line_items'] as $_product) {
                                        $product = OrderProduct::where('order_id', $order->id)->where('store_product_id', $_product['id'])->where('client_id', $client->id)->first();
                                        if (!isset($product->id)) {
                                            $product = new OrderProduct();
                                            $product->fill([
                                                'client_id' => $client->id,
                                                'customer_id' => $customer->id,
                                                'store_id' => $wooCommerceStore->id,
                                                'order_id' => $order->id,
                                                'store_product_id' => $_product['id'],
                                                'variant_id' => $_product['variation_id'],
                                                'name' => $_product['name'],
                                                'name_customer' => $customer->first_name . ' ' . $customer->last_name,
                                                'sku' => $_product['sku'],
                                                'base_price' => $_product['price'],
                                                'price_ex_tax' => $_product['subtotal'],
                                                'price_inc_tax' => $_product['subtotal'],
                                                'price_tax' => $_product['subtotal_tax'],
                                                'base_total' => $_product['subtotal'],
                                                'total_ex_tax' => $_product['total'],
                                                'total_inc_tax' => $_product['total'],
                                                'total_tax' => $_product['total_tax'],
                                                'quantity' => $_product['quantity']
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
}