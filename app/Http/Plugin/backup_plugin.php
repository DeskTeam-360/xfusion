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

        const baseStorage = 'https://demo.xperiencefusion.com/storage/';
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
                    console.log(response)
                    console.log(window.location.href.split('?')[0], window.location.href.split('?')[1],)

                    if (response.data.status === "setId") {
                        addQueryParam('dataId', response.data.dataId)
                    }
                    if (response.data.status === "redirect") {
                        window.location.replace(response.data.url)
                    }

                    if (response.data.logo_url !== null) {
                        const company_logo = document.getElementsByClassName("wp-image-11067");
                        company_logo[0].src = response.data.logo_url.replace("public/", baseStorage);
                        const qrcode = document.getElementsByClassName("wp-image-1124");
                        qrcode[0].src = response.data.qrcode_url.replace("public/", baseStorage);
                        qrcode[0].srcset = "";
                    } else {
                        const company_logo = document.getElementsByClassName("wp-image-11067");
                        const a = "https://demo.xperiencefusion.com/wp-content/uploads/2024/08/FUSION_Transparent-black-font.png";
                        company_logo[0].src = a;

                    }

                    f(response.data.form_id, response.data.url_next)


                },
                error: function (xhr, status, error) {
                    console.error(xhr);
                    console.error(status);
                    console.error(error);
                }
            });
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

            function f(formId, next) {
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

                        console.log(response)
                        const res = response.data[0]

                        if (next) {
                            console.log(next)
                            const buttonSubmit = document.querySelector('.gform_button');


                            if (buttonSubmit) {
                                // Buat elemen <a> baru
                                const newLink = document.createElement('a');
                                newLink.href = next // Ganti dengan link tujuan
                                newLink.className = buttonSubmit.className; // Salin class
                                newLink.style.cssText = buttonSubmit.style.cssText; // Salin inline style

                                if (buttonSubmit.value === "") {

                                    newLink.textContent = "Next Lesson"; // Salin inline style
                                } else if (buttonSubmit.value === 'Mark as Complete') {

                                    newLink.textContent = 'Return to Menu'
                                } else {

                                    newLink.textContent = buttonSubmit.value; // Gunakan teks yang sama
                                }

                                if (buttonSubmit.id) {
                                    newLink.id = buttonSubmit.id;
                                }


                                // Ganti tombol lama dengan elemen <a>
                                buttonSubmit.parentNode.replaceChild(newLink, buttonSubmit);

                            }
                            const buttonSubmit2 = document.querySelector('.mark-as-complete');
                            if (buttonSubmit2) {
                                const newLink = document.createElement('a');
                                newLink.href = next // Ganti dengan link tujuan
                                newLink.className = buttonSubmit2.className; // Salin class
                                newLink.style.cssText = buttonSubmit2.style.cssText; // Salin inline style
                                newLink.style.color='white';
                                newLink.style.textTransform='none';

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
                                        document.getElementsByName('input_' + key)[0].value = response.data[0][key]
                                    }
                                    document.getElementsByName('input_' + key)[0].disable = true
                                    document.getElementsByName('input_' + key)[0].readOnly = true
                                }
                            }
                        }


                    },
                    error: function (xhr, status, error) {
                        console.log(error)
                    }
                });
            }
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
            $keapTags = get_usermeta($userID, 'keap_tags');


            $query = "select * from companies where id=$companyID";
            $click_logs = $wpdb->get_results($query);


            $result = [];
            $user = get_userdata($userID);
            $user_roles = $user->roles;
            foreach ($click_logs as $log) {
                $result['logo_url'] = $log->logo_url;
                $result['qrcode_url'] = $log->qrcode_url;
            }

            $temp_data2 = $url;


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


            $query = "select meta_value from wp_usermeta where user_id=$userID and meta_key='keap_tags' ";
            $t = $wpdb->get_results($query);


            $result = [];
            $user = get_userdata($userID);
            $user_roles = $user->roles;
            foreach ($click_logs as $log) {
                $result['logo_url'] = $log->logo_url;
                $result['qrcode_url'] = $log->qrcode_url;
            }

            $query = "SELECT id,form_id FROM wp_gf_entry where source_url = '$url' and created_by = '$userID' and status='active'";
            $checkEntry = $wpdb->get_results($query);

            foreach ($checkEntry as $check) {
                $message = "You've done the topic";
                $status = 'redirect';
                if ($_POST['param'] == 'dataId=' . $check->id) {
                    wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'form_id' => $check->form_id, 'url_next' => $limit->url_next]);
                    wp_die();
                }
                wp_send_json_success(['url' => $url . '/?dataId=' . $check->id, 'dataId' => $check->id, 'status' => $status, 'message' => $message, 'logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                wp_die();
            }

            if ($limit->keap_tag == null) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => $url]);
                wp_die();
            }

            if (in_array($limit->keap_tag, explode(';', $keapTags))) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => 'asdasdasd']);
                wp_die();
            }

            if (in_array('administrator', $user_roles, true)) {
                wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url'], 'asd' => 'asdasdasdaa']);
                wp_die();
            }


            if (in_array($limit->keap_tag_parent, explode(';', $keapTags))) {
                $status = 'redirect';
                $message = "You need waiting " . $limit->delay + 5 . "minutes from last submit";
                wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message]);
                wp_die();
            }

            $status = 'redirect';
            $message = "You don't have access";
            wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message]);
            wp_die();
        }
        $url = $limit->redirect_url;
        $status = 'redirect';
        $message = "You need login ";
        wp_send_json_success(['url' => "https://demo.xperiencefusion.com/sustain/sustain-menu/self-actualization/", 'status' => $status, 'message' => $message]);
        wp_die();
    }

    wp_send_json_success(['logo_url' => null, 'qrcode_url' => null, 'asd' => 'asdasd']);
    wp_die();
}

add_action('wp_ajax_get_company_info', 'get_company_info');
add_action('wp_ajax_nopriv_get_company_info', 'get_company_info', 1, 3);
