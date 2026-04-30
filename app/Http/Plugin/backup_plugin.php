<?php

global $wpdb;

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
        function moveCloseButton() {
            const closeButton = document.querySelector('#container-revitlize-center .btn-close');
            const container = document.querySelector('#container-revitlize-center');

            if (closeButton && container) {
                const prevDiv = container.previousElementSibling;

                if (prevDiv && prevDiv.nodeType === 1) {
                    prevDiv.appendChild(closeButton);

                    // Tambahkan CSS agar tetap rata kiri
                    if (!document.querySelector('#custom-close-css')) {
                    const style = document.createElement('style');
                    style.id = 'custom-close-css';
                    style.textContent = `
                        .btn-close {
                        display: block !important;
                        margin: 10px 0 !important;
                        }
                    `;
                    document.head.appendChild(style);
                    }
                }
            }
        }

  // Initial call


        function openWindowXfusion(link) {
            window.open(link, '_blank'); // Buka Google di tab baru
        }

        const { protocol, host } = window.location;
        const baseStorage = `${protocol}//admin.${host}/storage/`;


        var data = {
            url: window.location.href.split('?')[0]
        }
        const addQueryParam = (key, value) => {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            window.history.pushState({}, '', url.toString());
        };
        jQuery(document).ready(function ($) {
            jQuery(document).on('gform_confirmation_loaded', function () {
                jQuery('.btn-close').appendTo('#container-revitlize-center');
                jQuery('#btn-prev-revitalize').appendTo('#container-revitlize-center');
            });
        })


        const buttonSubmit99 = document.querySelector('.gform_button');
        const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
        let container = document.querySelector("#container-revitlize-center");

        if (buttonSubmit3 && !buttonSubmit99 && container) {
            container.appendChild(buttonSubmit3);
		
           moveCloseButton();

        }

        document.addEventListener('DOMContentLoaded', function () {


            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = window.location.search.substring(1),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;
                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');
                    if (sParameterName[0] === sParam) {
                        return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                    }
                }
                return false;
            };


            jQuery.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'post',
                dataType: "json",
                data: {
                    action: 'get_company_info',
                    url: window.location.href.split('?')[0],
                    param: window.location.href.split('?')[1],
                },
                success: function (response) {
                    const tools = response.data.tools ?? 0;
                    if (response.data.status === "setId") {
                        addQueryParam('dataId', response.data.dataId)
                    }
                    if (response.data.status === "return") {
                        addQueryParam('dataId', response.data.dataId)
                        f(response.data.form_id, response.data.url_next, tools)
                    }
                    if (response.data.status === "redirect") {
                        alert(response.data.message)
                        if (getUrlParameter('btn-close') == 'true') {
                            window.close()
                        } else {
                            window.location.replace(response.data.url)
                        }
                    }

                    if (response.data.logo_url !== null) {
                        const company_logo = document.getElementsByClassName("wp-image-11067");
                        if (company_logo.length > 0) {
                            company_logo[0].src = response.data.logo_url.replace("public/", baseStorage);
                        }

                        const qrcode = document.getElementsByClassName("wp-image-1124");
                        if (qrcode.length > 0) {
                            qrcode[0].src = response.data.qrcode_url.replace("public/", baseStorage);
                            qrcode[0].srcset = "";
                        }

                        const companyLogoLink = document.querySelector("#company-logo > div > a");
                        if (companyLogoLink) {
                            if (response.data.company_url) {
                                companyLogoLink.href = response.data.company_url;
                            } else {
                                companyLogoLink.href = '#';
                            }
                        }

                        //const cll = document.querySelector("#company-logo > div > a");

                    } else {
                        const companyLogoImg = document.querySelector("#company-logo > div > a > img");
                        if (companyLogoImg) {
                            companyLogoImg.src = "http://xperiencefusion.com/wp-content/uploads/2025/08/XFUSION_Transparent.png";
                        }
                    }

                    if (getUrlParameter('btn-close') === 'true') {
                        if (response.data.tools == 1) {
                            const buttonSubmit99 = document.querySelector('.gform_button');
                            const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
                            if (buttonSubmit3 && buttonSubmit99) {
                                let button = document.createElement("button");
                                button.className = "btn-close";
                                button.innerText = "Close tab";
                                button.style.display = "block";
                                button.style.marginTop = "10px";
                                button.onclick = function () {
                                    window.close()
                                };
                                buttonSubmit3.parentNode.replaceChild(button, buttonSubmit3);
                            } else {
                                let forms = document.querySelector("#container-revitlize-center");
                                let btnClose = document.querySelector(".btn-close");
                                if (forms) { // Cegah duplikasi tombol
                                    if (!btnClose) {
                                        const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
                                        if (buttonSubmit3) {
                                            buttonSubmit3.remove()
                                        }
                                        let button = document.createElement("button");
                                        button.className = "btn-close";
                                        button.innerText = "Close tab";
                                        button.style.display = "block";
                                        button.style.marginTop = "10px";
                                        button.onclick = function () {
                                            window.close()
                                        };
                                        forms.appendChild(button);
                                    }
                                }		
                            }
                            if(buttonSubmit99) {
                                moveCloseButton();
                            }
                            const buttonSubmit4 = document.querySelector('#btn-next-revitalize');
                            if (buttonSubmit4) {
                                buttonSubmit4.remove();
                            }
                        } else {
                            
                            const buttonSubmit99 = document.querySelector('.gform_button');
                            const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
                            if (buttonSubmit3) {
                                buttonSubmit3.remove();
                            }
                            const buttonSubmit4 = document.querySelector('#btn-next-revitalize');
                            if (buttonSubmit4) {
                                buttonSubmit4.remove();
                            }

                            let forms = document.querySelector("#container-revitlize-center");
                            let btnClose = document.querySelector(".btn-close");
                            if (forms) { 
                                if (!btnClose) {
                                    const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
                                    if (buttonSubmit3) {
                                        buttonSubmit3.remove()
                                    }
                                    let button = document.createElement("button");
                                    button.className = "btn-close";
                                    button.innerText = "Close tab";
                                    button.style.display = "block";
                                    button.style.marginTop = "10px";
                                    button.onclick = function () {
                                        window.close()
                                    };
                                    forms.appendChild(button);
                                }
                            }
                            if(buttonSubmit99) {
                                moveCloseButton();
                            }
							
                        }
                    }
                    if (getUrlParameter('dataId')) {
                        f(response.data.form_id, response.data.url_next, tools)
                        if (getUrlParameter('btn-close') === 'true') {
                            document.querySelectorAll('.gform_button')?.forEach(btn => btn.remove());
                        }
                    }

                },
            });


            function f(formId, next, tools) {
                jQuery.ajax({
                    url: '/wp-admin/admin-ajax.php',
                    type: 'GET',
                    dataType: 'json',
                    async: 'false',
                    data: {
                        action: 'get_form_data_gform',
                        form_id: formId,
                        order_id: getUrlParameter('dataId')
                    },
                    success: function (response) {
                        const res = response.data[0]
						console.log(res)
                        if (getUrlParameter('btn-close') == 'true' && tools && res) {
                            const buttonSubmit = document.querySelector('.gform_button');
                            if (buttonSubmit) {
                                buttonSubmit.remove()
                            }
                        }
                        if (getUrlParameter('btn-close') == 'true') {
                            const btn = document.querySelector(".btn-close");
                            const container = document.getElementById("container-revitlize-center");
                            if (container) {
                                if (btn) {
                                    container.appendChild(btn);
                                } else {
                                    let button = document.createElement("button");
                                    button.className = "btn-close";
                                    button.innerText = "Close tab";
                                    button.style.display = "block";
                                    button.style.marginTop = "10px";
                                    button.onclick = function () {
                                        window.close()
                                    };
                                    container.appendChild(button);
                                }
                            }
                        }


                        if (next && (getUrlParameter('btn-close') != 'true')) {
                            const buttonSubmit = document.querySelector('.gform_button');

                            if (buttonSubmit) {
                                // Buat elemen <a> baru
                                const newLink = document.createElement('a');
                                newLink.href = next // Ganti dengan link tujuan
                                newLink.className = buttonSubmit.className + ' btn-close';

                                newLink.style.cssText = buttonSubmit.style.cssText + '; text-align: center; position:static'; // Salin inline style

                                if (buttonSubmit.value === "" || buttonSubmit.value === undefined || buttonSubmit.value === "Next Lesson" || buttonSubmit.value === "Done" || buttonSubmit.value === "Submit" || buttonSubmit.value === "Complete Session") {
                                    newLink.textContent = "Return to menu"; // Salin inline style
                                    const buttonSubmit = document.querySelector('#gform_submit_button_13');
                                    if (buttonSubmit) {
                                        buttonSubmit.remove();
                                    }


                                } else if (buttonSubmit.value === 'Mark as Complete') {
                                    newLink.textContent = 'Return to Menu'
                                } else {

                                    newLink.textContent = buttonSubmit.value; // Gunakan teks yang sama
                                }

                                if (buttonSubmit.id) {
                                    newLink.id = buttonSubmit.id;
                                }
                                var container = document.getElementById("container-revitlize-center");


                                // Ganti tombol lama dengan elemen <a>
                                buttonSubmit.parentNode.replaceChild(newLink, buttonSubmit);
                                if (newLink && container) {
                                    container.appendChild(newLink);
                                    var btnPrev = document.getElementById('btn-prev-revitalize')
                                    if (btnPrev) {
                                        btnPrev.remove();
                                    }
                                }

                            }
                            const buttonSubmit2 = document.querySelector('.mark-as-complete');
                            if (buttonSubmit2) {
                                const newLink = document.createElement('a');
                                newLink.href = next // Ganti dengan link tujuan
                                newLink.className = buttonSubmit2.className; // Salin class
                                newLink.style.cssText = buttonSubmit2.style.cssText; // Salin inline style
                                newLink.style.color = 'white';
                                newLink.style.textTransform = 'none';
                                if (buttonSubmit2.textContent === "") {
                                    newLink.textContent = "Next Lesson"; // Salin inline style
                                } else if (buttonSubmit2.textContent === 'Mark as Complete') {
                                    newLink.textContent = 'Return to Menu'
                                } else {
                                    newLink.textContent = buttonSubmit2.textContent; // Gunakan teks yang sama
                                }

                                if (buttonSubmit2.id) {
                                    newLink.id = buttonSubmit2.id;
                                }
                                // Ganti tombol lama dengan elemen <a>
                                buttonSubmit2.parentNode.replaceChild(newLink, buttonSubmit2);
                            }

                        }

                        if (getUrlParameter('btn-close') == 'true') {
                            const buttonSubmit2 = document.querySelector('.mark-as-complete');
                            if (buttonSubmit2) {
                                let button = document.createElement("button");
                                button.className = buttonSubmit2.className;
                                button.innerText = "Close tab";
                                button.style.display = "block";

                                button.style.marginTop = "10px";
                                button.onclick = function () {
                                    window.close()
                                };
                                buttonSubmit2.parentNode.replaceChild(button, buttonSubmit2);
                            }
                        }


                        for (var key in res) {
                            if (!res.hasOwnProperty(key)) continue;
                            if (!isNaN(key)) {
                                if (document.getElementsByName('input_' + key)[0] != null) {
                                    if (document.getElementsByName('input_' + key)[0]['type'] === "radio") {
                                        const radioButtons = document.querySelectorAll(`input[name="${'input_' + key}"]`);

                                        radioButtons.forEach(function (radioButton) {
                                            radioButton.disabled = true; // Menonaktifkan radio button
                                        });
                                        var radio = document.querySelector(`input[name="${'input_' + key}"][value="${response.data[0][key]}"]`);
                                        // Ensure the radio button exists before setting the checked property
                                        if (radio) {
                                            radio.checked = true;
                                            radio.disabled = false;

                                        }


                                    } else if (key % 1 !== 0) {
                                        if (response.data[0][key] !== '') {
                                            document.getElementsByName('input_' + key)[0].checked = true
                                        }
                                        document.getElementsByName('input_' + key)[0].disabled = true

                                        const event = new Event('change', {bubbles: true});
                                        document.getElementsByName('input_' + key)[0].dispatchEvent(event);


                                    } else if (document.getElementsByName('input_' + key)[0]['type'] === "file") {
                                        const file = document.getElementsByName('input_' + key)[0]

                                        const downloadBtn = document.createElement('a');
                                        downloadBtn.textContent = "Download file";
                                        downloadBtn.className = "previous-lesson-button";
                                        downloadBtn.style.marginTop = '10px'
                                        downloadBtn.style.padding = '10px'
                                        downloadBtn.href = response.data[0][key]
                                        downloadBtn.target = '_blank'

                                        file.parentNode.insertBefore(downloadBtn, file.nextSibling);
                                    } else {
                                        // document.getElementsByName('input_' + key)[0].value = response.data[0][key]
                                        const inputElement = document.getElementsByName('input_' + key)[0];

                                        inputElement.value = response.data[0][key];
                                        const event = new Event('change', {bubbles: true});
                                        inputElement.dispatchEvent(event);
										
										
inputElement.dispatchEvent(new Event('input', { bubbles: true }));
inputElement.dispatchEvent(new Event('change', { bubbles: true }));
                                    }
                                    document.getElementsByName('input_' + key)[0].disable = true
                                    document.getElementsByName('input_' + key)[0].readOnly = true

                                }
                            }
                        }
                    }
                });
            }
        })

    </script>
    <?php
}

