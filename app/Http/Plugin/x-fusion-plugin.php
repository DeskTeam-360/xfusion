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


        function openWindowXfusion(link) {
            window.open(link, '_blank'); // Buka Google di tab baru
        }

        const baseStorage = 'https://demo.xperiencefusion.com/xfusion-laravel/public/storage/';
        var data = {
            url: window.location.href.split('?')[0]
        }
        const addQueryParam = (key, value) => {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            window.history.pushState({}, '', url.toString());
        };
        jQuery(document).ready(function ($) {
            jQuery(document).on('gform_confirmation_loaded', function() {
                jQuery('.btn-close').appendTo('#container-revitlize-center');
                jQuery('#btn-prev-revitalize').appendTo('#container-revitlize-center');
            });
        })


        const buttonSubmit99 = document.querySelector('.gform_button');
        const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
        let container = document.querySelector("#container-revitlize-center");

        if (buttonSubmit3 && !buttonSubmit99 && container) {
            container.appendChild(buttonSubmit3);
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
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
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
                        window.location.replace(response.data.url)
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
                        //const cll = document.querySelector("#company-logo > div > a");

                    } else {
                        const company_logo = document.getElementsByClassName("wp-image-11067");
                        if (company_logo){
                            company_logo[0].src = "https://demo.xperiencefusion.com/wp-content/uploads/2024/08/FUSION_Transparent-black-font.png";
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


                            const buttonSubmit4 = document.querySelector('#btn-next-revitalize');
                            if (buttonSubmit4) {
                                buttonSubmit4.remove();
                            }

                        } else {


                            // const buttonSubmit2 = document.querySelector('.mark-as-complete');
                            // if (buttonSubmit2) {
                            //     buttonSubmit2.remove();
                            // }
                            const buttonSubmit3 = document.querySelector('#btn-prev-revitalize');
                            if (buttonSubmit3) {
                                buttonSubmit3.remove();
                            }
                            const buttonSubmit4 = document.querySelector('#btn-next-revitalize');
                            if (buttonSubmit4) {
                                buttonSubmit4.remove();
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
                error: function (xhr, status, error) {
                    console.error(xhr);
                    console.error(status);
                    console.error(error);
                }
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
                        if (getUrlParameter('btn-close') == 'true' && tools && res) {
                            const buttonSubmit = document.querySelector('.gform_button');
                            if (buttonSubmit) {
                                buttonSubmit.remove()
                            }
                        }
                        if (getUrlParameter('btn-close') == 'true') {
                            const btn = document.querySelector(".btn-close");
                            const container = document.getElementById("container-revitlize-center");
                            if (container){
                                if (btn) {
                                    container.appendChild(btn);
                                }else{
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

                                newLink.style.cssText = buttonSubmit.style.cssText+'; text-align: center; position:static'; // Salin inline style

                                if (buttonSubmit.value === "" || buttonSubmit.value === undefined ||  buttonSubmit.value==="Next Lesson" ||  buttonSubmit.value==="Done" || buttonSubmit.value==="Submit") {
                                    newLink.textContent = "Return to menu"; // Salin inline style
                                    const buttonSubmit = document.querySelector('#gform_submit_button_13').remove();



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
                                    if (btnPrev){
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

                                        } else {
                                            console.warn(`Radio button not found for selector: input[name="${'input_' + key}"][value="${response.data[0][key]}"]`);
                                        }

                                    } else if (key % 1 !== 0) {
                                        if (response.data[0][key] !== '') {
                                            document.getElementsByName('input_' + key)[0].checked = true
                                        }
                                        document.getElementsByName('input_' + key)[0].disabled = true
                                    }
                                    if (document.getElementsByName('input_' + key)[0]['type'] === "file") {
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
                                    }
                                    document.getElementsByName('input_' + key)[0].disable = true
                                    document.getElementsByName('input_' + key)[0].readOnly = true
                                }
                            }
                        }


                    },
                    error: function (xhr, status, error) {
                        console.error(error)
                    }
                });
            }
        })

    </script>
    <?php
}

add_action('wp_head', 'company_detect');

