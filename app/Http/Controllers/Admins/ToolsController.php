<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Post;

class ToolsController extends Controller
{
    public function index()
    {
        return view('admins.tools.index');
    }

    public function exportPostImages()
    {
        $urls = [];
        \App\Models\Post::withTrashed()->select('id', 'thumbnail', 'content')->chunk(1000, function ($posts) use (&$urls) {
            foreach ($posts as $post) {
                if (!empty($post->thumbnail)) {
                    $urls[] = "[Post ID: {$post->id}] Thumbnail: " . $post->thumbnail;
                }
                if (!empty($post->content)) {
                    preg_match_all('/src="([^"]+\.(?:jpg|jpeg|png|gif|webp|svg))"/i', $post->content, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $url) {
                            $urls[] = "[Post ID: {$post->id}] Content: " . $url;
                        }
                    }
                }
            }
        });

        $content = implode(PHP_EOL, array_unique($urls));
        $filename = 'danh_sach_link_anh_trong_posts_' . date('Y_m_d_His') . '.txt';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
