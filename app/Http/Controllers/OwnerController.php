<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Env;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\OwnerResource;
use Illuminate\Support\Facades\Cache;

class OwnerController extends Controller
{
    // app/Http/Controllers/Api/OwnerController.php
    public function show()
    {
        // Cache owner data for 2 hours
        $owner = Cache::rememberForever('owner.data', function () {
            return User::select([
                'id',
                'name',
                'email',
                'headline',
                'bio',
                'avatar',
                'tech_stack',
                'expertise',
                'urls',
                'professional_journey',
                'hobbies',
                'languages',
                'contact_info',
                'is_owner'
            ])
                ->where('is_owner', true)
                ->first();
        });

        if (!$owner) {
            // Return cached fallback data
            return Cache::rememberForever('owner.fallback', function () {
                return response()->json([
                    "message" => "Owner not found",
                    "data" => [
                        'name' => 'Oseahumen Agboifoh John',
                        'headline' => 'Software Engineer',
                        'bio' => 'Experienced software engineer with a passion for building scalable web applications and a strong background in full-stack development.',
                        'avatar' => 'https://example.com/default-avatar.png',
                        'tech_stack' => [
                            ['technology' => 'PHP', 'rating' => 5, 'years_experience' => 3],
                            ['technology' => 'Laravel', 'rating' => 5, 'years_experience' => 3],
                            ['technology' => 'JavaScript', 'rating' => 4, 'years_experience' => 2],
                            ['technology' => 'Vue.js', 'rating' => 4, 'years_experience' => 2],
                        ],
                        'expertise' => [
                            'Web Development',
                            'API Development',
                            'Database Design',
                            'Cloud Computing',
                        ],
                        'social_links' => [
                            'github' => 'https://github.com/prnzdiamond',
                            'linkedin' => 'https://linkedin.com/in/oseahumen',
                        ],
                        'contact_methods' => [
                            'email' => 'oseahumenagboifo@gmail.com',
                            'whatsapp' => '2347043530060',
                        ],
                    ],
                    "success" => true
                ]);
            });
        }

        $data = Cache::rememberForever("owner-data.transformed", function () use ($owner) {
            return new OwnerResource($owner);
        });

        return response()->json([
            "data" => $data,
            "message" => "Owner details retrieved successfully",
            "success" => true
        ]);
    }

}