function get_company_info()
{
    global $wpdb;

    $url = $_POST['url'];
    $query = "select * from course_lists where url='$url'";

    $limitLinks = $wpdb->get_results($query);


    $userID = get_current_user_id();
    $query_user = "select * from wp_users where id='$userID'";
    $qu = $wpdb->get_results($query_user);

    $t5 = json_encode($qu);

    // Decode JSON to a PHP array
    $data = json_decode($t5, true);

    // Access the "user_login" value
    $user_login = str_replace(' ', '+', strtolower($data[0]['user_login']));


    $arrayLinks = ["https://demo.xperiencefusion.com/user/$user_login/",
        "https://demo.xperiencefusion.com/lms-home-screen/",
        "https://demo.xperiencefusion.com/topics/dependability/",
        "https://demo.xperiencefusion.com/transform/transform-menu/",
        "https://demo.xperiencefusion.com/transform/transform-menu/introduction-level-1/",
        "https://demo.xperiencefusion.com/sustain/sustain-menu/introduction-level-1/",
        "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/",
        "https://demo.xperiencefusion.com/sustain/sustain-menu/",
        "https://demo.xperiencefusion.com/sustain/sustain-menu/intermediate-level-1/",
        "https://demo.xperiencefusion.com/sustain/sustain-menu/intermediate-level-2/",
        "https://demo.xperiencefusion.com/sustain/sustain-menu/intermediate-level-3/",
        "https://demo.xperiencefusion.com/thank-you-for-successfully-completing-this-topic/",
        "https://demo.xperiencefusion.com/account/",
        "https://demo.xperiencefusion.com/resources/resource-menu/",
        "https://demo.xperiencefusion.com/revitalize/course/",
        "https://demo.xperiencefusion.com/transform/transform-menu/", "https://demo.xperiencefusion.com/transform/transform-menu/introduction-level-1/", "https://demo.xperiencefusion.com/transform/transform-menu/intermediate-level-2/",];

    if (!$limitLinks) {

        if ($userID != null) {
            $companyID = get_usermeta($userID, 'company');

            $query = "select * from companies where id=$companyID";
            $click_logs = $wpdb->get_results($query);


            $result = [];
            foreach ($click_logs as $log) {
                $result['logo_url'] = $log->logo_url;
                $result['qrcode_url'] = $log->qrcode_url;
            }
            if (in_array($url, $arrayLinks)) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => 'asdasd1']);
                wp_die();
            }
        }

    }

    foreach ($limitLinks as $limit) {

        $userID = get_current_user_id();
        if ($userID != null) {
            $companyID = get_usermeta($userID, 'company');
            $keapTags = get_usermeta($userID, 'keap_tags');


            $query = "select * from companies where id=$companyID";
            $click_logs = $wpdb->get_results($query);


//            $query = "select meta_value from wp_usermeta where user_id=$userID and meta_key='keap_tags' ";
//            $t = $wpdb->get_results($query);


            $result = [];
            $user = get_userdata($userID);
            $user_roles = $user->roles;

            $user_access = get_user_meta($userID, 'user_access', true);
            if (in_array('administrator', $user_roles, true) or in_array('editor', $user_roles, true)) {

            }else{
                if (stripos($user_access,$limit->course_title) != true ) {
                    $status = 'redirect';
                    $message = "You don't have access to this page";
                    wp_send_json_success(['url' => "https://demo.xperiencefusion.com/lms-home-screen/", 'status' => $status, 'message' => $message,]);
                    wp_die();
                }
            }



            foreach ($click_logs as $log) {
                $result['logo_url'] = $log->logo_url;
                $result['qrcode_url'] = $log->qrcode_url;
            }

            $query = "SELECT id,form_id FROM wp_gf_entry where source_url = '$url' and created_by = '$userID' and status='active'";
            $checkEntry = $wpdb->get_results($query);

            foreach ($checkEntry as $check) {
                $message = "You've done the topic";
                $status = 'return';
                if (isset($_POST['param']) && strpos($_POST['param'], $check->id) !== false) {
                    wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'form_id' => $check->form_id, 'url_next' => $limit->url_next, 'tools' => $limit->repeat_entry]);
                    wp_die();
                }

                if ($limit->repeat_entry == 1) {
                    wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'form_id' => $check->form_id, 'url_next' => $limit->url_next, 'tools' => $limit->repeat_entry]);
                    wp_die();
                }

                wp_send_json_success(['url' => $url . '?dataId=' . $check->id . '&' . $_POST['param'], 'dataId' => $check->id, 'status' => $status, 'message' => $message, 'logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'tools' => $limit->repeat_entry, 'url_next' => $limit->url_next,]);
                wp_die();
            }

            if ($limit->keap_tag == null) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => $url, 'tools' => $limit->repeat_entry]);
                wp_die();
            }

            if (in_array($limit->keap_tag, explode(';', $keapTags))) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => 'asdasdasd', 'tools' => $limit->repeat_entry]);
                wp_die();
            }

            if (in_array('administrator', $user_roles, true)) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => 'asdasdasdaa', 'tools' => $limit->repeat_entry]);
                wp_die();
            }


            if (in_array($limit->keap_tag_parent, explode(';', $keapTags))) {
                $status = 'redirect';
                $message = "You need waiting " . $limit->delay + 5 . "minutes from last submit";
                wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message, 'tools' => $limit->repeat_entry]);
                wp_die();
            }

            $status = 'redirect';
            $message = "You don't have access";
            wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message, 'tools' => $limit->repeat_entry]);
            wp_die();
        }
        $url = $limit->redirect_url;
        $status = 'redirect';
        $message = "You need login ";
        wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message, 'tools' => $limit->repeat_entry]);
        wp_die();
    }

    wp_send_json_success(['logo_url' => null, 'qrcode_url' => null, 'asd' => 'asdasd', 'tools' => $limit->repeat_entry]);
    wp_die();
}

add_action('wp_ajax_get_company_info', 'get_company_info');
add_action('wp_ajax_nopriv_get_company_info', 'get_company_info', 1, 3);

add_filter('wp_hash_password', 'custom_wp_hash_password', 10, 2);

function custom_wp_hash_password($password, $user_id = null) {
    require_once ABSPATH . WPINC . '/class-phpass.php';
    $wp_hasher = new PasswordHash(8, true);
    return $wp_hasher->HashPassword(trim($password));
}


if (!function_exists('wp_hash_password')) {
    function wp_hash_password($password) {
        require_once ABSPATH . WPINC . '/class-phpass.php';
        $hasher = new PasswordHash(8, true); // 8 adalah strength, true untuk portable
        return $hasher->HashPassword(trim($password));
    }
}

if (!function_exists('wp_check_password')) {
    function wp_check_password($password, $hash, $user_id = '') {
        require_once ABSPATH . WPINC . '/class-phpass.php';
        $hasher = new PasswordHash(8, true);
        $check = $hasher->CheckPassword($password, $hash);

        return apply_filters('check_password', $check, $password, $hash, $user_id);
    }
}
