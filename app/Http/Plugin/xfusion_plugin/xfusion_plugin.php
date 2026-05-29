<?php

/**
 * Plugin Name: Xperience Fusion  Plugin
 * Description: Plugin for Xperience Fusion.
 * Version: 1.2.0
 * Author: Deskteam360
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

if (!defined('XFUSION_PLUGIN_DIR')) {
    define('XFUSION_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

require_once XFUSION_PLUGIN_DIR . 'includes/load.php';

function company_detect()
{
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
(function ($) {
    'use strict';

    /** URL admin-ajax (satu sumber kebenaran; hindari path hardcode). */
    var XFUSION_AJAX_URL = <?php echo wp_json_encode($ajax_url); ?>;

    /** Base URL untuk mengganti prefix path storage logo/qrcode dari Laravel. */
    var BASE_STORAGE = window.location.protocol + '//admin.' + window.location.host + '/storage/';

    /** Dibutuhkan shortcode LearnDash / markup lain â€” tetap global. */
    window.openWindowXfusion = function (href) {
        window.open(href, '_blank', 'noopener,noreferrer');
    };

    function getUrlParameter(name) {
        var params = new URLSearchParams(window.location.search);
        if (!params.has(name)) {
            return false;
        }
        var raw = params.get(name);
        return raw === '' || raw === null ? true : decodeURIComponent(raw);
    }

    function addQueryParam(key, value) {
        var url = new URL(window.location.href);
        url.searchParams.set(key, String(value));
        window.history.pushState({}, '', url.toString());
    }

    function moveCloseButton() {
        var closeButton = document.querySelector('#container-revitlize-center .btn-close');
        var container = document.querySelector('#container-revitlize-center');
        if (!closeButton || !container) {
            return;
        }
        var prevDiv = container.previousElementSibling;
        if (!prevDiv || prevDiv.nodeType !== 1) {
            return;
        }
        prevDiv.appendChild(closeButton);
        if (!document.querySelector('#custom-close-css')) {
            var style = document.createElement('style');
            style.id = 'custom-close-css';
            style.textContent = '.btn-close{display:block!important;margin:10px 0!important;}';
            document.head.appendChild(style);
        }
    }

    /**
     * Pasang tombol "Close tab" di container revitalize (satu implementasi, dipakai berulang).
     */
    function appendCloseTabButton(container) {
        if (!container) {
            return null;
        }
        var existing = container.querySelector('.btn-close');
        if (existing) {
            return existing;
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-close';
        btn.textContent = 'Close tab';
        btn.style.display = 'block';
        btn.style.marginTop = '10px';
        btn.addEventListener('click', function () {
            window.close();
        });
        container.appendChild(btn);
        return btn;
    }

    function syncCompanyBranding(data) {
        if (data.logo_url !== null && data.logo_url !== undefined && data.logo_url !== '') {
            var companyLogo = document.getElementsByClassName('wp-image-11067');
            if (companyLogo.length > 0) {
                companyLogo[0].src = String(data.logo_url).replace('public/', BASE_STORAGE);
            }
            var qrcode = document.getElementsByClassName('wp-image-1124');
            if (qrcode.length > 0) {
                qrcode[0].src = String(data.qrcode_url || '').replace('public/', BASE_STORAGE);
                qrcode[0].srcset = '';
            }
            var companyLogoLink = document.querySelector('#company-logo > div > a');
            if (companyLogoLink) {
                companyLogoLink.href = data.company_url ? data.company_url : '#';
            }
        } else {
            var fallbackImg = document.querySelector('#company-logo > div > a > img');
            if (fallbackImg) {
                fallbackImg.src = 'http://xperiencefusion.com/wp-content/uploads/2025/08/XFUSION_Transparent.png';
            }
        }
    }

    /**
     * Mode btn-close=true: sesuaikan tombol Prev / Next / Close sesuai tools (repeat entry).
     */
    function applyBtnCloseToolbarLayout(data) {
        var tools = data.tools;
        var btnCloseParam = getUrlParameter('btn-close');
        if (btnCloseParam !== 'true') {
            return;
        }

        if (tools == 1) {
            var buttonSubmit99 = document.querySelector('.gform_button');
            var buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
            if (buttonSubmit3 && buttonSubmit99) {
                var replaceBtn = document.createElement('button');
                replaceBtn.type = 'button';
                replaceBtn.className = 'btn-close';
                replaceBtn.textContent = 'Close tab';
                replaceBtn.style.display = 'block';
                replaceBtn.style.marginTop = '10px';
                replaceBtn.addEventListener('click', function () {
                    window.close();
                });
                buttonSubmit3.parentNode.replaceChild(replaceBtn, buttonSubmit3);
            } else {
                var forms = document.querySelector('#container-revitlize-center');
                var btnCloseEl = document.querySelector('.btn-close');
                if (forms && !btnCloseEl) {
                    var prevAgain = document.querySelector('#btn-prev-revitalize');
                    if (prevAgain) {
                        prevAgain.remove();
                    }
                    appendCloseTabButton(forms);
                }
            }
            if (buttonSubmit99) {
                moveCloseButton();
            }
            var buttonSubmit4 = document.querySelector('#btn-next-revitalize');
            if (buttonSubmit4) {
                buttonSubmit4.remove();
            }
        } else {
            var bs99 = document.querySelector('.gform_button');
            var bs3 = document.querySelector('#btn-prev-revitalize');
            if (bs3) {
                bs3.remove();
            }
            var bs4 = document.querySelector('#btn-next-revitalize');
            if (bs4) {
                bs4.remove();
            }
            var formsBlock = document.querySelector('#container-revitlize-center');
            var btnCloseGlobal = document.querySelector('.btn-close');
            if (formsBlock && !btnCloseGlobal) {
                var prevDup = document.querySelector('#btn-prev-revitalize');
                if (prevDup) {
                    prevDup.remove();
                }
                appendCloseTabButton(formsBlock);
            }
            if (bs99) {
                moveCloseButton();
            }
        }
    }

    /**
     * Ambil data entry GF via AJAX, isi field readonly + opsi ganti tombol submit jadi link "Return to menu".
     */
    function prefillGravityForm(formId, nextUrl, tools) {
        var formIdNum = parseInt(formId, 10);
        if (!formIdNum || formIdNum < 1) {
            return;
        }
        $.ajax({
            url: XFUSION_AJAX_URL,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_form_data_gform',
                form_id: formIdNum,
                order_id: getUrlParameter('dataId'),
            },
            success: function (response) {
                if (!response || !response.data || !response.data[0]) {
                    return;
                }
                var row = response.data[0];
                var attempts = 0;
                /** GF sering render setelah get_company_info selesai â€” tunggu wrapper/input ada. */
                function applyPrefillWhenDomReady() {
                    attempts += 1;
                    var wrap = document.getElementById('gform_wrapper_' + formIdNum);
                    var ready = wrap && wrap.querySelector('[name^="input_"]');
                    if (!ready && attempts < 50) {
                        window.setTimeout(applyPrefillWhenDomReady, 100);
                        return;
                    }

                if (getUrlParameter('btn-close') == 'true' && tools && row) {
                    var submitRm = document.querySelector('.gform_button');
                    if (submitRm) {
                        submitRm.remove();
                    }
                }

                if (getUrlParameter('btn-close') == 'true') {
                    var container = document.getElementById('container-revitlize-center');
                    var existingClose = document.querySelector('.btn-close');
                    if (container) {
                        if (existingClose) {
                            container.appendChild(existingClose);
                        } else {
                            appendCloseTabButton(container);
                        }
                    }
                }

                if (nextUrl && getUrlParameter('btn-close') != 'true') {
                    var buttonSubmit = document.querySelector('.gform_button');
                    if (buttonSubmit) {
                        var newLink = document.createElement('a');
                        newLink.href = nextUrl;
                        newLink.className = buttonSubmit.className + ' btn-close';
                        newLink.style.cssText = buttonSubmit.style.cssText + '; text-align: center; position:static';

                        var val = buttonSubmit.value;
                        if (val === '' || val === undefined || val === 'Next Lesson' || val === 'Done' || val === 'Submit' || val === 'Complete Session') {
                            newLink.textContent = 'Return to menu';
                            var g13 = document.querySelector('#gform_submit_button_13');
                            if (g13) {
                                g13.remove();
                            }
                        } else if (val === 'Mark as Complete') {
                            newLink.textContent = 'Return to Menu';
                        } else {
                            newLink.textContent = val;
                        }

                        if (buttonSubmit.id) {
                            newLink.id = buttonSubmit.id;
                        }

                        var wrap = document.getElementById('container-revitlize-center');
                        buttonSubmit.parentNode.replaceChild(newLink, buttonSubmit);
                        if (newLink && wrap) {
                            wrap.appendChild(newLink);
                            var btnPrev = document.getElementById('btn-prev-revitalize');
                            if (btnPrev) {
                                btnPrev.remove();
                            }
                        }
                    }

                    var markComplete = document.querySelector('.mark-as-complete');
                    if (markComplete) {
                        var link2 = document.createElement('a');
                        link2.href = nextUrl;
                        link2.className = markComplete.className;
                        link2.style.cssText = markComplete.style.cssText;
                        link2.style.color = 'white';
                        link2.style.textTransform = 'none';
                        if (markComplete.textContent === '') {
                            link2.textContent = 'Next Lesson';
                        } else if (markComplete.textContent === 'Mark as Complete') {
                            link2.textContent = 'Return to Menu';
                        } else {
                            link2.textContent = markComplete.textContent;
                        }
                        if (markComplete.id) {
                            link2.id = markComplete.id;
                        }
                        markComplete.parentNode.replaceChild(link2, markComplete);
                    }
                }

                if (getUrlParameter('btn-close') == 'true') {
                    var mc = document.querySelector('.mark-as-complete');
                    if (mc) {
                        var closeBtn = document.createElement('button');
                        closeBtn.type = 'button';
                        closeBtn.className = mc.className;
                        closeBtn.textContent = 'Close tab';
                        closeBtn.style.display = 'block';
                        closeBtn.style.marginTop = '10px';
                        closeBtn.addEventListener('click', function () {
                            window.close();
                        });
                        mc.parentNode.replaceChild(closeBtn, mc);
                    }
                }

                Object.keys(row).forEach(function (key) {
                    if (!row.hasOwnProperty(key)) {
                        return;
                    }
                    if (isNaN(key)) {
                        return;
                    }
                    var inputs = document.getElementsByName('input_' + key);
                    if (!inputs || !inputs[0]) {
                        return;
                    }
                    var el = inputs[0];

                    if (el.type === 'radio') {
                        document.querySelectorAll('input[name="' + 'input_' + key + '"]').forEach(function (radio) {
                            radio.disabled = true;
                        });
                        var chosen = document.querySelector('input[name="' + 'input_' + key + '"][value="' + row[key] + '"]');
                        if (chosen) {
                            chosen.checked = true;
                            chosen.disabled = false;
                        }
                    } else if (key % 1 !== 0) {
                        if (row[key] !== '') {
                            el.checked = true;
                        }
                        el.disabled = true;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (el.type === 'file') {
                        var downloadBtn = document.createElement('a');
                        downloadBtn.textContent = 'Download file';
                        downloadBtn.className = 'previous-lesson-button';
                        downloadBtn.style.marginTop = '10px';
                        downloadBtn.style.padding = '10px';
                        downloadBtn.href = row[key];
                        downloadBtn.target = '_blank';
                        el.parentNode.insertBefore(downloadBtn, el.nextSibling);
                    } else {
                        el.value = row[key];
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    el.disabled = true;
                    el.readOnly = true;
                });
                }
                applyPrefillWhenDomReady();
            },
        });
    }

    $(document).on('gform_confirmation_loaded', function () {
        $('.btn-close').appendTo('#container-revitlize-center');
        $('#btn-prev-revitalize').appendTo('#container-revitlize-center');
    });

    (function movePrevRevitalizeWhenNoSubmit() {
        var submitBtn = document.querySelector('.gform_button');
        var prevBtn = document.querySelector('#btn-prev-revitalize');
        var container = document.querySelector('#container-revitlize-center');
        if (prevBtn && !submitBtn && container) {
            container.appendChild(prevBtn);
            moveCloseButton();
        }
    })();

    document.addEventListener('DOMContentLoaded', function () {
        $.ajax({
            url: XFUSION_AJAX_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_company_info',
                url: window.location.href.split('?')[0],
                param: window.location.href.split('?')[1],
            },
            success: function (response) {
                if (!response || !response.data) {
                    return;
                }
                var d = response.data;
                var tools = d.tools ?? 0;

                if (d.status === 'setId') {
                    addQueryParam('dataId', d.dataId);
                    if (d.form_id) {
                        prefillGravityForm(d.form_id, d.url_next, tools);
                    }
                }
                if (d.status === 'return') {
                    addQueryParam('dataId', d.dataId);
                    prefillGravityForm(d.form_id, d.url_next, tools);
                }
                if (d.status === 'redirect') {
                    alert(d.message);
                    if (getUrlParameter('btn-close') == 'true') {
                        window.close();
                    } else {
                        window.location.replace(d.url);
                    }
                }

                syncCompanyBranding(d);
                applyBtnCloseToolbarLayout(d);

                if (getUrlParameter('dataId')) {
                    prefillGravityForm(d.form_id, d.url_next, tools);
                    if (getUrlParameter('btn-close') === 'true') {
                        document.querySelectorAll('.gform_button').forEach(function (btn) {
                            btn.remove();
                        });
                    }
                }
            },
        });
    });
})(jQuery);
    </script>
    <?php
}

