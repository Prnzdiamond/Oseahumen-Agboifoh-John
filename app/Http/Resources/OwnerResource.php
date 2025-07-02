<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'avatar' => $this->avatar ? Storage::disk('cloudinary')->url($this->avatar) : null,
            'tech_stack' => $this->tech_stack_list ?? [],
            'expertise' => $this->expertise_list ?? [],
            'links' => $this->urls_list ?? [],
            'professional_journey' => $this->professional_journey_list ?? [],
            'hobbies' => $this->hobbies_list ?? [],
            'languages' => $this->languages_list ?? [],
            'contact_info' => $this->contact_info_list ?? [],

            // Helper methods for quick access
            'social_links' => [
                'github' => $this->getGithubUrl(),
                'linkedin' => $this->getLinkedinUrl(),
                'twitter' => $this->getTwitterUrl(),
                'instagram' => $this->getInstagramUrl(),
                'facebook' => $this->getFacebookUrl(),
                'youtube' => $this->getYoutubeUrl(),
                'tiktok' => $this->getTiktokUrl(),
                'snapchat' => $this->getSnapchatUrl(),
                'pinterest' => $this->getPinterestUrl(),
                'reddit' => $this->getRedditUrl(),
                'medium' => $this->getMediumUrl(),
                'behance' => $this->getBehanceUrl(),
                'dribbble' => $this->getDribbbleUrl(),
                'discord' => $this->getDiscordUrl(),
                'telegram' => $this->getTelegramUrl(),
                'skype' => $this->getSkypeUrl(),
                'website' => $this->getWebsiteUrl(),
            ],
            'contact_methods' => [
                'email' => $this->getEmail(),
                'whatsapp' => $this->getWhatsapp(),
                'phone' => $this->getPhone(),
                'telegram' => $this->getTelegram(),
                'skype' => $this->getSkype(),
                'discord' => $this->getDiscord(),
            ],
        ];
    }
}