add_action("wp_head", "company_detect");

function get_company_info()
{
    global $wpdb;

    $url = $_POST["url"];
    $query = "select * from wp_course_lists where url='$url'";

    $limitLinks = $wpdb->get_results($query);

    $userID = get_current_user_id();
    $query_user = "select * from wp_users where id='$userID'";
    $qu = $wpdb->get_results($query_user);

    $t5 = json_encode($qu);

    // Decode JSON to a PHP array
    $data = json_decode($t5, true);

    // Access the "user_login" value
    $user_login = str_replace(" ", "+", strtolower($data[0]["user_login"]));

    $arrayLinks = [
        "/user/$user_login/",
        "/lms-home-screen/",
        "/topics/dependability/",
        "/account/",
        "/resources/resource-menu/",
    ];

    if (!$limitLinks) {
        if ($userID != null) {
            $companyID = get_usermeta($userID, "company");

            $query = "SELECT * FROM wp_companies WHERE id = %d";
            $click_logs = $wpdb->get_results(
                $wpdb->prepare($query, $companyID)
            );

            $result = [];
            foreach ($click_logs as $log) {
                $result["logo_url"] = $log->logo_url;
                $result["qrcode_url"] = $log->qrcode_url;
                $result["company_url"] = $log->company_url;
            }

            // Ambil hanya path dari $url
            $urlPath = parse_url($url, PHP_URL_PATH);

            if (in_array($urlPath, $arrayLinks, true)) {
                wp_send_json_success([
                    "logo_url" => $result["logo_url"] ?? "",
                    "qrcode_url" => $result["qrcode_url"] ?? "",
                    "company_url" => $result["company_url"] ?? "",
                ]);
                wp_die();
            } else {
                wp_die();
            }
        }
    }

    foreach ($limitLinks as $limit) {
        $userID = get_current_user_id();
		
		$userRole = get_usermeta($userID, "user_role");
        if ($userID != null) {
            $companyID = get_usermeta($userID, "company");
            $keapTags = get_usermeta($userID, "access_tags");

            $query = "select * from wp_companies where id=$companyID";
            $click_logs = $wpdb->get_results($query);

            $result = [];
            $user = get_userdata($userID);
            $user_roles = $user->roles;

            $user_access = get_user_meta($userID, "user_access", true);
            if (
                in_array("administrator", $user_roles, true) or
                in_array("editor", $user_roles, true) or $userRole =="Super Admin"
            ) {
            } else {
                $user_access = strtolower(str_replace(" ", "-", $user_access));
                $course_title_slug = strtolower(
                    str_replace(" ", "-", $limit->course_title)
                );
                if ($course_title_slug == "revitalize-resources") {
                    $course_title_slug = "revitalize";
                }
                if ($course_title_slug == "transform-resources") {
                    $course_title_slug = "transform-resource";
                }
                if ($course_title_slug == "sustain-resources") {
                    $course_title_slug = "sustain-resource";
                }

                if (!stripos($user_access, $course_title_slug)) {
                    $status = "redirect";
                    $message = "You don't have access to this page";

                    wp_send_json_success([
                        "url" => $limit->url_redirect,
                        "status" => $status,
                        "message" => $message,
                        "access" => $user_access,
                        "access2" => $limit->course_title,
                    ]);
                    wp_die();
                }
            }

            foreach ($click_logs as $log) {
                $result["logo_url"] = $log->logo_url ? $log->logo_url : null;
                $result["qrcode_url"] = $log->qrcode_url
                    ? $log->qrcode_url
                    : null;
            }

            $query = "SELECT id,form_id FROM wp_gf_entry where form_id = '$limit->wp_gf_form_id' and created_by = '$userID' and status='active'";
            $checkEntry = $wpdb->get_results($query);

            foreach ($checkEntry as $check) {
                $message = "You've done the topic";
                $status = "return";
                if (
                    isset($_POST["param"]) &&
                    strpos($_POST["param"], $check->id) !== false
                ) {
                    wp_send_json_success([
                        "logo_url" => $result["logo_url"],
                        "qrcode_url" => $result["qrcode_url"],
                        "company_url" => $result["company_url"],
                        "form_id" => $check->form_id,
                        "url_next" => $limit->url_next,
                        "tools" => $limit->repeat_entry,
                    ]);
                    wp_die();
                }

                if ($limit->repeat_entry == 1) {
                    wp_send_json_success([
                        "logo_url" => $result["logo_url"],
                        "qrcode_url" => $result["qrcode_url"],
                        "company_url" => $result["company_url"],
                        "form_id" => $check->form_id,
                        "url_next" => $limit->url_next,
                        "tools" => $limit->repeat_entry,
                    ]);
                    wp_die();
                }

                wp_send_json_success([
                    "url" =>
                        $url . "?dataId=" . $check->id . "&" . $_POST["param"],
                    "dataId" => $check->id,
                    "status" => $status,
                    "message" => $message,
                    "logo_url" => $result["logo_url"],
                    "qrcode_url" => $result["qrcode_url"],
                    "company_url" => $result["company_url"],
                    "tools" => $limit->repeat_entry,
                    "url_next" => $limit->url_next,
                ]);
                wp_die();
            }

            if ($limit->keap_tag == null) {
                wp_send_json_success([
                    "logo_url" => $result["logo_url"],
                    "qrcode_url" => $result["qrcode_url"],
                    "company_url" => $result["company_url"],
                    "tools" => $limit->repeat_entry,
                ]);
                wp_die();
            }

            if (
                $limit->keap_tag == null ||
                $limit->keap_tag == false ||
                $limit->keap_tag == ""
            ) {
                wp_send_json_success([
                    "logo_url" => $result["logo_url"],
                    "qrcode_url" => $result["qrcode_url"],
                    "company_url" => $result["company_url"],
                    "tools" => $limit->repeat_entry,
                ]);
                wp_die();
            }

            if (in_array($limit->keap_tag, explode(";", $keapTags))) {
                wp_send_json_success([
                    "logo_url" => $result["logo_url"],
                    "qrcode_url" => $result["qrcode_url"],
                    "company_url" => $result["company_url"],
                    "tools" => $limit->repeat_entry,
                ]);
                wp_die();
            }

             if (in_array("administrator", $user_roles, true)  or $userRole =="Super Admin") {
                wp_send_json_success([
                    "logo_url" => $result["logo_url"],
                    "qrcode_url" => $result["qrcode_url"],
                    "company_url" => $result["company_url"],
                    "tools" => $limit->repeat_entry,
                ]);
                wp_die();
            }

            if (in_array($limit->keap_tag_parent, explode(";", $keapTags))) {
                $status = "redirect";
                $message =
                    "You need waiting " .
                    $limit->delay +
                    5 .
                    "minutes from last submit";
                wp_send_json_success([
                    "url" => $limit->url_redirect,
                    "status" => $status,
                    "message" => $message,
                    "tools" => $limit->repeat_entry,
                ]);
                wp_die();
            }

            $status = "redirect";
            $message = "You don't have access to this page";
            wp_send_json_success([
                "url" => $limit->url_redirect,
                "status" => $status,
                "message" => $message,
                "tools" => $limit->repeat_entry,
            ]);
            wp_die();
        }
        $url = $limit->redirect_url;
        $status = "redirect";
        $message = "You need login ";

        $protocol =
            !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
                ? "https://"
                : "http://";
        $domain = $_SERVER["HTTP_HOST"];

        $fullDomain = $protocol . $domain;

        wp_send_json_success([
            "url" => "$fullDomain/lms-home-screen/",
            "status" => $status,
            "message" => $message,
            "tools" => $limit->repeat_entry,
        ]);
        wp_die();
    }

    wp_send_json_success([
        "logo_url" => null,
        "qrcode_url" => null,
        "tools" => $limit->repeat_entry,
    ]);
    wp_die();
}

