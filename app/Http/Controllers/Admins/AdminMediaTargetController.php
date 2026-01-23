<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Http\Request;

class AdminMediaTargetController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,post,category,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar',
            'q' => 'nullable|string|max:255',
        ]);

        $query = trim($validated['q'] ?? '');
        $type = $validated['type'];

        $results = match ($type) {
            'product' => $this->searchProducts($query),
            'post' => $this->searchPosts($query),
            'category' => $this->searchCategories($query),
            'banner_desktop', 'banner_mobile' => $this->searchBanners($query),
            'profile_avatar', 'profile_sub_avatar' => $this->searchProfiles($query),
            default => collect(),
        };

        return response()->json([
            'data' => $results->take(20)->values(),
        ]);
    }

    protected function searchProducts(string $term)
    {
        $builder = Product::query()->select('id', 'name', 'sku')->latest('id');
        if ($term !== '') {
            $builder->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        return $builder->limit(25)->get()->map(function (Product $product) {
            return [
                'id' => $product->id,
                'label' => $product->name,
                'description' => $product->sku ? "SKU: {$product->sku}" : null,
            ];
        });
    }

    protected function searchPosts(string $term)
    {
        $builder = Post::query()->select('id', 'title', 'slug')->latest('id');
        if ($term !== '') {
            $builder->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        return $builder->limit(25)->get()->map(function (Post $post) {
            return [
                'id' => $post->id,
                'label' => $post->title,
                'description' => $post->slug,
            ];
        });
    }

    protected function searchCategories(string $term)
    {
        $builder = Category::query()->select('id', 'name', 'slug')->latest('id');
        if ($term !== '') {
            $builder->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        return $builder->limit(25)->get()->map(function (Category $category) {
            return [
                'id' => $category->id,
                'label' => $category->name,
                'description' => $category->slug,
            ];
        });
    }

    protected function searchBanners(string $term)
    {
        $builder = Banner::query()->select('id', 'title', 'position')->latest('id');
        if ($term !== '') {
            $builder->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return $builder->limit(25)->get()->map(function (Banner $banner) {
            return [
                'id' => $banner->id,
                'label' => $banner->title,
                'description' => $banner->position ? "Vị trí: {$banner->position}" : null,
            ];
        });
    }

    protected function searchProfiles(string $term)
    {
        $builder = Profile::query()->select('id', 'full_name', 'nickname')->latest('id');
        if ($term !== '') {
            $builder->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('nickname', 'like', "%{$term}%");
            });
        }

        return $builder->limit(25)->get()->map(function (Profile $profile) {
            return [
                'id' => $profile->id,
                'label' => $profile->full_name ?? "Profile #{$profile->id}",
                'description' => $profile->nickname,
            ];
        });
    }
}


