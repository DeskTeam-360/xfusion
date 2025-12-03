<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\CourseList;
use App\Models\CourseGroup;
use App\Models\User;

use App\Models\WpGfEntryMeta;
use App\Models\WpGfFormMeta;
use Livewire\Component;

class ExportResult extends Component
{
    public $results=[];
    public $title;
    public $form_ids=[];
    public $field_target=[];
    public $users = [];
    public $userLists = [];
    public $companies=[];
    public $typeUser=[];
    public $courseLists=[];
    public $fields=[];
    public $optionUsers = [];
    public $optionTypeUser=[];
    public $optionCompanies = [];
    public $optionFields = [];
    public $optionCourseTitle = [];

    public $optionCourseGroupLists = [];
    public $optionCourseLists2 = [];

    public $courseGroupLists = [];
    public $courseLists2 = [];
    public $headerFormat="[clean_course_title] - [clean_question]";

    public $headerFormatPivot1='Clean';
    public $headerFormatPivot2='Clean';
    public $headerFormatPivotOption=['Full', 'Clean'];
    public $table=0;
    
    
    public function mount()
    {
        $this->optionTypeUser = [
            ['value' => 'users', 'title' => 'Users'],
            ['value' => 'companies', 'title' => 'companies'],
        ];
        
        foreach (CourseGroup::get() as $cg){
            
            $this->optionCourseLists2[$cg->id] = $cg->courseGroupDetails->pluck('course_list_id')->toArray();
            $this->optionCourseGroupLists[] = ['value'=>$cg->id, 'title'=>$cg->title.' - '.$cg->sub_title .' ('.count($cg->courseGroupDetails).')'];
        }
        foreach (CourseList::get() as $cl){
            $this->optionCourseTitle[] = ['value'=>$cl->id, 'title'=>$cl->course_title.' - '.$cl->page_title];
        }

        // dd($this->courseGroupLists);

        foreach (User::get() as $user) {
            $this->optionUsers[] = ['value' => $user->ID, 'title' => $user->first_name . ' ' . $user->last_name];
        }
        foreach (Company::get() as $user) {
            $this->optionCompanies[] = ['value' => $user->id, 'title' => $user->title];
        }
        $this->optionFields=['text', 'checkbox', 'number', 'select', 'multiselect', 'radio', 'email', 'name','textarea'];
    }

    public function getHeaderFormat($course_title, $question)
    {   
        $headerFormat = $this->headerFormat;
        $clean_question = $question;
        $clean_course_title = $course_title;

        // daftar kata yang ingin dibersihkan
        $blacklist = [
            'Rate your current ability to',
            '1-5 (5 highest)',
            '1-5 (5 highest).',
            'with yourself and others',
            'Rate your current level of',
            'Rate your ability to',
            'Rate the current status of',
            'Rate your current practices for maintaining',
        'Rate your',
        '(1-5-highest)',
        '(1-5-highest).',
        '(1-5-highest)',
        'Course - Topic : ',
        'Course - Topic'
        ];
        // hapus tanda petik satu dan dua
        $clean_question = str_replace(["'", '"','“','”',' .','.'], '', $clean_question);
        $clean_course_title = str_replace(["'", '"','“','”',' .','.'], '', $clean_course_title);

        // hapus kata-kata blacklist
        $clean_question = str_ireplace($blacklist, '', $clean_question);
        $clean_course_title = str_ireplace($blacklist, '', $clean_course_title);    

        // ganti banyak spasi jadi satu spasi
        $clean_question = preg_replace('/\s+/', ' ', $clean_question);
        $clean_course_title = preg_replace('/\s+/', ' ', $clean_course_title);
        // trim
        $clean_question = trim($clean_question);
        $clean_course_title = trim($clean_course_title);
        // ganti spasi dengan underscore

        

        $headerFormat = str_replace('[course_title]', $course_title, $headerFormat);
        $headerFormat = str_replace('[question]', $question, $headerFormat);
        $headerFormat = str_replace('[clean_question]', $clean_question, $headerFormat);
        $headerFormat = str_replace('[clean_course_title]', $clean_course_title, $headerFormat);
        return $headerFormat;
    }

    public function getData()
    {
        $this->table = 1;
        $this->getMainData();
    }
    public function getData2()
    {
        $this->table = 2;
        $this->getMainData();
    }
    

