<?php
/**
 * Email kustom registrasi event + nonaktif email completed order WooCommerce untuk produk tertentu.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_filter('woocommerce_email_enabled_customer_completed_order', static function ($enabled, $order) {
    $target_product_id = 22562;

    foreach ($order->get_items() as $item) {
        if ((int) $item->get_product_id() === $target_product_id) {
            return false;
        }
    }

    return $enabled;
}, 10, 2);

add_action('woocommerce_payment_complete', 'xfusion_event_registration_custom_email', 10, 1);

function xfusion_event_registration_custom_email($order_id): void
{
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $target_product_id = 22562;
    $has_event = false;

    foreach ($order->get_items() as $item) {
        if ((int) $item->get_product_id() === $target_product_id) {
            $has_event = true;
            break;
        }
    }

    if (!$has_event) {
        return;
    }

    $to = $order->get_billing_email();
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $name = trim($first_name . ' ' . $last_name);
    $email = $order->get_billing_email();
    $order_date = wc_format_datetime($order->get_date_created());

    $subject = 'Event Registration Confirmation';

    $message = '
		<div style="background:#3f4a67; padding:40px 0; font-family: Arial, sans-serif;">

			<div style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:6px; overflow:hidden;">

				<div style="text-align:center; padding:20px;">
					<img src="https://sandbox.xperiencefusion.com/wp-content/uploads/2024/08/FUSION_Transparent-black-font-1024x474.png"
						 style="max-width:180px;">
				</div>

				<div style="padding: 0 30px 30px; color:#333; line-height:1.6;">

					<h2 style="margin-top:0; font-size: 28px; text-align: center;">Thank you for your registration!</h2>

					<p style="font-size: 15px;">Hi ' . esc_html($name) . ',</p>

					<p style="font-size: 15px; border-bottom: 1px solid #00000020; padding-bottom: 20px;">Thank you for registering. Here are your details:</p>

					<h3 style="margin-top:20px; font-size: 20px;">Registration Details</h3>
					<p style="padding: 20px; background: aliceblue; font-size: 15px;">
					<strong>Name:</strong> ' . esc_html($name) . '<br>
					<strong>Email:</strong> ' . esc_html($email) . '<br>
					<strong>Date:</strong> ' . esc_html($order_date) . '
					</p>

					<h3 style="margin-top:20px; font-size: 20px;">Event Details</h3>
					<p style="padding: 20px; background: aliceblue; font-size: 15px;">
					<strong>Date:</strong> May 16, 2026<br>
					<strong>Time:</strong> 11:30 AM – 1:30 PM<br>
					<strong>Location:</strong><br>
					Food + Farm Exploration Center<br>
					3400 Innovation Drive, Plover, WI 54467<br>
					Ag Hall (2nd floor)
					</p>

					<p style="margin:20px 0; font-size: 16px;">
						<a href="https://maps.google.com/?q=Food+Farm+Exploration+Center+Plover"
						   target="_blank"
						   style="display:inline-block; padding:10px 15px; background:#3f4a67; color:#fff; text-decoration:none; border-radius:4px;">
						   View Location on Google Maps
						</a>
					</p>

					<p style="font-size: 16px;"><strong>Lunch is included.</strong></p>

					<p style="font-size: 16px; border-bottom: 1px solid #00000020; padding-bottom: 20px;"><strong>Order ID:</strong> ' . esc_html((string) $order->get_id()) . '</p>

					<p style="font-size: 15px;">
					Please keep this email as your confirmation.<br>
					Please show this email at check-in.
					</p>

					<p style="font-size: 15px;">See you at the event.</p>

				</div>

				<div style="text-align:center; padding:20px; font-size:15px; color:#777; border-top:1px solid #eee;">
					<p style="margin:0;">XFusion<br>AZ, United States (US)</p>
				</div>

			</div>

		</div>
		';

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($to, $subject, $message, $headers);
}