add_action("wp_ajax_get_company_info", "get_company_info");
add_action("wp_ajax_nopriv_get_company_info", "get_company_info", 1, 3);

add_filter("wp_hash_password", "custom_wp_hash_password", 10, 2);

function custom_wp_hash_password($password, $user_id = null)
{
    require_once ABSPATH . WPINC . "/class-phpass.php";
    $wp_hasher = new PasswordHash(8, true);
    return $wp_hasher->HashPassword(trim($password));
}

if (!function_exists("wp_hash_password")) {
    function wp_hash_password($password)
    {
        require_once ABSPATH . WPINC . "/class-phpass.php";
        $hasher = new PasswordHash(8, true); // 8 adalah strength, true untuk portable
        return $hasher->HashPassword(trim($password));
    }
}

if (!function_exists("wp_check_password")) {
    function wp_check_password($password, $hash, $user_id = "")
    {
        require_once ABSPATH . WPINC . "/class-phpass.php";
        $hasher = new PasswordHash(8, true);
        $check = $hasher->CheckPassword($password, $hash);

        return apply_filters(
            "check_password",
            $check,
            $password,
            $hash,
            $user_id
        );
    }
}
// Disable WordPress search hanya di homepage frontend
function disable_wp_search_homepage($query)
{
    // Hanya jalan di frontend, bukan wp-admin
    if (
        !is_admin() &&
        $query->is_main_query() &&
        is_search() &&
        is_front_page()
    ) {
        $query->is_search = false;
        $query->set("s", false);

        // Redirect ke homepage
        wp_redirect(home_url());
        exit();
    }
}
add_action("pre_get_posts", "disable_wp_search_homepage");

