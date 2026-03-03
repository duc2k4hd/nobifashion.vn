<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Post;
use Illuminate\Support\Facades\DB;

echo "--- DATABASE CHECK ---\n";
$totalPosts = DB::table('posts')->count();
echo "Total posts: $totalPosts\n";

$accounts = DB::table('accounts')->get(['id', 'email', 'name']);
echo "Total accounts: " . count($accounts) . "\n";
foreach ($accounts as $acc) {
    echo "ID: {$acc->id} | Email: {$acc->email} | Name: {$acc->name}\n";
}

$distinctCreatedBy = DB::table('posts')->whereNotNull('created_by')->distinct()->pluck('created_by')->toArray();
echo "\nDistinct created_by values: " . implode(', ', $distinctCreatedBy) . "\n";

if (!empty($distinctCreatedBy)) {
    foreach ($distinctCreatedBy as $id) {
        $account = DB::table('accounts')->find($id);
        if ($account) {
            echo "Account ID $id: Name='{$account->name}', Email='{$account->email}'\n";
        } else {
            echo "Account ID $id: NOT FOUND in accounts table!\n";
        }
    }
}

echo "\n--- EAGER LOADING CHECK ---\n";
$posts = Post::with('author.profile')->take(5)->get();
foreach ($posts as $p) {
    echo "Post {$p->id}: Title='{$p->title}', created_by='{$p->created_by}', author_exists=" . ($p->author ? 'YES' : 'NO') . "\n";
    if ($p->author) {
        echo "  - DisplayName: " . $p->author->displayName() . "\n";
    }
}
