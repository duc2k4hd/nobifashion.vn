<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Profile;
use App\Services\Media\MediaAssignmentService;
use Illuminate\Http\Request;

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

    public function __invoke(Request $request, MediaAssignmentService $assignment)
    {
        $validated = $request->validate([
            'source' => 'required|in:product_image,post_thumbnail,category_image,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar',
            'media_id' => 'required',
            'target_type' => 'required|in:' . implode(',', $this->targets),
            'target_id' => 'required|integer|min:1',
        ]);

        $paths = $this->resolveSourcePaths($validated['source'], $validated['media_id']);
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

    protected function resolveSourcePaths(string $source, string $id): array
    {
        return match ($source) {
            'product_image' => $this->mapImage(Image::findOrFail((int) $id)),
            'post_thumbnail' => $this->mapPost(Post::findOrFail((int) $id)),
            'category_image' => $this->mapCategory(Category::findOrFail((int) $id)),
            'banner_desktop' => $this->mapBanner(Banner::findOrFail((int) $id), 'desktop'),
            'banner_mobile' => $this->mapBanner(Banner::findOrFail((int) $id), 'mobile'),
            'profile_avatar' => $this->mapProfile(Profile::findOrFail((int) $id), 'avatar'),
            'profile_sub_avatar' => $this->mapProfile(Profile::findOrFail((int) $id), 'sub_avatar'),
            default => throw new \InvalidArgumentException('Nguồn media không hợp lệ.'),
        };
    }

    protected function mapImage(Image $image): array
    {
        return [
            'original' => $image->url,
            'thumbnail' => $image->thumbnail_url,
            'medium' => $image->medium_url,
            'title' => $image->title,
            'alt' => $image->alt,
        ];
    }

    protected function mapPost(Post $post): array
    {
        return [
            'original' => $post->thumbnail,
            'title' => $post->title,
            'alt' => $post->thumbnail_alt_text,
        ];
    }

    protected function mapCategory(Category $category): array
    {
        return [
            'original' => $category->image,
            'title' => $category->name,
        ];
    }

    protected function mapBanner(Banner $banner, string $mode): array
    {
        return [
            'original' => $mode === 'desktop' ? $banner->image_desktop : $banner->image_mobile,
            'title' => $banner->title,
        ];
    }

    protected function mapProfile(Profile $profile, string $column): array
    {
        return [
            'original' => $profile->{$column},
            'title' => $profile->full_name,
        ];
    }
}