// Optional: sembunyikan form search hanya di homepage
function disable_search_form_homepage($form)
{
    if (is_front_page()) {
        return "";
    }
    return $form;
}
add_filter("get_search_form", "disable_search_form_homepage");


add_action('um_after_profile_content', function(){
    echo '<div style="padding:20px;background:#eee">Custom Content</div>';
});

function test_um_hook(){
    echo '<div style="background:red;color:#fff;padding:10px">UM HOOK WORK</div>';
}
add_action('um_profile_before_header','test_um_hook');

function test_um_bottom(){
    echo '<div style="background:blue;color:#fff;padding:10px">BOTTOM PROFILE</div>';
}
add_action('um_after_profile','test_um_bottom');

function custom_um_section(){
    $output = '';

    //get user
    $current_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $path = parse_url($current_url, PHP_URL_PATH);
    $user = basename(rtrim($path, '/'));
    if (strpos($user, "+") !== false) {
        $user = str_replace("+", " ", $user);
    }

    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}users WHERE user_login = '$user'";
    $user_result = $wpdb->get_results($query);
    $user_id = ($user_result && isset($user_result[0])) ? $user_result[0]->ID : 0;
    if ($user_id == 0) {
        $user_id = get_current_user_id();
    }

    if (UM()->options()->get('profile_empty_text')) {
        $emo = UM()->options()->get('profile_empty_text_emo');
        if ($emo) {
            $emo = '<i class="um-faicon-frown-o"></i>';
        } else {
            $emo = false;
        }

        if (um_is_myprofile()) {
            if (isset($_GET['profiletab']) && 'main' !== $_GET['profiletab']) {
                $tab = sanitize_key($_GET['profiletab']);
                $edit_action = 'edit_' . $tab;
                $profile_url = um_user_profile_url(um_profile_id());
                $edit_url = add_query_arg(['profiletab' => $tab, 'um_action' => $edit_action], $profile_url);
            } else {
                $edit_url = um_edit_profile_url();
            }

            global $wpdb;

            $query = "SELECT * FROM wp_course_groups where tools=0 order by order_group ";
            $cg_list = $wpdb->get_results($query);

            $output .= '<style>
                .profile-notes {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: space-between;
                }
                .note-column {
                    flex: 30%;
                    margin: 0 10px 10px;
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    transition: background-color 0.3s, transform 0.3s;
                    text-align: center;
                }
                .note-column:hover {
                    background-color: #f0f0f0;
                    transform: scale(1.05);
                }

                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    overflow: auto;
                    background-color: rgb(0,0,0);
                    background-color: rgba(0,0,0,0.4);
                }

                .modal-content {
                    background-color: #fefefe;
                    margin: 15% auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                    border-radius: 5px;
                }

                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                }

                .close:hover,
                .close:focus {
                    color: black;
                    text-decoration: none;
                    cursor: pointer;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th, td {
                    border: 1px solid #ccc;
                    padding: 10px;
                    text-align: left;
                }

                th {
                    background-color: #f2f2f2;
                }

                .custom-btn {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #007bff;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 5px;
                    transition: background-color 0.3s, transform 0.3s;
                }

                .custom-btn:hover {
                    background-color: #0056b3;
                    transform: scale(1.05);
                }

                .accordion-tools {
                  background-color: #eee;
                  color: #444;
                  cursor: pointer;
                  padding: 5px;
                  width: 100%;
                  border: none;
                  text-align: left;
                  outline: none;
                  font-size: 15px;
                  transition: 0.4s;
                  margin-bottom: 10px;
                }

                .active-tools, .accordion-tools:hover {
                  background-color: #ccc;
                }

                .panel-tools {
                  display: none;
                  background-color: white;
                }

                .accordion-item {
                    margin-bottom: 10px;
                    border: 1px solid #ccc;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .accordion-header:hover {
                    background-color: #e0e0e0;
                }
                .note-column {
                    display: block;
                    padding: 8px;
                    margin: 5px 0;
                    background-color: #fafafa;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    text-decoration: none;
                }
                .accordion-level1, .accordion-level2 {
                    background-color: #f1f1f1;
                    border: 1px solid #ccc;
                    padding: 10px;
                    border-radius: 5px;
                    margin-bottom: 5px;
                }

                .accordion-level1:hover, .accordion-level2:hover {
                    background-color: #ddd;
                    cursor: pointer;
                }

                .panel-level1, .panel-level2 {
                  background-color: white;
                  transition: all 0.3s ease;
                }

                .accordion-box {
                    background-color: #f9f9f9;
                    padding: 15px;
                    margin-bottom: 10px;
                    cursor: pointer;
                    border-radius: 6px;
                    transition: all 0.3s ease;
                }

                .accordion-box:hover {
                    background-color: #f0f0f0;
                }

                .accordion-content {
                    padding: 10px;
                    margin-bottom: 15px;
                    background: #fff;
                }
                .accordion-header{
                    font-weight: 900;
                }
            </style>
            <script>
function toggleAccordion(id) {
    var content = document.getElementById(id);
    if (content.style.display === "none" || content.style.display === "") {
        content.style.display = "block";
    } else {
        content.style.display = "none";
    }
}
</script>
            ';

            $output .= "<h2 style='text-align: center'>Course List</h2>";

            foreach ($cg_list as $cg) {
                $accordion_id = 'accordion_' . uniqid();

                $output .= "<div class='accordion-item'>";
                $output .= "<div class=' accordion-box accordion-header' onclick=\"toggleAccordion('$accordion_id')\" style='font-size: 26px; cursor: pointer; margin: 0;'>$cg->title - $cg->sub_title</div>";

                $output .= "<div id='$accordion_id' class='accordion-content' style='display: none; padding: 10px;'>";

                $query = "SELECT * FROM wp_course_group_details where course_group_id = $cg->id order by orders";
                $q_list = $wpdb->get_results($query);

                if (count($q_list) == 0) {
                    $output .= "<div style='font-size: 24px'>Coming soon </div>";
                }

                $output .= "<div class='profile-notes' style='gap: 10px'>";
                foreach ($q_list as $q) {
                    $temp_id = (int)$q->course_list_id;
                    $query = "SELECT * FROM wp_course_lists WHERE id = $temp_id";
                    $c_list = $wpdb->get_results($query);

                    $form_id = $c_list[0]->wp_gf_form_id;
                    $link_child = $c_list[0]->url;

                    try {
                        $subquery = $wpdb->prepare("
                            SELECT created_by, MAX(date_created) as max_date
                            FROM {$wpdb->prefix}gf_entry
                            WHERE form_id = %d AND created_by IS NOT NULL
                            GROUP BY created_by
                        ", $form_id);

                        $query = $wpdb->prepare("
                            SELECT id, created_by, date_created
                            FROM {$wpdb->prefix}gf_entry
                            WHERE form_id = %d AND created_by = %d AND created_by IS NOT NULL AND status ='active'
                            AND (created_by, date_created) IN ($subquery)
                        ", $form_id, $user_id);

                        $entry_id = $wpdb->get_var($query);
                    } catch (Exception $e) {
                        $entry_id = false;
                    }

                    // Sembunyikan jika course list legacy == 1 dan entry_id null
                    if (isset($c_list[0]->legacy) && $c_list[0]->legacy == 1 && !$entry_id) {
                        continue;
                    }

                    $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gf_form_meta WHERE form_id = %d", $form_id));

                    $data_entry = $wpdb->get_results($wpdb->prepare("
                        SELECT * FROM {$wpdb->prefix}gf_entry_meta
                        WHERE form_id = %d AND entry_id = %d
                    ", $form_id, $entry_id));

                    $array_entry = [];
                    $fields = json_decode($data->display_meta);

                    if (isset($fields->fields)) {
                        foreach ($fields->fields as $field) {
                            $array_entry[$field->id] = null;
                        }
                    }

                    foreach ($data_entry as $entry) {
                        $array_entry[$entry->meta_key] = $entry->meta_value;
                    }

                    $title_display = $c_list[0]->page_title;
                    if ($entry_id && isset($c_list[0]->legacy) && $c_list[0]->legacy == 1) {
                        $title_display .= ' (legacy)';
                    }

                    if ($entry_id) {
                        $output .= '<a
                            onclick="openWindowXfusion(\'' . $link_child . '?dataId=' . $entry_id . '&btn-close=true\')"
                            class="note-column" style="color: #666; font-weight: bold">
                            <span>' . esc_html($title_display) . '</span>
                        </a>';
                    } else {
                        $output .= '<a href="' . $link_child . '" class="note-column" style="color: red; pointer-events: none;">
                            <span>' . esc_html($title_display) . '</span>
                        </a>';
                    }
                }

                $output .= "</div></div></div>";
            }

            $query = "SELECT * FROM wp_course_groups WHERE tools=1 ORDER BY order_group";
            $cg_list = $wpdb->get_results($query);

            $output .= "<h2 style='text-align: center'>Tool List</h2>";

            foreach ($cg_list as $cg) {
                $output .= "<div class='accordion-item'>";
                $output .= "<div class='accordion-tools accordion-box accordion-header' style='font-size: 26px; margin: 0'>$cg->title</div>";
                $output .= "<div class='panel-tools accordion-content' style='display: none; flex-direction: column'>";

                $query = "SELECT * FROM wp_course_group_details WHERE course_group_id = $cg->id ORDER BY orders";
                $q_list = $wpdb->get_results($query);

                if (count($q_list) == 0) {
                    $output .= "<div style='font-size: 20px; padding: 10px;'>Coming soon</div>";
                }

                foreach ($q_list as $q) {
                    $temp_id = (int)$q->course_list_id;
                    $query = "SELECT * FROM wp_course_lists WHERE id = $temp_id";
                    $c_list = $wpdb->get_results($query);

                    $form_id = $c_list[0]->wp_gf_form_id;
                    $link_child = $c_list[0]->url;

                    try {
                        $query = $wpdb->prepare("
                            SELECT id, created_by, date_created
                            FROM {$wpdb->prefix}gf_entry
                            WHERE form_id = %d AND created_by = %d AND created_by IS NOT NULL AND status ='active'
                        ", $form_id, $user_id);
                        $entry_ids = $wpdb->get_results($query);
                    } catch (Exception $e) {
                        $entry_ids = [];
                    }

                    $c = count($entry_ids);

                    $output .= "<div class='accordion-tools accordion-box' style='font-size: 22px; border: 1px solid #ccc;'> {$c_list[0]->page_title} ($c)</div>";
                    $output .= "<div class='panel-tools' style='display: none;margin-bottom: 10px; flex-direction: row; flex-wrap: wrap'>";

                    foreach ($entry_ids as $entry_id) {
                        $timestamp = $entry_id->date_created;
                        $formatted_date = date("F j, Y H:i:s", strtotime($timestamp));

                        $output .= '<a onclick="openWindowXfusion(\'' . $link_child . '?dataId=' . $entry_id->id . '&btn-close=true\')"
                            target="_blank" class="note-column"
                            style="color: #666; font-weight: bold; margin: 10px 10px 0 0; display: inline-block;"
                            data-timestamp="' . strtotime($timestamp) . '">
                            <span class="localized-time">' . $formatted_date . '</span>
                        </a>';
                    }

                    $output .= "</div>";
                }

                $output .= "</div>";
                $output .= "</div>";
            }

            $output .= '
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".note-column").forEach(function (element) {
        let timestamp = element.getAttribute("data-timestamp");
        if (timestamp) {
            let date = new Date(timestamp * 1000);
            let options = { year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit" };
            let formattedDate = date.toLocaleString(undefined, options);
            element.querySelector(".localized-time").innerText = formattedDate;
        }
    });

    var acc = document.getElementsByClassName("accordion-tools");
    for (var i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function () {
            this.classList.toggle("active-tools");
            var panel = this.nextElementSibling;
            if (panel.style.display === "flex" || panel.style.display === "block") {
                panel.style.display = "none";
            } else {
                panel.style.display = "flex";
            }
        });
    }
});
</script>';
        } else {
            $output .= '<p class="um-profile-note">' . $emo . '<span>' . __('This user has not added any information to their profile yet.', 'ultimate-member') . '</span></p>';
        }
    }

    echo $output;
}