add_action("wp_head", "company_detect");

function get_company_info()
{
    global $wpdb;

    $url = isset($_POST['url']) ? sanitize_text_field(wp_unslash($_POST['url'])) : '';

    $limitLinks = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM wp_course_lists WHERE url = %s',
            $url
        )
    );

    $userID = get_current_user_id();
    $qu = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}users WHERE id = %d",
            $userID
        )
    );

    $t5 = json_encode($qu);

    $data = json_decode($t5, true);
    if (!is_array($data) || !isset($data[0]['user_login'])) {
        $user_login = '';
    } else {
        $user_login = str_replace(' ', '+', strtolower($data[0]['user_login']));
    }

    $arrayLinks = [
        "/user/$user_login/",
        "/lms-home-screen/",
        "/topics/dependability/",
        "/account/",
        "/resources/resource-menu/",
		"/start-here/",
		"/individual-contributor/",
		"/leader/",
    ];

    if (!$limitLinks) {
        if ($userID != null) {
            $companyID = get_usermeta($userID, "company");

            $query = 'SELECT * FROM wp_companies WHERE id = %d';
            $click_logs = $wpdb->get_results(
                $wpdb->prepare($query, (int) $companyID)
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

            $click_logs = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM wp_companies WHERE id = %d',
                    (int) $companyID
                )
            );

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

            $checkEntry = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT id, form_id FROM wp_gf_entry WHERE form_id = %d AND created_by = %d AND status = %s',
                    (int) $limit->wp_gf_form_id,
                    (int) $userID,
                    'active'
                )
            );

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
                    "form_id" => $check->form_id,
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

// Ultimate Member — [xfusion_um_profile_courses] : includes/um-profile-courses*.php (muat via includes/load.php).