    public function getMainData()
    {

     
        $field_types = $this->fields;
        $course_ids = $this->courseLists;
        $companies = $this->companies;
        $users = User::query()->whereIn('ID', $this->users);
        if ($companies != []) {
            $users->orWhereHas('companyEmployee', function ($q) use ($companies) {
                $q->whereIn('company_id', $companies);
            });
        }
        $user_ids = $users->pluck('id')->toArray();
        $this->userLists = $users->get();

        $form_ids = CourseList::whereIn('id', $course_ids)->pluck('wp_gf_form_id')->toArray();

        $form_meta = WpGfFormMeta::whereIn('form_id', $form_ids)->get(['display_meta', 'form_id']);
        $field_target = [];

        foreach ($form_meta as $meta) {
            $f = json_decode($meta->display_meta)->fields;
            foreach ($f as $field) {
                $field_target[$meta->form_id]['form_title'] = $meta->wpGfForm->title;
                if (in_array($field->type, $field_types)) {
                    $field_target[$meta->form_id]['id'][] = $field->id;
                    $field_target[$meta->form_id]['title'][$field->id] = $field->label;
                }
            }
        }
        $entries = WpGfEntryMeta::whereIn('form_id', $form_ids)->whereHas('wpGfEntry', function ($q) use ($user_ids) {
            $q->whereIn('created_by', $user_ids)->where('status', 'Active');
        })->get();
        $results = [];
        foreach ($entries as $entry) {
            $k = explode('.', $entry->meta_key)[0];
            if (isset($field_target[$entry->form_id])) {
                if (isset($field_target[$entry->form_id]['id'])) {
                    if (in_array($k, $field_target[$entry->form_id]['id'])) {
                        $results [$entry->wpGfEntry->created_by][$entry->form_id]['title'] = $entry->wpGfEntry->wpGfForm->title;
                        $results [$entry->wpGfEntry->created_by][$entry->form_id]['data'][$k] = $entry->meta_value;
                    }
                } else {
                    unset($field_target[$entry->form_id]);
                }

            }
        }
        $this->results = $results;
        $this->field_target = $field_target;
        $this->form_ids = $form_ids;
        
    }
    public function getCleanHeaderFormat($title)
    {
        $clean_title = $title;

        // daftar kata yang ingin dibersihkan
        $blacklist = [
            'Rate your current ability to',
            '1-5 (5 highest)',
            '1-5 (5 highest).',
            'with yourself and others',
            'Rate your current level of',
            'Rate your ability to',
            'Rate the current status of',
            'Rate your current practices for maintaining',
        'Rate your',
        '(1-5-highest)',
        '(1-5-highest).',
        '(1-5-highest)',
        'Course - Topic : ',
        'Course - Topic'
        ];
        // hapus tanda petik satu dan dua
        $clean_title = str_replace(["'", '"','“','”',' .','.'], '', $clean_title);

        // hapus kata-kata blacklist
        $clean_title = str_ireplace($blacklist, '', $clean_title);

        // ganti banyak spasi jadi satu spasi
        $clean_title = preg_replace('/\s+/', ' ', $clean_title);
        // trim
        $clean_title = trim($clean_title);
        // ganti spasi dengan underscore

        return $clean_title;
    }

    public function exportCsv2()
    {
        $this->getMainData();
        $filename = ($this->title?$this->title."-":'') . time() . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

    
            $header2 = ['Name', 'Course title', 'Question', 'Answer'];
            fputcsv($handle, $header2);

            // Data Baris
            foreach ($this->userLists as $user) {
                $clean_course_title = '';
                $clean_question = '';

                foreach ($this->field_target as $form_id => $field) {
                    if (isset($field['title'])) {
                        if ($this->headerFormatPivot1 == 'Clean') {
                            $clean_course_title = $this->getCleanHeaderFormat($field['form_title']);
                        } else {
                            $clean_course_title = $field['form_title'];
                        }
                        
                        foreach ($field['title'] as $k => $f) {
                            if ($this->headerFormatPivot2 == 'Clean') {
                                $clean_question = $this->getCleanHeaderFormat($f);
                            } else {
                                $clean_question = $f;
                            }
                            $row = [];
                            $row = [$user->user_nicename];
                            $row[] = $clean_course_title;
                            $row[] = $clean_question;
                            $row[] = $this->results[$user->ID][$form_id]['data'][$k] ?? '-';
                            fputcsv($handle, $row);
                        }
                    }
                }

            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCsv()
    {
        $this->getMainData();
        $filename = ($this->title?$this->title."-":'') . time() . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // ===== BARIS 1: Form Titles (rowspan/colspan header) =====
            // $header1 = ['Name']; // Kolom pertama: Name
            // foreach ($this->field_target as $field) {
            //     if (isset($field['title'])) {
            //         $colspan = count($field['title']);
            //         // Tambahkan form_title, lalu isi colspan-1 dengan string kosong (agar sejajar dengan header2)
            //         $header1[] = $field['form_title'];
            //         for ($i = 1; $i < $colspan; $i++) {
            //             $header1[] = '';
            //         }
            //     }
            // }
            // fputcsv($handle, $header1);

            // ===== BARIS 2: Field Titles (judul per kolom) =====
            $header2 = ['Name'];
            foreach ($this->field_target as $field) {
                if (isset($field['title'])) {
                    foreach ($field['title'] as $title) {
                        $header2[] = $this->getHeaderFormat($field['form_title'], $title);
                    }
                }
            }
            fputcsv($handle, $header2);

            // Data Baris
            foreach ($this->userLists as $user) {
                $row = [$user->user_nicename];
                foreach ($this->field_target as $form_id => $field) {
                    if (isset($field['title'])) {
                        foreach ($field['title'] as $k => $f) {
                            $row[] = $this->results[$user->ID][$form_id]['data'][$k] ?? '-';
                        }
                    }
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        };
        $this->loading = false;

        return response()->stream($callback, 200, $headers);
    }



    public function render()
    {

        return view('livewire.export-result');
    }
}