add_action('um_profile_content_main', 'custom_um_section');

// =====================================
// LearnDash topic global search (index + shortcode)
// =====================================

/**
 * Tarik teks dari data JSON Elementor (rekursif).
 */
function ld_extract_elementor_text($data)
{
    $text = '';
    if (!is_array($data)) {
        return $text;
    }
    foreach ($data as $key => $value) {
        if (is_string($value) && in_array(
            $key,
            ['editor', 'title', 'text', 'caption', 'heading', 'description', 'html', 'alert_title', 'alert_description',
                'tab_title', 'tab_content', 'before_text', 'after_text', 'prefix', 'suffix', 'sub_title', 'link_text',],
            true
        )) {
            $text .= ' ' . wp_strip_all_tags($value);
        } elseif ($key === 'elements' && is_array($value)) {
            foreach ($value as $element) {
                $text .= ' ' . ld_extract_elementor_text($element);
            }
        } elseif ($key === 'settings' && is_array($value)) {
            $text .= ' ' . ld_extract_elementor_text($value);
        } elseif (is_array($value)) {
            $text .= ' ' . ld_extract_elementor_text($value);
        }
    }

    return trim(preg_replace('/\s+/', ' ', $text));
}

add_action('save_post', 'ld_build_topic_search_index', 20, 3);
add_action('save_post_sfwd-topic', 'ld_build_topic_search_index', 20, 3);
add_action('wp_after_insert_post', 'ld_build_topic_search_index_fallback', 20, 4);

