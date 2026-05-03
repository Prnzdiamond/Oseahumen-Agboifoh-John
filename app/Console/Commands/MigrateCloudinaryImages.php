<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateCloudinaryImages extends Command
{
    protected $signature = 'images:migrate-from-cloudinary';
    protected $description = 'Download all Cloudinary images and store them in local public storage';

    private int $success = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('Starting Cloudinary → Local migration...');
        $this->newLine();

        // ── Projects ──────────────────────────────────────────────────────────
        $projects = Project::with('images')->get();
        $this->info("Found {$projects->count()} projects + their gallery images.");
        $this->newLine();

        foreach ($projects as $project) {
            $this->line("📁 <fg=cyan>{$project->title}</>");

            $this->migrateField($project, 'cover_image', 'projects/covers');
            $this->migrateField($project, 'image', 'projects/images');

            foreach ($project->images as $projectImage) {
                $this->migrateGalleryImage($projectImage);
            }
        }

        // ── Owner avatar ──────────────────────────────────────────────────────
        $this->newLine();
        $this->info('Checking owner avatar...');

        $owner = User::where('is_owner', true)->first();

        if ($owner && $owner->avatar) {
            if ($this->isAlreadyLocal($owner->avatar)) {
                $this->line('  <fg=yellow>↷ avatar already local, skipping</>');
                $this->skipped++;
            } else {
                $newPath = $this->downloadFromCloudinary($owner->avatar, 'avatars');
                if ($newPath) {
                    $owner->update(['avatar' => $newPath]);
                    $this->line('  <fg=green>✓ avatar migrated</>');
                    $this->success++;
                } else {
                    $this->line('  <fg=red>✗ avatar FAILED</>');
                    $this->failed++;
                }
            }
        } else {
            $this->line('  ↷ no avatar set, skipping');
        }

        // ── Summary ───────────────────────────────────────────────────────────
        $this->newLine();
        $this->info("✅ Done. Success: {$this->success} | Failed: {$this->failed} | Skipped (already local): {$this->skipped}");

        if ($this->failed > 0) {
            $this->warn('Re-run this command to retry failed images (safe to run multiple times).');
        }

        return Command::SUCCESS;
    }

    /**
     * Migrate a single field (cover_image or image) on a Project model.
     */
    private function migrateField(Project $project, string $field, string $directory): void
    {
        $value = $project->$field;

        if (!$value) {
            return;
        }

        if ($this->isAlreadyLocal($value)) {
            $this->line("  <fg=yellow>↷ {$field} already local, skipping</>");
            $this->skipped++;
            return;
        }

        $newPath = $this->downloadFromCloudinary($value, $directory);

        if ($newPath) {
            // withoutEvents prevents boot() observer from deleting the old
            // Cloudinary file during migration — we keep it as backup
            Project::withoutEvents(function () use ($project, $field, $newPath) {
                $project->update([$field => $newPath]);
            });
            $this->line("  <fg=green>✓ {$field} migrated</>");
            $this->success++;
        } else {
            $this->line("  <fg=red>✗ {$field} FAILED — will need to re-run</>");
            $this->failed++;
        }
    }

    /**
     * Migrate a ProjectImage gallery image.
     */
    private function migrateGalleryImage(ProjectImage $projectImage): void
    {
        $value = $projectImage->image;

        if (!$value) {
            return;
        }

        if ($this->isAlreadyLocal($value)) {
            $this->line('  <fg=yellow>↷ gallery image already local, skipping</>');
            $this->skipped++;
            return;
        }

        $newPath = $this->downloadFromCloudinary($value, 'projects/gallery');

        if ($newPath) {
            ProjectImage::withoutEvents(function () use ($projectImage, $newPath) {
                $projectImage->update(['image' => $newPath]);
            });
            $this->line('  <fg=green>✓ gallery image migrated</>');
            $this->success++;
        } else {
            $this->line('  <fg=red>✗ gallery image FAILED</>');
            $this->failed++;
        }
    }

    /**
     * Download a file from Cloudinary by its public ID and save locally.
     * Returns the local relative path on success, null on failure.
     */
    private function downloadFromCloudinary(string $publicId, string $directory): ?string
    {
        try {
            $url = Storage::disk('cloudinary')->url($publicId);

            $context = stream_context_create([
                'http' => ['timeout' => 30, 'ignore_errors' => true],
            ]);
            $contents = file_get_contents($url, false, $context);

            if ($contents === false || empty($contents)) {
                $this->error("    Could not download: {$url}");
                return null;
            }

            $urlPath = parse_url($url, PHP_URL_PATH);
            $ext = pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'jpg';
            $ext = strtolower(explode('?', $ext)[0]);

            $filename = Str::uuid() . '.' . $ext;
            $localPath = $directory . '/' . $filename;

            Storage::disk('public')->put($localPath, $contents);

            return $localPath;
        } catch (\Throwable $e) {
            $this->error('    Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect if a stored value is already a local path.
     *
     * Local paths match the directories Filament uploads to:
     *   projects/covers/     ← cover_image
     *   projects/images/     ← main image
     *   projects/gallery/    ← gallery images (ImagesRelationManager)
     *   avatars/             ← owner avatar
     *
     * Cloudinary public IDs look like: "portfolio/abc123" or "db86imnv0/image/upload/..."
     */
    private function isAlreadyLocal(string $value): bool
    {
        if (str_starts_with($value, 'http')) {
            return false;
        }

        return str_starts_with($value, 'projects/covers/')
            || str_starts_with($value, 'projects/images/')
            || str_starts_with($value, 'projects/gallery/')
            || str_starts_with($value, 'avatars/');
    }
}
