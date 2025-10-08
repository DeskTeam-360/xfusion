<?php
function get_filtered_course_progresss($user_id, $lesson_id)
{
    if (!is_user_logged_in()) {
        return 'Silakan login untuk melihat progress.';
    }

// Dapatkan semua topik dalam kursus

    $topic_ids = [];
    $lesson_ids = [];
    $completed_topic_ids = [];

    $topics = learndash_get_topic_list($lesson_id);


    foreach ($topics as $topic) {
        $topic_ids[] = $topic->ID;
        $completed = learndash_is_topic_complete(get_current_user_id(), $topic->ID);
        if ($completed) {
            $completed_topic_ids[] = $topic->ID;
            $data0[$topic->ID] = 1;
        }
    }
    $dt = [
        0, 0, 0, 0,
    ];
    $dr = [
        0, 0, 0, 0,
    ];

    foreach ($data as $index => $dd) {
        foreach ($dd as $k => $d) {
            $dt[$index] += 1;
            if ($data0[$k] == 1) {
                $dr[$index] += 1;
            }

        }
    }
    $t = 0;
    foreach ($dt as $k => $d) {
        $t += ($dr[$k] / $dt[$k]) * 25;
    }


// Hilangkan duplikasi ID
    $unique_topic_ids = array_unique($topic_ids);
    $unique_completed_topic_ids = array_unique($completed_topic_ids);

// Hitung total topik & topik yang sudah selesai
    $total_topics = count($unique_topic_ids);
    $completed_topics = count($unique_completed_topic_ids);

// Gabungkan ID unik menjadi string jika ingin menampilkannya
    $ids = implode(" ", $unique_topic_ids);
    $idss = implode(" ", $unique_completed_topic_ids);
    $idsss = implode(" ", $lesson_ids);

//    if ($total_topics == 0) {
//        return 'Belum ada topik aktif dalam kursus ini.';
//    }

//    $progress = round(($completed_topics / $total_topics) * 100, 2);
    $progress = round($t, 2);

    return [$progress, $completed_topics, $total_topics, $ids, $idss, $idsss, count($lesson_ids)];
}