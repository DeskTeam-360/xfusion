<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\LogActivity;
use App\Models\User;
use App\Models\WpUserMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Livewire\WithFileUploads;
use MikeMcLin\WpPassword\Facades\WpPassword;
use App\Livewire\Table\Master;

class ImportController extends Controller
{

    public $userMeta;
    private $username;
    private $first_name;
    private $last_name;
    private $password;
    private $rePassword;
    private $email;
    private $website;
    private $role;

    public $fileCsv;

    public function importIndex()
    {
//        dd(User::where('user_email', 'devy@email.com')->first()['ID']);
        return view(
            'admin.user.import-user'
        );
    }

    private function mount($id) {
        $data = \App\Models\User::find($id);
        $this->username = $data->user_login;
        $this->first_name = $data->user_nicename;
        $this->last_name = $data->last_name;
        $this->password = $data->password;
        $this->rePassword = $data->password;
        $this->email = $data->user_email;
        $this->website = $data->user_url;
        $roles = $data->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $this->role = '';
        foreach ($roles as $r) {
            $this->role = array_key_first(unserialize($r['meta_value']));
        }
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

            if ($row[3]) {
                try {
                    $data_id = User::where('user_email', $row[3])->first()['ID'];
//                    dd($data_id);
                } catch (\Exception) {
                    $data_id = NULL;
                }

                if ($data_id) {
                    $this->mount($data_id);

//                    dd($row[2]);
                    $user = \App\Models\User::find($data_id)->update([
                        'user_nicename' => $row[2] ?? $this->username,
                        'user_email' => $this->email,
                        'user_url' => ($row[4] ?? 'http://' . $row[5]) ?? ($this->website ?? 'http://' . $this->first_name),
                        'user_registered' => Carbon::now()->toDateTimeString(),
                        'user_status' => 0,
                        'display_name' => ($row[5].' '.$row[6]) ?? ($this->first_name . ' ' . $this->last_name),
                    ]);

                    $fn = WpUserMeta::where('user_id', $data_id)->where('meta_key', 'first_name')->first();
                    $ln = WpUserMeta::where('user_id', $data_id)->where('meta_key', 'last_name')->first();
//                    $keapId = WpUserMeta::where('user_id', $this->dataId)->where('meta_key', 'keap_contact_id')->first();

                    if ($ln != null) {
                        $ln->update([
                            'meta_value' => $row[6] ?? $this->last_name
                        ]);
                    } else {
                        WpUserMeta::create([
                            'user_id' => $data_id,
                            'meta_key' => 'last_name',
                            'meta_value' => $row[6]
                        ]);
                    }
                    if ($fn != null) {
                        $fn->update([
                            'meta_value' => $row[5] ?? $this->first_name
                        ]);
                    } else {
                        WpUserMeta::create([
                            'user_id' => $data_id,
                            'meta_key' => 'first_name',
                            'meta_value' => $row[6]
                        ]);
                    }
                    $client = Http::post('https://hooks.zapier.com/hooks/catch/941497/2hr769d/', [
                        'first_name' => $row[5] ?? $this->first_name,
                        'last_name' => $row[6] ?? $this->last_name,
                        'email' => $this->email,
                        'website' => ($row[4] ?? 'http://' . $row[5]) ?? $this->website,
                    ]);
                } else {
                    $user = User::create([
                        'user_login' => $row[0] ?? $row[3],
                        'user_pass' => WpPassword::make($row[1]),
                        'user_nicename' => $row[2],
                        'user_email' => $row[3],
                        'user_url' => $row[4] ?? 'http://' . $row[5],
                        'user_registered' => Carbon::now()->toDateTimeString(),
                        'user_activation_key' => '',
                        'user_status' => 0,
                        'display_name' => $row[5].' '.$row[6],
                    ]);

                    $client = Http::post('https://hooks.zapier.com/hooks/catch/941497/2hr769d/', [
                        'first_name' => $row[5],
                        'last_name' => $row[6],
                        'email' => $row[3],
                        'website' => $row[4] ?? 'http://' . $row[4],
                    ]);

                    $this->userMeta['nickname'] = $row[2];
                    $this->userMeta['first_name'] = $row[5];
                    $this->userMeta['last_name'] = $row[6];
                    $this->userMeta['description'] = '';
                    $this->userMeta['rich_editing'] = true;
                    $this->userMeta['syntax_highlighting'] = true;
                    $this->userMeta['comment_shortcuts'] = false;
                    $this->userMeta['admin_color'] = 'fresh';
                    $this->userMeta['use_ssl'] = 0;
                    $this->userMeta['show_admin_bar_front'] = true;
                    $this->userMeta['locale'] = '';
                    $this->userMeta['wp_capabilities'] = serialize([$row[8] => true]);
                    $this->userMeta['wp_user_level'] = 0;
                    $this->userMeta['dismissed_wp_pointers'] = '';
                    if ($row[7] != null) {
                        $this->userMeta['company'] = $row[7];
                        CompanyEmployee::create([
                            'user_id' => $user->ID,
                            'company_id' => $row[7]
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
            } else {
                $name = $row[0] ?? $row[2] ?? '';
                LogActivity::create([
                    'log' => 'user '.$name.' must have email value when importing user!'
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
