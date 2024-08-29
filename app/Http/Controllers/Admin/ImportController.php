<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\User;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use MikeMcLin\WpPassword\Facades\WpPassword;
use App\Livewire\Table\Master;

class ImportController extends Controller
{
    public $userMeta;

    public function importIndex()
    {
        return view(
            'admin.user.import-user'
        );
    }

    public function importCSV(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $file = fopen($request->file('file'), 'r');

        $isFirstRow = true;
        $data = [];
        while (($row = fgetcsv($file, 1000, ',')) !== FALSE) {
            if ($isFirstRow) {
                $isFirstRow = false;
                continue;
            }

            $user = User::create([
                'user_login' => $row[0],
                'user_pass' => WpPassword::make($row[1]),
                'user_nicename' => $row[2],
                'user_email' => $row[3],
                'user_url' => $row[4] ?? 'http://' . $row[4],
                'user_registered' => Carbon::now()->toDateTimeString(),
                'user_activation_key' => '',
                'user_status' => 0,
                'display_name' => $row[0].' '.$row[5],
            ]);

            $this->userMeta['nickname'] = $row[0];
            $this->userMeta['first_name'] = $row[0];
            $this->userMeta['last_name'] = $row[5];
            $this->userMeta['description'] = '';
            $this->userMeta['rich_editing'] = true;
            $this->userMeta['syntax_highlighting'] = true;
            $this->userMeta['comment_shortcuts'] = false;
            $this->userMeta['admin_color'] = 'fresh';
            $this->userMeta['use_ssl'] = 0;
            $this->userMeta['show_admin_bar_front'] = true;
            $this->userMeta['locale'] = '';
            $this->userMeta['wp_capabilities'] = serialize([$row[7] => true]);
            $this->userMeta['wp_user_level'] = 0;
            $this->userMeta['dismissed_wp_pointers'] = '';
            if ($row[6] != null) {
                $this->userMeta['company'] = $row[6];
                CompanyEmployee::create([
                    'user_id' => $user->ID,
                    'company_id' => $row[6]
                ]);
            }

            foreach ($this->userMeta as $key => $meta) {
                WpUserMeta::create([
                    'meta_key' => $key,
                    'user_id' => $user->ID,
                    'meta_value' => $meta
                ]);
            }
        }

        fclose($file);

        return redirect(route('user.index'))->with('swal', [
            'type' => 'success',
            'title' => 'Data successfully imported',
            'timeout' => 3000,
            'icon' => 'success'
        ]);
    }
}
