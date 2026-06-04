<?php
/**
 * Checkout WooCommerce minimal untuk produk event Mindful Mothers.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_filter('woocommerce_checkout_fields', 'xfusion_event_minimal_checkout_fields', 20);

/**
 * @param array<string, mixed> $fields
 * @return array<string, mixed>
 */
function xfusion_event_minimal_checkout_fields($fields)
{
    if (!xfusion_event_product_in_cart()) {
        return $fields;
    }

    $fields['billing'] = [];

    $fields['billing']['billing_email'] = [
        'label'    => 'Email',
        'required' => true,
        'type'     => 'email',
        'class'    => ['form-row-wide'],
        'priority' => 10,
    ];

    $fields['billing']['billing_first_name'] = [
        'label'    => 'First Name',
        'required' => true,
        'type'     => 'text',
        'class'    => ['form-row-first'],
        'priority' => 20,
    ];

    $fields['billing']['billing_last_name'] = [
        'label'    => 'Last Name',
        'required' => true,
        'type'     => 'text',
        'class'    => ['form-row-last'],
        'priority' => 30,
    ];

    $fields['billing']['billing_phone'] = [
        'label'    => 'Phone (optional)',
        'required' => false,
        'type'     => 'text',
        'class'    => ['form-row-wide'],
        'priority' => 40,
    ];

    return $fields;
}

function xfusion_event_product_in_cart(): bool
{
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    /** @var list<int> $target_product_ids */
    $target_product_ids = [22562];

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (in_array((int) $cart_item['product_id'], $target_product_ids, true)) {
            return true;
        }
    }

    return false;
}
