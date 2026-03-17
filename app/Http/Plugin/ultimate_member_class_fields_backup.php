
//get user
            $current_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $path = parse_url($current_url, PHP_URL_PATH);
            $user = basename(rtrim($path, '/'));
            if (strpos($user, "+") !== false) {
                $user = str_replace("+", " ", $user);
            }
            //


            global $wpdb;
            $query = "SELECT * FROM {$wpdb->prefix}users WHERE user_login = '$user'";
            $user_id = $wpdb->get_results($query)[0]->ID;
            if ($user_id==0){
                $user_id=get_current_user_id();
            }
            // print_r($wpdb->get_results($query)[0]);
            // echo " testttttt $user_id ==== $query";


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
                    // translators: %s: edit user link.


                    global $wpdb;

                    $query = "SELECT * FROM course_groups where tools=0 order by order_group ";
                    $cg_list = $wpdb->get_results($query);


                    //print_r($q_list);

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
                            border: 1px solid #ccc; /* Optional border */
                            border-radius: 5px; /* Optional rounded corners */
                            transition: background-color 0.3s, transform 0.3s; /* Smooth transition */
                            text-align: center;
                        }
                        .note-column:hover {
                            background-color: #f0f0f0; /* Change background color on hover */
                            transform: scale(1.05); /* Slightly increase size on hover */
                        }

                        /* Modal styles */
                        .modal {
                            display: none; /* Hidden by default */
                            position: fixed; /* Stay in place */
                            z-index: 1; /* Sit on top */
                            left: 0;
                            top: 0;
                            width: 100%; /* Full width */
                            height: 100%; /* Full height */
                            overflow: auto; /* Enable scroll if needed */
                            background-color: rgb(0,0,0); /* Fallback color */
                            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
                        }

                        .modal-content {
                            background-color: #fefefe;
                            margin: 15% auto; /* 15% from the top and centered */
                            padding: 20px;
                            border: 1px solid #888;
                            width: 80%; /* Could be more or less, depending on screen size */
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
                        $accordion_id = 'accordion_' . uniqid(); // unique ID for each accordion

                        // Accordion header
                        $output .= "<div class='accordion-item'>";
                        $output .= "<div class=' accordion-box accordion-header' onclick=\"toggleAccordion('$accordion_id')\" style='font-size: 26px; cursor: pointer; margin: 0;'>$cg->title - $cg->sub_title</div>";

                        // Accordion content
                        $output .= "<div id='$accordion_id' class='accordion-content' style='display: none; padding: 10px;'>";

                        $query = "SELECT * FROM course_group_details where course_group_id = $cg->id order by orders";
                        $q_list = $wpdb->get_results($query);

                        if (count($q_list) == 0) {
                            $output .= "<div style='font-size: 24px'>Coming soon </div>";
                        }

                        $output .= "<div class='profile-notes' style='gap: 10px'>";
                        foreach ($q_list as $q) {
                            $temp_id = (int)$q->course_list_id;
                            $query = "SELECT * FROM course_lists WHERE id = $temp_id";
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
                                // var_dump("a".$query);
                            } catch (Exception $e) {
                                $entry_id = false;
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

                            if ($entry_id) {
                                $output .= '<a 
                onclick="openWindowXfusion(\'' . $link_child . '?dataId=' . $entry_id . '&btn-close=true\')"
                 class="note-column" style="color: #666; font-weight: bold">
                <span>' . $c_list[0]->page_title . '</span>
            </a>';
                            } else {
                                $output .= '<a href="' . $link_child . '" class="note-column" style="color: red; pointer-events: none;">
                <span>' . $c_list[0]->page_title . '</span>
            </a>';
                            }
                        }

                        $output .= "</div></div></div>"; // close profile-notes, accordion-content, and accordion-item
                    }


                    $query = "SELECT * FROM course_groups WHERE tools=1 ORDER BY order_group";
                    $cg_list = $wpdb->get_results($query);

                    $output .= "<h2 style='text-align: center'>Tool List</h2>";

                    foreach ($cg_list as $cg) {
                        // Level 1 Accordion
                        $output .= "<div class='accordion-item'>";
                        $output .= "<div class='accordion-tools accordion-box accordion-header' style='font-size: 26px; margin: 0'>$cg->title</div>";
                        $output .= "<div class='panel-tools accordion-content' style='display: none; flex-direction: column'>";

                        $query = "SELECT * FROM course_group_details WHERE course_group_id = $cg->id ORDER BY orders";
                        $q_list = $wpdb->get_results($query);

                        if (count($q_list) == 0) {
                            $output .= "<div style='font-size: 20px; padding: 10px;'>Coming soon</div>";
                        }

                        foreach ($q_list as $q) {
                            $temp_id = (int)$q->course_list_id;
                            $query = "SELECT * FROM course_lists WHERE id = $temp_id";
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

                            // Level 2 Accordion
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

                            $output .= "</div>"; // end panel-tools (level 2)
                        }

                        $output .= "</div>"; // end panel-tools (level 1)
                        $output .= "</div>"; // end panel-tools (level 1)
                    }

// Script
                    $output .= '
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Time Localization
    document.querySelectorAll(".note-column").forEach(function (element) {
        let timestamp = element.getAttribute("data-timestamp");
        if (timestamp) {
            let date = new Date(timestamp * 1000);
            let options = { year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit" };
            let formattedDate = date.toLocaleString(undefined, options);
            element.querySelector(".localized-time").innerText = formattedDate;
        }
    });

    // Accordion behavior
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



                    //$output .= '<p class="um-profile-note">' . $emo . '<span>' . sprintf( __( 'Your profile is looking a little empty. Why not <a href="%s">add</a> some information!', 'ultimate-member' ), esc_url( $edit_url ) ) . '</span></p>';
                } else {
                    $output .= '<p class="um-profile-note">' . $emo . '<span>' . __('This user has not added any information to their profile yet.', 'ultimate-member') . '</span></p>';
                }
            }