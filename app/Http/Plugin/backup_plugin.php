<?php

/**
 * Plugin Name: xfusion logo changer
 * Description: Plugin untuk menginjek fungsi updateIssue pada Wordfence.
 * Version: 1.0
 * Author: deskteam360
 */


function company_detect()
{
    ?>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        var data = {
            url: window.location.href.split('?')[0]
        }
        document.addEventListener('DOMContentLoaded', function () {
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'post',
                dataType: "json",
                data: {
                    action: 'get_company_info',
                    url: window.location.href.split('?')[0],
                },
                success: function (response) {
                    if (response.data.status === "redirect") {
                        window.setTimeout(
                            function () {
                                alert(response.data.message)
                            },
                            1000);
                        window.location.replace(response.data.url)
                    }
                    if (response.data.logo_url !== null) {
                        const company_logo = document.getElementsByClassName("wp-image-5940");
                        company_logo[0].src = response.data.logo_url.replace("public/", "https://admin.teamsetup-2.deskteam360.com/storage/");
                        const qrcode = document.getElementsByClassName("wp-image-1124");
                        qrcode[0].src = response.data.qrcode_url.replace("public/", "https://admin.teamsetup-2.deskteam360.com/storage/");
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
    $limitLinks = $wpdb->get_results($query);
    $wpdb->insert('log', array('log'=>'test 1'));
    foreach ($limitLinks as $limit) {
        $userID = get_current_user_id();

        if ($userID != null) {

            $companyID = get_usermeta($userID, 'company');

            $query = "select * from companies where id=$companyID";

            $click_logs = $wpdb->get_results($query);

            $result = [];

            foreach ($click_logs as $log) {
                $result['logo_url'] = $log->logo_url;
                $result['qrcode_url'] = $log->qrcode_url;
            }

            $query = "select * from schedule_executions where link='$url' and company_id =$companyID and user_id ='$userID'";
            $schedules = $wpdb->get_results($query);

            foreach ($schedules as $schedule) {

                $now = date("Y-m-d h:i:s");

                if ($schedule->schedule_access == null) {

                    $query = "SELECT cls.id as id,
       cls.url as course_url,
       cls.course_title as course_title,
       cls.page_title as page_title,
       cl.url as course_url_parent,
       week FROM course_schedule_generates csg
           join course_lists cls on cls.id=csg.course_list_id
           join course_lists cl on cl.id=csg.course_list_parent_id
            where cl.url = '$url'";

                    $wpdb->insert('log', array('log'=>$query));
                    $generateTemplates = $wpdb->get_results($query);

                    foreach ($generateTemplates as $template) {
                        $wpdb->insert('log', array('log'=>json_encode($template)));

                        $start_date = date('Y-m-d H:i:s');
                        $date = strtotime($start_date);
                        $dateStart = $template->week - 1;
                        $dateEnd = $template->week;

                        $wpdb->insert('log', array('log'=>$url . '  '. $template->course_url ));
                        if ($url == $template->course_url){
                            $wpdb->update('schedule_executions',
                                array(
                                    'schedule_access'=>date('Y-m-d H:i:s', strtotime("+$dateStart week", $date)),
                                    'schedule_deadline'=>date('Y-m-d H:i:s', strtotime("+$dateEnd week", $date)),
                                ), array('id'=>$schedule->id));
                        }else{
//                            $query2 = "SELECT * FROM course_lists where id = '$template->id'";
//                            $courseList = $wpdb->get_results($query2);

                            $wpdb->insert('log', array('log'=>"$dateStart $dateEnd ".strtotime("+$dateStart week", $date)." ".strtotime("+$dateEnd week", $date) ));
                            $wpdb->insert(
                                'schedule_executions',
                                array(
                                    'link' => $template->course_url,
                                    'company_id' => $companyID,
                                    'user_id' => $userID,
                                    'status' => 0,
                                    'title' => $template->page_title . ' - ' . $template->course_title,
                                    'schedule_access' => date('Y-m-d H:i:s', strtotime("+$dateStart week", $date)),
                                    'schedule_deadline' => date('Y-m-d H:i:s', strtotime("+$dateEnd week", $date)),
                                )
                            );
                        }
                    }
                    wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                    wp_die();
                }

                // schedule cukup buat 1 tanpa waktu generate dari create user

                if ($now >= $schedule->schedule_access) {
                    if ($now <= $schedule->schedule_deadline) {
                        wp_send_json_success(['logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                        wp_die();
                    } else {
                        $url = $limit->redirect_url;
                        $status = 'redirect';
                        $message = "Has passed the limit " . $schedule->schedule_deadline;
                        wp_send_json_success(['url' => $url, 'status' => $status, 'message' => $message, 'logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                        wp_die();
                    }
                } else {
                    $url = $limit->redirect_url;
                    $status = 'redirect';
                    $message = "Can access on " . $schedule->schedule_access;
                    wp_send_json_success(['url' => $url, 'status' => $status, 'message' => $message, 'logo_url' => $result['logo_url'], 'qrcode_url' => $result['qrcode_url']]);
                    wp_die();
                }
            }
            $url = $limit->redirect_url;
            $status = 'redirect';
            $message = "You don't have access";
            wp_send_json_success(['url' => "../../../", 'status' => $status, 'message' => $message]);
            wp_die();
        }
    }

    wp_send_json_success(['logo_url' => null, 'qrcode_url' => null]);
    wp_die();
}

add_action('wp_ajax_get_company_info', 'get_company_info', 1, 3);