/**
 * @param WP_Post                $post
 * @param bool                   $update
 * @param WP_Post|null           $post_before
 */
function ld_build_topic_search_index_fallback($post_id, $post, $update, $post_before)
{
    if (!$post instanceof WP_Post || $post->post_type !== 'sfwd-topic') {
        return;
    }
    ld_build_topic_search_index($post_id, $post, $update);
}

/**
 * Bangun meta _search_index untuk pencarian global topic LMS.
 *
 * @param int      $post_id
 * @param WP_Post|null $post
 */
function ld_build_topic_search_index($post_id, $post = null, $update = null)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    $post = $post instanceof WP_Post ? $post : get_post($post_id);
    if (!$post || $post->post_type !== 'sfwd-topic') {
        return;
    }

    $title = get_the_title($post_id);
    $content = get_post_field('post_content', $post_id);
    $final_text = $title;

    // Elementor template shortcode
    preg_match_all('/\[elementor-template[^\]]*id=[\'"]?(\d+)/i', $content, $tpl_matches);
    if (!empty($tpl_matches[1])) {
        foreach ($tpl_matches[1] as $tpl_id) {
            $tpl_content = get_post_field('post_content', (int) $tpl_id);
            if (!$tpl_content) {
                continue;
            }
            if (strpos($tpl_content, '"widgetType"') !== false) {
                $decoded = json_decode($tpl_content, true);
                if (is_array($decoded)) {
                    $final_text .= ' ' . ld_extract_elementor_text($decoded);
                }
            } else {
                $final_text .= ' ' . wp_strip_all_tags($tpl_content);
            }
        }
    }

    // Gravity Forms shortcode
    preg_match_all('/\[gravityform[^\]]*id=[\'"]?(\d+)/i', $content, $gf_matches);
    if (!empty($gf_matches[1]) && class_exists('GFAPI')) {
        foreach ($gf_matches[1] as $form_id) {
            $form = GFAPI::get_form((int) $form_id);
            if ($form && !empty($form['fields'])) {
                foreach ($form['fields'] as $field) {
                    if (!empty($field->label)) {
                        $final_text .= ' ' . $field->label;
                    }
                    if (!empty($field->placeholder)) {
                        $final_text .= ' ' . $field->placeholder;
                    }
                }
            }
        }
    }

    $final_text = preg_replace('/\[elementor-template[^\]]*\]/i', '', $final_text);
    $final_text = preg_replace('/\[gravityform[^\]]*\]/i', '', $final_text);
    $final_text = strtolower($final_text);
    $final_text = html_entity_decode($final_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $final_text = preg_replace('/[^a-z0-9\s]/u', ' ', $final_text);
    $final_text = preg_replace('/\s+/', ' ', $final_text);
    $final_text = trim($final_text);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LD topic search index saved: ' . $post_id);
    }

    update_post_meta($post_id, '_search_index', $final_text);
}

