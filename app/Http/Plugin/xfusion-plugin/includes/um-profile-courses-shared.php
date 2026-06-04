<?php
/**
 * Ultimate Member profile — course/tool blocks: resolver user ID + CSS + toggleAccordion().
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/**
 * Cocok dengan URL profil UM (slug user_login di path terakhir).
 */
function xfusion_um_profile_courses_resolve_user_id(): int
{
    global $wpdb;

    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $current_url = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    $path = parse_url($current_url, PHP_URL_PATH);
    $userSlug = basename(rtrim(is_string($path) ? $path : '', '/'));
    if (strpos($userSlug, '+') !== false) {
        $userSlug = str_replace('+', ' ', $userSlug);
    }
    $userSlug = sanitize_text_field($userSlug);

    $user_result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}users WHERE user_login = %s",
            $userSlug
        )
    );
    $user_id = ($user_result && isset($user_result[0])) ? (int) $user_result[0]->ID : 0;
    if ($user_id === 0) {
        $user_id = (int) get_current_user_id();
    }

    return $user_id;
}

function xfusion_um_profile_courses_inline_styles_and_toggle(): string
{
    return '<style>
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
}
