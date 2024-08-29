<?php

namespace Database\Seeders;

use App\Models\CourseScheduleGenerateTemplate;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);

        $topics = [
            'Get Real', 'Fill Buckets',
            'Be Intentional', 'Foster Grit',
            'Drive Growth', 'Self- Awareness (Get Real)',
            'Communication (Fill Buckets)', 'Systems and Social Connectedness (Be Intentional)',

            'Journaling (Get Real)', 'Culture (Drive Growth)',
            'Mattering (RaRa)', 'Goal Setting (Drive Growth)',
            'Combating Busyness (Be Intentional)', 'Creative Integration (Fill Buckets)',
            'Fear and Vulnerability (Foster Grit)', 'Authenticity (Get Real)',

            'Meditation/Reflection as a Practice (Get Real)', 'Relationship Building (Be Intentional)',
            'Heathy Relationships (Fill Buckets)', 'Behavior Change (Drive Growth)',
            'Reflections (feelings) (Get Real)', 'Comfort Zone (Foster Grit)',
            'Curiosity (Drive Growth)', 'Interacting with Empathy (Fill Buckets)',

            'Mind Body Connection (Be Intentional)', 'The Power of the Assist (Be Intentional)',
            'WIIFM (Foster Grit)', 'Growth Mindset (Drive Growth)',
            'Flexibility (Get Real)', 'Metrics and KPIs (Drive Growth)',
            'Transformative Mindset (Drive Growth)', 'Healthy Brain Function (Fill Buckets)',
        ];

        foreach ($topics  as $i=>$topic){
//        for ($i=0; $i<61;$i++){
            $url = $i+1;
            CourseScheduleGenerateTemplate::create([
                'header'=>'Milestone '.(intval($i/8)+1),
                'title'=>'Topic '.(($i%2)+1) .' '.$topic,
                'sub_title'=>'Topic '.(($i%2)+1). 'Week '.(intval($i/2)+1),
                'week'=>(intval($i/2)+1),
                'url'=>"https://teamsetup-2.deskteam360.com/revitalize/lms-page-$url/",
                'parent_url'=>'https://teamsetup-2.deskteam360.com/revitalize/lms-page-1/'
            ]);
        }
    }
}
