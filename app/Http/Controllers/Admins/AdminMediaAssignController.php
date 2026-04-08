<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Profile;
use App\Services\Media\FileHelperService;
use App\Services\Media\MediaAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminMediaAssignController extends Controller
{
    protected array $targets = [
        'product',
        'post',
        'category',
        'banner_desktop',
        'banner_mobile',
        'profile_avatar',
        'profile_sub_avatar',
    ];

    public function __invoke(
        Request $request,
        MediaAssignmentService $assignment,
        FileHelperService $files
    ) {
        $validated = $request->validate([
            'source' => 'required|in:product_image,post_thumbnail,category_image,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar,library_image,filesystem_file',
            'media_id' => 'required',
            'target_type' => 'required|in:' . implode(',', $this->targets),
            'target_id' => 'required|integer|min:1',
        ]);

        if ($validated['source'] === 'library_image') {
            $result = $assignment->attachLibraryImage(
                (int) $validated['media_id'],
                $validated['target_type'],
                (int) $validated['target_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã gán ảnh vào đối tượng.',
                'item' => $result,
            ]);
        }

        $paths = $this->resolveSourcePaths(
            $validated['source'],
            $validated['media_id'],
            $files
        );

        $meta = [
            'title' => $paths['title'] ?? null,
            'alt' => $paths['alt'] ?? null,
        ];

        $result = $assignment->assignExisting(
            $validated['target_type'],
            (int) $validated['target_id'],
            $paths,
            $meta
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã gán ảnh vào đối tượng.',
            'item' => $result,
        ]);
    }

    protected function resolveSourcePaths(string $source, string $id, FileHelperService $files): array
    {
        return match ($source) {
            'product_image' => $this->mapImage(Image::findOrFail((int) $id)),
            'post_thumbnail' => $this->mapPost(Post::findOrFail((int) $id)),
            'category_image' => $this->mapCategory(Category::findOrFail((int) $id)),
            'banner_desktop' => $this->mapBanner(Banner::findOrFail((int) $id), 'desktop'),
            'banner_mobile' => $this->mapBanner(Banner::findOrFail((int) $id), 'mobile'),
            'profile_avatar' => $this->mapProfile(Profile::findOrFail((int) $id), 'avatar'),
            'profile_sub_avatar' => $this->mapProfile(Profile::findOrFail((int) $id), 'sub_avatar'),
            'library_image' => $this->mapImage(Image::findOrFail((int) $id)),
            'filesystem_file' => $this->mapFilesystemFile($id, $files),
            default => throw new \InvalidArgumentException('Nguồn media không hợp lệ.'),
        };
    }

    protected function mapImage(Image $image): array
    {
        $folderKey = match ($image->entity_type ?: ($image->product_id ? 'product' : $image->context)) {
            'product' => 'clothes',
            'post' => 'posts',
            'category' => 'categories',
            'banner' => 'banners',
            'profile' => 'accounts_avatars',
            default => null,
        };

        return [
            'original' => $this->expandMediaPath($image->path ?: $image->url, $folderKey),
            'thumbnail' => $this->expandMediaPath($image->thumbnail_url, $folderKey),
            'medium' => $this->expandMediaPath($image->medium_url, $folderKey),
            'title' => $image->title,
            'alt' => $image->alt,
        ];
    }

    protected function mapPost(Post $post): array
    {
        return [
            'original' => $this->expandMediaPath($post->thumbnail, 'posts'),
            'title' => $post->title,
            'alt' => $post->thumbnail_alt_text,
        ];
    }

    protected function mapCategory(Category $category): array
    {
        return [
            'original' => $this->expandMediaPath($category->image, 'categories'),
            'title' => $category->name,
        ];
    }

    protected function mapBanner(Banner $banner, string $mode): array
    {
        return [
            'original' => $this->expandMediaPath($mode === 'desktop' ? $banner->image_desktop : $banner->image_mobile, 'banners'),
            'title' => $banner->title,
        ];
    }

    protected function mapProfile(Profile $profile, string $column): array
    {
        return [
            'original' => $this->expandMediaPath($profile->{$column}, 'accounts_avatars'),
            'title' => $profile->full_name,
        ];
    }

    protected function mapFilesystemFile(string $path, FileHelperService $files): array
    {
        $normalized = $files->normalizeRelativePath($path);
        if (
            !$normalized
            || !$files->isManagedMediaPath($normalized, config('media.directories', []))
            || !$files->fileExists($normalized)
        ) {
            throw new \InvalidArgumentException('Không tìm thấy file media để gán.');
        }

        return [
            'original' => $normalized,
            'title' => Str::headline(pathinfo($normalized, PATHINFO_FILENAME)),
        ];
    }

    protected function expandMediaPath(?string $path, ?string $folderKey = null): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized === '' || Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized ?: null;
        }

        $normalized = ltrim($normalized, '/');
        $directories = config('media.directories', []);

        foreach ($directories as $directory) {
            $directory = trim(str_replace('\\', '/', $directory), '/');
            if ($normalized === $directory || Str::startsWith($normalized, $directory . '/')) {
                return $normalized;
            }
        }

        if ($folderKey && isset($directories[$folderKey])) {
            return trim($directories[$folderKey], '/') . '/' . basename($normalized);
        }

        return $normalized;
    }
}