/**
 * Generate hingga $max_snippets potongan teks dengan highlight keyword.
 */
function ld_generate_snippets($text, $keywords, $radius = 10, $max_snippets = 3)
{
    $text = strtolower(strip_tags($text));
    $words = explode(' ', $text);
    $total = count($words);

    $snippets = [];
    $used_indexes = [];

    foreach ($words as $index => $word) {
        foreach ($keywords as $keyword) {
            if (strpos($word, $keyword) !== false && !in_array($index, $used_indexes, true)) {
                $start = max(0, $index - $radius);
                $end = min($total - 1, $index + $radius);

                $slice = array_slice($words, $start, $end - $start + 1);
                $snippet = implode(' ', $slice);

                foreach ($keywords as $kw) {
                    $snippet = preg_replace(
                        '/(' . preg_quote($kw, '/') . ')/i',
                        '<strong>$1</strong>',
                        $snippet
                    );
                }

                $snippets[] = '...' . $snippet . '...';

                for ($i = $start; $i <= $end; $i++) {
                    $used_indexes[] = $i;
                }

                if (count($snippets) >= $max_snippets) {
                    return $snippets;
                }
            }
        }
    }

    return $snippets;
}

/**
 * Shortcode: [ld_topic_search] — gunakan ?q=keyword di URL atau sesuaikan form GET.
 */
