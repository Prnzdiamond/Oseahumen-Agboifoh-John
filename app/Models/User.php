<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_owner',
        'avatar',
        'headline',
        'bio',
        'tech_stack',
        'expertise',
        'urls',
        'professional_journey',
        'hobbies',
        'languages',
        'contact_info',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tech_stack' => 'array',
            'expertise' => 'array',
            'urls' => 'array',
            'professional_journey' => 'array',
            'hobbies' => 'array',
            'languages' => 'array',
            'contact_info' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_owner) {
            return true;
        }
        return false;
    }

    // Accessor to get tech stack with ratings for display
    protected function techStackList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->tech_stack)
                    return [];

                return array_map(function ($item) {
                    if (is_array($item)) {
                        return [
                            'technology' => $item['technology'] ?? $item['name'] ?? '',
                            'rating' => $item['rating'] ?? null,
                            'years_experience' => $item['years_experience'] ?? null,
                        ];
                    }
                    return [
                        'technology' => $item,
                        'rating' => null,
                        'years_experience' => null,
                    ];
                }, $this->tech_stack);
            }
        );
    }

    // Accessor to get expertise as simple array for display
    protected function expertiseList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->expertise) {
                    return [];
                }

                return collect($this->expertise)->map(function ($item) {
                    return is_array($item) ? $item['area'] ?? $item : $item;
                })->toArray();
            }
        );
    }

    // Accessor to get URLs with platform info
    protected function urlsList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->urls)
                    return [];

                return array_map(function ($item) {
                    if (is_array($item) && isset($item['platform'], $item['url'])) {
                        return [
                            'platform' => $item['platform'],
                            'url' => $item['url'],
                            'display' => ucfirst($item['platform']) . ': ' . $item['url']
                        ];
                    }
                    return ['platform' => 'other', 'url' => $item, 'display' => $item];
                }, $this->urls);
            }
        );
    }

    // Accessor for professional journey with timeline format
    protected function professionalJourneyList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->professional_journey) {
                    return [];
                }

                return collect($this->professional_journey)->map(function ($item) {
                    return [
                        'position' => $item['position'] ?? '',
                        'company' => $item['company'] ?? '',
                        'period' => $item['period'] ?? '',
                        'description' => $item['description'] ?? '',
                        'is_current' => $item['is_current'] ?? false,
                    ];
                })->sortByDesc('is_current')->values()->toArray();
            }
        );
    }

    // Accessor for hobbies
    protected function hobbiesList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->hobbies) {
                    return [];
                }

                return collect($this->hobbies)->map(function ($item) {
                    if (is_array($item)) {
                        return [
                            'name' => $item['name'] ?? $item['hobby'] ?? '',
                            'description' => $item['description'] ?? null,
                            'icon' => $item['icon'] ?? null,
                        ];
                    }
                    return [
                        'name' => $item,
                        'description' => null,
                        'icon' => null,
                    ];
                })->toArray();
            }
        );
    }

    // Accessor for languages with proficiency
    protected function languagesList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->languages) {
                    return [];
                }

                return collect($this->languages)->map(function ($item) {
                    if (is_array($item)) {
                        return [
                            'language' => $item['language'] ?? $item['name'] ?? '',
                            'proficiency' => $item['proficiency'] ?? 'conversational',
                            'is_native' => $item['is_native'] ?? false,
                        ];
                    }
                    return [
                        'language' => $item,
                        'proficiency' => 'conversational',
                        'is_native' => false,
                    ];
                })->toArray();
            }
        );
    }

    // Accessor for contact information
    protected function contactInfoList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->contact_info) {
                    return [];
                }

                return collect($this->contact_info)->map(function ($item) {
                    return [
                        'type' => $item['type'] ?? 'other',
                        'value' => $item['value'] ?? '',
                        'label' => $item['label'] ?? ucfirst($item['type'] ?? 'Contact'),
                        'is_primary' => $item['is_primary'] ?? false,
                        'is_public' => $item['is_public'] ?? true,
                    ];
                })->toArray();
            }
        );
    }

    // Helper method to get specific platform URL
    public function getUrlByPlatform(string $platform): ?string
    {
        if (!$this->urls)
            return null;

        foreach ($this->urls as $item) {
            if (is_array($item) && ($item['platform'] ?? '') === $platform) {
                return $item['url'] ?? null;
            }
        }
        return null;
    }

    // Helper method to get contact info by type
    public function getContactByType(string $type): ?string
    {
        if (!$this->contact_info) {
            return null;
        }

        $contact = collect($this->contact_info)->first(function ($item) use ($type) {
            return is_array($item) && ($item['type'] ?? '') === $type;
        });

        return $contact['value'] ?? null;
    }

    // Helper methods for common platforms
    public function getGithubUrl(): ?string
    {
        return $this->getUrlByPlatform('github');
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->getUrlByPlatform('linkedin');
    }

    public function getTwitterUrl(): ?string
    {
        return $this->getUrlByPlatform('twitter');
    }

    public function getInstagramUrl(): ?string
    {
        return $this->getUrlByPlatform('instagram');
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->getUrlByPlatform('website');
    }

    // Helper methods for contact info
    public function getEmail(): ?string
    {
        return $this->getContactByType('email') ?? $this->email;
    }

    public function getWhatsapp(): ?string
    {
        return $this->getContactByType('whatsapp');
    }

    public function getPhone(): ?string
    {
        return $this->getContactByType('phone');
    }

    // Additional helper methods for popular social platforms
    public function getFacebookUrl(): ?string
    {
        return $this->getUrlByPlatform('facebook');
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->getUrlByPlatform('youtube');
    }

    public function getTiktokUrl(): ?string
    {
        return $this->getUrlByPlatform('tiktok');
    }

    public function getSnapchatUrl(): ?string
    {
        return $this->getUrlByPlatform('snapchat');
    }

    public function getPinterestUrl(): ?string
    {
        return $this->getUrlByPlatform('pinterest');
    }

    public function getRedditUrl(): ?string
    {
        return $this->getUrlByPlatform('reddit');
    }

    public function getMediumUrl(): ?string
    {
        return $this->getUrlByPlatform('medium');
    }

    public function getBehanceUrl(): ?string
    {
        return $this->getUrlByPlatform('behance');
    }

    public function getDribbbleUrl(): ?string
    {
        return $this->getUrlByPlatform('dribbble');
    }

    public function getDiscordUrl(): ?string
    {
        return $this->getUrlByPlatform('discord');
    }

    public function getTelegramUrl(): ?string
    {
        return $this->getUrlByPlatform('telegram');
    }

    public function getSkypeUrl(): ?string
    {
        return $this->getUrlByPlatform('skype');
    }

    // Additional contact methods
    public function getTelegram(): ?string
    {
        return $this->getContactByType('telegram');
    }

    public function getSkype(): ?string
    {
        return $this->getContactByType('skype');
    }

    public function getDiscord(): ?string
    {
        return $this->getContactByType('discord');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($user) {
            if ($user->isDirty('avatar') && $user->getOriginal('avatar')) {
                // If avatar is being updated, delete the old one
                $oldAvatar = $user->getOriginal('avatar');
                if ($oldAvatar) {
                    \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($oldAvatar);
                }
            }
        });

        static::deleting(function ($user) {
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($user->avatar);
            }
        });

        static::saved(function ($user) {
            if ($user->is_owner) {
                \Illuminate\Support\Facades\Cache::forget('owner.data');
                \Illuminate\Support\Facades\Cache::forget('owner.fallback');
            }
        });
    }
}
