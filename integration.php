<?php

class TP_WC_2Performant_Tracking extends WC_Integration {

    function __construct() {
        $this->id = 'wc-2performant-tracking';
        $this->method_title = __( '2Performant Sale Tracking Pixel', 'wc-2performant-tracking' );
        $this->method_description = __( 'This is where you set up the parameters for 2Performant\'s sale tracking code:', 'wc-2performant-tracking' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Load user variables
        $this->campaign_unique = $this->get_option('campaign_unique');
        $this->campaign_secret = $this->get_option('campaign_secret');
        $this->tax_mode = $this->get_option('tax_mode');
        $this->tax_amount = $this->get_option('tax_amount');
        $this->debug_mode = $this->get_option('debug_mode');

        // Save settings if the we are in the right section
        if ( isset( $_POST[ 'section' ] ) && $this->id === $_POST[ 'section' ] ) {
            add_action( 'woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options') );
        }

        if($this->campaign_unique && $this->campaign_secret)
            add_action('woocommerce_thankyou', array($this, 'add_2performant_code'));
    }

    function init_form_fields() {
        $this->form_fields = array(
            'campaign_unique' => array(
                'title'       => __( 'Campaign unique code', 'wc-2performant-tracking' ),
                'description' => __( 'The campaign unique code you can find in your advertiser interface or in the tracking code as a parameter', 'wc-2performant-tracking' ),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'campaign_secret' => array(
                'title'       => __( 'Confirm code', 'wc-2performant-tracking' ),
                'description' => __( 'This is the "confirm" parameter in your tracking code', 'wc-2performant-tracking' ),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'tax_mode' => array(
                'title'       => __( 'Taxation mode', 'wc-2performant-tracking' ),
                'type'        => 'select',
                'default'     => 'auto',
                'options'     => array(
                    'auto'      => __( 'I have set up taxes in WooCommerce, use those', 'wc-2performant-tracking' ),
                    'manual'    => __( 'I haven\'t set up taxes in WooCommerce, but I need to subtract the amount below as VAT or some other tax', 'wc-2performant-tracking' ),
                ),
            ),
            'tax_amount' => array(
                'title'       => __( 'Tax amount', 'wc-2performant-tracking' ),
                'description' => __( 'Use this amount as the tax percentage if you need to deduct taxes from product prices (if you selected this option above)', 'wc-2performant-tracking' ),
                'desc_tip'    => false,
                'type'        => 'decimal',
                'placeholder' => 'e.g. 20, which means 20%',
            ),
            'debug_mode' => array(
                'title'       => __( 'Debug mode', 'wc-2performant-tracking' ),
                'description' => __( 'If this is checked, then the tracking code will not be loaded, but rather the parameters will be sent to the JS console', 'wc-2performant-tracking' ),
                'type'        => 'checkbox',
                'default'     => 'no',
            ),
        );
    }

    public function add_2performant_code($order_id) {
        $order = $this->parse_order_data($order_id);

        $output = '';
        if($this->debug_mode === "yes") {
            $output = "<script type='text/javascript'>var tp_values={amount:%s,unique:'%s',confirm:'%s',transaction_id:'%s',description:'%s'};console.log('2Performant tracking code values: ',tp_values);</script>";
        } else {
            $output = "<iframe height='1' width='1' scrolling='no' marginheight='0' marginwidth='0' frameborder='0' src='//event.2performant.com/events/salecheck?amount=%s&campaign_unique=%s&confirm=%s&transaction_id=%s&description=%s'></iframe>";
        }

        printf($output,
            urlencode($order['amount']),
            urlencode($this->campaign_unique),
            urlencode($this->campaign_secret),
            urlencode($order['transaction_id']),
            urlencode($order['description'])
        );
    }

    public function parse_order_data($order_id) {
        $result = array(
            'amount' => 0,
            'transaction_id' => 0,
            'description' => ''
        );

        $f = new WC_Order_Factory();
        $order = $f->get_order($order_id);

        if(!$order)
            return $result;

        $result['amount'] = $order->get_total() - $order->get_total_tax() - $order->get_total_shipping();
        if($this->tax_mode === "manual") {
            $result['amount'] *= 10000;
            $result['amount'] /= 100 + floatval($this->tax_amount);
            $result['amount'] = 0.01 * round($result['amount']);
        }

        $result['transaction_id'] = $order->get_order_number();

        $result['description'] = array();
        foreach($order->get_items() as $item) {
            $result['description'][] = $item['item_meta']['_qty'][0] . 'x' . $item['name'];
        }
        $result['description'] = implode('|', $result['description']);


        return $result;
    }

}
