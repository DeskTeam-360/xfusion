<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpFutureState extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_arp_future_states';

    protected $fillable = [
        'arp_id',
        'narrative',
        'future_characteristics',
        'desired_culture',
        'desired_customer_experience',
        'desired_employee_experience',
        'desired_leadership_environment',
    ];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }

    /**
     * Wizard slug => DB column.
     *
     * @return array<string, string>
     */
    public static function uiToColumnMap(): array
    {
        return [
            'future_state_narrative' => 'narrative',
            'future_characteristics' => 'future_characteristics',
            'desired_culture' => 'desired_culture',
            'desired_customer_experience' => 'desired_customer_experience',
            'desired_employee_experience' => 'desired_employee_experience',
            'desired_leadership_environment' => 'desired_leadership_environment',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toWizardValues(): array
    {
        $out = [];
        foreach (self::uiToColumnMap() as $slug => $column) {
            $out[$slug] = (string) ($this->{$column} ?? '');
        }

        return $out;
    }
}
