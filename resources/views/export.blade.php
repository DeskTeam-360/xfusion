<?php

add_filter( 'woocommerce_account_menu_items', 'reorder_myaccount_menu', 999 );
function reorder_myaccount_menu( $items ) {

    // Daftar urutan menu yang kamu mau (kunci = endpoint)
    $order = [
        'get-started',
        'get-support',
        'my-website',
        'my-domains',
        'cpanel-domains',
        'company-dashboard', // posisi ke-6
        'company-texting',   // posisi ke-7
        'company-volunteer', // posisi ke-8
        'edit-account',
        'payment-methods',
        'orders',
        'subscriptions',
        'edit-address',
        'logout',
    ];

    $new = [];

    // Tambahkan berdasarkan urutan di atas
    foreach ( $order as $key ) {
        if ( isset( $items[$key] ) ) {
            $new[$key] = $items[$key];
            unset( $items[$key] );
        }
    }

    // Tambahkan sisa menu lain (kalau plugin nambah)
    return array_merge( $new, $items );
}
