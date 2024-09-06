<?php

/**
 * Plugin Name: xfusion plugin
 * Description: Plugin for XperienceFusion.
 * Version: 1.0
 * Author: Deskteam360
 */


function company_detect()
{
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        const baseStorage = 'https://admin.teamsetup-2.deskteam360.com/storage/';
        var data = {
            url: window.location.href.split('?')[0]
        }
        const addQueryParam = (key, value) => {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            window.history.pushState({}, '', url.toString());
        };
        document.addEventListener('DOMContentLoaded', function () {
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'post',
                dataType: "json",
                data: {
                    action: 'get_company_info',
                    url: window.location.href.split('?')[0],
                    param: window.location.href.split('?')[1],
                },
                success: function (response) {
                    if (response.data.status === "setId") {
                        addQueryParam('dataId', response.data.dataId)
                    }
                    if (response.data.status === "redirect") {
                        alert("You dont have access")

                        window.setTimeout(function () {window.location.replace(response.data.url)}, 1000);
                    }
                    if (response.data.logo_url !== null) {
                        const company_logo = document.getElementsByClassName("wp-image-5940");
                        company_logo[0].src = response.data.logo_url.replace("public/", baseStorage);
                        const qrcode = document.getElementsByClassName("wp-image-1124");
                        qrcode[0].src = response.data.qrcode_url.replace("public/", baseStorage);
                        qrcode[0].srcset = "";
                    }

                },
                error: function (xhr, status, error) {
                    console.error(xhr);
                    console.error(status);
                    console.error(error);
                }
            });
        });
    </script>
    <?php
}

add_action('wp_head', 'company_detect');

function get_company_info()
{
    global $wpdb;

    $url = $_POST['url'];
    $query = "select * from course_lists where url='$url'";

    $wpdb->insert("logs", array('log' => $query));

    $limitLinks = $wpdb->get_results($query);
    foreach ($limitLinks as $limit) {
        $userID = get_current_user_id();
        $wpdb->insert("logs", array('log' => "test1"));
        if ($userID != null) {
            $companyID = get_usermeta($userID, 'company');
            $keapTags = get_usermeta($userID, 'keap_tags');
            $query = "select * from companies where id=$companyID";
            $click_logs = $wpdb->get_results($query);
            $wpdb->insert("logs", array('log' => "test2"));
            $result = [];
            $user = get_userdata($userID);
            $user_roles = $user->roles;
            $wpdb->insert("logs", array('log' => "test3"));
            foreach ($click_logs as $log) {
                $result['logo_url'] = $log->logo_url;
                $result['qrcode_url'] = $log->qrcode_url;
            }

            if (in_array('editor', $user_roles, true) or in_array('administrator', $user_roles, true)) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                $wpdb->insert("logs", array('log' => "test4"));
                wp_die();
            }
            if ($limit->keap_tag == null) {
                $wpdb->insert("logs", array('log' => "test5"));
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                wp_die();
            }

            $wpdb->insert("logs", array('log' => "test6"));
            $query = "SELECT id FROM wp_gf_entry where source_url = '$url' and created_by = '$userID' and status='active'";
            $checkEntry = $wpdb->get_results($query);
            $wpdb->insert("logs", array('log' => "test7"));
            foreach ($checkEntry as $check) {
                $message = "You've done this course";
                $status = 'redirect';
                if ($_POST['param'] == 'dataId=' . $check->id) {
                    $wpdb->insert("logs", array('log' => "test99"));
                    wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                    wp_die();
                }
                wp_send_json_success(['url' => $url . '/?dataId=' . $check->id, 'dataId' => $check->id, 'status' => $status, 'message' => $message, 'logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                wp_die();
            }
            $wpdb->insert("logs", array('log' => "test99888"));
            if (in_array($limit->keap_tag, explode(';', $keapTags))) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                wp_die();
            }
            $wpdb->insert("logs", array('log' => "test99888"));
            $status = 'redirect';
            $message = "You don't have access";
            wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message]);
            wp_die();
        }
        $wpdb->insert("logs", array('log' => "test99888"));
        $url = $limit->redirect_url;
        $status = 'redirect';
        $message = "You need login";
        wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message]);
        wp_die();
    }

    wp_send_json_success(['logo_url' => null, 'qrcode_url' => null]);
    wp_die();
}

add_action('wp_ajax_get_company_info', 'get_company_info', 1, 3);
add_action('wp_ajax_nopriv_get_company_info', 'get_company_info', 1, 3);