function ld_topic_search_results()
{
    $keyword = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

    if ($keyword === '') {
        return '<p>' . esc_html__('Enter a keyword to search…', 'xfusion') . '</p>';
    }

    $keywords = array_filter(explode(' ', strtolower($keyword)));

    $meta_query = ['relation' => 'AND'];
    foreach ($keywords as $word) {
        $meta_query[] = [
            'key' => '_search_index',
            'value' => $word,
            'compare' => 'LIKE',
        ];
    }

    $args = [
        'post_type' => 'sfwd-topic',
        'posts_per_page' => 20,
        'post_status' => 'publish',
        'meta_query' => $meta_query,
    ];

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        echo '<div class="ld-search-results">';
        while ($query->have_posts()) {
            $query->the_post();
            $index_text = get_post_meta(get_the_ID(), '_search_index', true);
            $snippets = ld_generate_snippets((string) $index_text, $keywords, 8, 5);

            echo '<div style="margin-bottom:20px;padding:15px;border:1px solid #1c1c1c;border-radius:8px;">';
            echo '<a href="' . esc_url(get_permalink()) . '"><strong>' . esc_html(get_the_title()) . '</strong></a>';

            if (!empty($snippets)) {
                foreach ($snippets as $snippet) {
                    echo '<p style="margin:5px 0;color:#555;">' . wp_kses_post($snippet) . '</p>';
                }
            }
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>' . sprintf(
            /* translators: %s: search keyword */
            esc_html__('No results found for: %s', 'xfusion'),
            '<strong>' . esc_html($keyword) . '</strong>'
        ) . '</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('ld_topic_search', 'ld_topic_search_results');
