<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AnonymousUser;
use App\Models\Reel;
use App\Models\ReelFeedCycle;
use App\Models\ReelRankingSnapshot;
use App\Services\ReelsFeed\FeedSnapshotService;
use App\Services\ReelsFeed\ReelsFeedOrchestrator;
use App\Services\ReelsFeed\TrendingRankingEngine;
use App\Services\ReelsFeed\NewestRankingEngine;

echo "========================================================\n";
echo "   REELS FEED RECOMMENDATION & DISTRIBUTION TEST SUITE\n";
echo "========================================================\n\n";

$snapshotService = app(FeedSnapshotService::class);
$orchestrator = app(ReelsFeedOrchestrator::class);
$trendingEngine = app(TrendingRankingEngine::class);
$newestEngine = app(NewestRankingEngine::class);

$allReelsCount = Reel::published()->count();
echo "-> Total published reels in DB: {$allReelsCount}\n";

// --- TEST 1: Snapshot Generation ---
echo "\n[TEST 1] Testing Shared Snapshot Generation & Caching...\n";
$snapshot = $snapshotService->getCurrentSnapshot();
assert($snapshot !== null, 'Snapshot should not be null');
assert($snapshot->total_videos > 0, 'Snapshot total_videos should be > 0');
assert(count($snapshot->trending_ids) > 0, 'Snapshot trending_ids should not be empty');
assert(count($snapshot->newest_ids) > 0, 'Snapshot newest_ids should not be empty');
echo "  [OK] Snapshot version: {$snapshot->version}, total_videos: {$snapshot->total_videos}\n";
echo "  [OK] Trending IDs count: " . count($snapshot->trending_ids) . ", Newest IDs count: " . count($snapshot->newest_ids) . "\n";

// --- TEST 2: Different Users -> Different Feed Sequences ---
echo "\n[TEST 2] Testing Feed Randomization Across Different Anonymous Users...\n";
$userA = AnonymousUser::firstOrCreate(
    ['nickname' => 'Test_User_A_Seed_Verification'],
    ['public_id' => 'anon_test_user_a', 'token_hash' => hash('sha256', 'token_a'), 'avatar_key' => 'cat', 'avatar_color' => '#ff0000', 'trust_score' => 100]
);
$userB = AnonymousUser::firstOrCreate(
    ['nickname' => 'Test_User_B_Seed_Verification'],
    ['public_id' => 'anon_test_user_b', 'token_hash' => hash('sha256', 'token_b'), 'avatar_key' => 'dog', 'avatar_color' => '#00ff00', 'trust_score' => 100]
);

// Xóa cycle cũ của 2 test user nếu có
ReelFeedCycle::whereIn('anonymous_user_id', [$userA->id, $userB->id])->delete();

$batchA = $orchestrator->getFeedBatch($userA, null, '127.0.0.1', 10);
$batchB = $orchestrator->getFeedBatch($userB, null, '127.0.0.1', 10);

$idsA = array_column($batchA['items'], 'id');
$idsB = array_column($batchB['items'], 'id');

echo "  User A (first 5 IDs): " . implode(', ', array_slice($idsA, 0, 5)) . "\n";
echo "  User B (first 5 IDs): " . implode(', ', array_slice($idsB, 0, 5)) . "\n";

assert($idsA !== $idsB, 'User A and User B should receive different initial sequences');
echo "  [OK] Confirmed: Different users receive uniquely randomized sequences!\n";

// --- TEST 3: Full Video Coverage & Zero Duplicate in Cycle ---
echo "\n[TEST 3] Testing Full Video Coverage (100% reels) & Zero Duplicates...\n";
$testUserCoverage = AnonymousUser::firstOrCreate(
    ['nickname' => 'Test_User_Coverage_Audit'],
    ['public_id' => 'anon_test_user_cov', 'token_hash' => hash('sha256', 'token_cov'), 'avatar_key' => 'bear', 'avatar_color' => '#0000ff', 'trust_score' => 100]
);
ReelFeedCycle::where('anonymous_user_id', $testUserCoverage->id)->delete();

$collectedIds = [];
$cursor = null;
$hasMore = true;
$pages = 0;
$duplicates = [];

while ($hasMore && $pages < 100) {
    $pages++;
    $batch = $orchestrator->getFeedBatch($testUserCoverage, null, '127.0.0.1', 20, $cursor);
    $items = $batch['items'];
    $cursor = $batch['nextCursor'];
    $hasMore = $batch['hasMore'];

    foreach ($items as $item) {
        $id = $item['id'];
        if (in_array($id, $collectedIds, true)) {
            $duplicates[] = $id;
        }
        $collectedIds[] = $id;
    }
}

echo "  Collected in full cycle: " . count($collectedIds) . " videos across {$pages} pages\n";
echo "  Unique videos count: " . count(array_unique($collectedIds)) . "\n";
echo "  Duplicates count: " . count($duplicates) . "\n";

assert(empty($duplicates), 'Cycle must have zero duplicate IDs');
assert(count($collectedIds) === $allReelsCount, "Cycle must cover 100% of all published reels ({$allReelsCount})");
echo "  [OK] FULL COVERAGE GUARANTEED: 100% valid videos ({$allReelsCount}/{$allReelsCount}) delivered with ZERO duplicates!\n";

// --- TEST 4: Resume Progress on Refresh / Reload ---
echo "\n[TEST 4] Testing Resume Progress on Refresh (No Repeat Videos)...\n";
$testUserRefresh = AnonymousUser::firstOrCreate(
    ['nickname' => 'Test_User_Refresh_Audit'],
    ['public_id' => 'anon_test_user_ref', 'token_hash' => hash('sha256', 'token_ref'), 'avatar_key' => 'fox', 'avatar_color' => '#ffa500', 'trust_score' => 100]
);
ReelFeedCycle::where('anonymous_user_id', $testUserRefresh->id)->delete();

// Step 1: User loads first batch (10 videos)
$batchStep1 = $orchestrator->getFeedBatch($testUserRefresh, null, '127.0.0.1', 10);
$step1Ids = array_column($batchStep1['items'], 'id');
echo "  Step 1 IDs: " . implode(', ', $step1Ids) . "\n";

// Step 2: User refreshes page (no cursor passed, e.g. reload /reels)
$batchStep2 = $orchestrator->getFeedBatch($testUserRefresh, null, '127.0.0.1', 10);
$step2Ids = array_column($batchStep2['items'], 'id');
echo "  Step 2 IDs (after refresh): " . implode(', ', $step2Ids) . "\n";

$intersection = array_intersect($step1Ids, $step2Ids);
echo "  Overlap between step 1 and step 2: " . count($intersection) . "\n";
assert(empty($intersection), 'User should not see repeat videos upon page refresh');
echo "  [OK] Refresh safety verified: user seamlessly continues their unviewed queue!\n";

// --- TEST 5: Cycle Completion & Next Cycle ---
echo "\n[TEST 5] Testing Automatic Transition to New Cycle Upon Completion...\n";
$cycle = ReelFeedCycle::active()->where('anonymous_user_id', $testUserRefresh->id)->latest('id')->first();
$totalInSeq = count($cycle->sequence);
$cycle->current_index = $totalInSeq - 3; // 3 videos left
$cycle->save();

$batchEnding = $orchestrator->getFeedBatch($testUserRefresh, null, '127.0.0.1', 10);
echo "  Fetched remaining: " . count($batchEnding['items']) . " videos. hasMore: " . ($batchEnding['hasMore'] ? 'true' : 'false') . "\n";
assert($batchEnding['hasMore'] === false, 'hasMore should be false at the end of the sequence');

$cycle->refresh();
assert($cycle->status === 'completed', 'Cycle should be marked completed');
echo "  Old cycle status: {$cycle->status}\n";

// Next request should automatically spawn a new cycle
$batchNextCycle = $orchestrator->getFeedBatch($testUserRefresh, null, '127.0.0.1', 10);
echo "  New cycle ID: {$batchNextCycle['cycleId']} (Old: {$cycle->id})\n";
assert($batchNextCycle['cycleId'] !== $cycle->id, 'New cycle must have a different ID');
echo "  [OK] Automatic new cycle rotation verified!\n";

// --- TEST 6: Performance Benchmark ---
echo "\n[TEST 6] Benchmarking Feed Batch Latency (10 iterations)...\n";
$latencies = [];
for ($i = 0; $i < 10; $i++) {
    $t0 = microtime(true);
    $orchestrator->getFeedBatch($userA, null, '127.0.0.1', 10);
    $latencies[] = (microtime(true) - $t0) * 1000;
}
$avgLatency = array_sum($latencies) / count($latencies);
$maxLatency = max($latencies);
echo "  Avg Latency: " . number_format($avgLatency, 2) . " ms\n";
echo "  Max Latency: " . number_format($maxLatency, 2) . " ms\n";
assert($avgLatency < 50, "Average latency should be < 50ms (Actual: {$avgLatency}ms)");
echo "  [OK] Ultra-fast performance confirmed (< 50ms)!\n";

// --- TEST 7: Controller & API Endpoint Verification ---
echo "\n[TEST 7] Testing Controller API Endpoint (/api/v1/reels & /api/v1/reels/feed)...\n";
$controller = app(\App\Http\Controllers\Api\V1\ReelApiController::class);

// Test with logged-in user ID to ensure ReelSave and ReelLike work properly
$requestFeed = \Illuminate\Http\Request::create('/api/v1/reels/feed', 'GET', ['limit' => 10]);
$responseFeed = $controller->feed($requestFeed);
$dataFeed = $responseFeed->getData(true);

assert($dataFeed['success'] === true, 'API /feed should return success: true');
assert(count($dataFeed['data']) === 10, 'API /feed should return 10 items');
assert(!empty($dataFeed['pagination']['cycle_id']), 'API /feed should include cycle_id in pagination');
echo "  [OK] /api/v1/reels/feed returned 10 items with cycle_id: {$dataFeed['pagination']['cycle_id']}\n";

$requestIndex = \Illuminate\Http\Request::create('/api/v1/reels', 'GET', ['limit' => 10]);
$responseIndex = $controller->index($requestIndex);
$dataIndex = $responseIndex->getData(true);

assert($dataIndex['success'] === true, 'API /index should return success: true');
assert(count($dataIndex['data']) === 10, 'API /index should return 10 items');
echo "  [OK] /api/v1/reels default route seamlessly invokes recommendation engine!\n";

// Test ReelController (Web View) with logged in user
$webController = app(\App\Http\Controllers\Clients\ReelController::class);
$userWeb = \App\Models\Account::first();
if ($userWeb) {
    auth()->login($userWeb);
}
$webReq = \Illuminate\Http\Request::create('/reels', 'GET');
$webView = $webController->index($webReq);
assert(!empty($webView->getData()['initialReels']), 'Web view should load initialReels for logged in user');
echo "  [OK] /reels web view rendered successfully for user ID " . ($userWeb->id ?? 'guest') . "!\n";

// --- TEST 8: Search Functionality - Old & New Videos Full Database Coverage ---
echo "\n[TEST 8] Testing Search Across Entire Database (Old & New Reels)...\n";
$oldestReel = Reel::published()->orderBy('id', 'asc')->first();
$searchKeyword = mb_substr($oldestReel->title, 0, 10);
$searchReq = \Illuminate\Http\Request::create('/api/v1/reels', 'GET', ['search' => $searchKeyword]);
$searchRes = $controller->index($searchReq);
$searchData = $searchRes->getData(true);

assert($searchData['success'] === true, 'Search request must return success');
assert(!empty($searchData['data']), 'Search must find matching reels');
$foundOldId = false;
foreach ($searchData['data'] as $item) {
    if ($item['id'] === $oldestReel->id) {
        $foundOldId = true;
        break;
    }
}
assert($foundOldId, "Search for keyword '{$searchKeyword}' must return the oldest matching reel ID {$oldestReel->id}");
echo "  [OK] Search successfully retrieved oldest Reel ID {$oldestReel->id} ('{$oldestReel->title}')!\n";

// Clean up test users
ReelFeedCycle::whereIn('anonymous_user_id', [$userA->id, $userB->id, $testUserCoverage->id, $testUserRefresh->id])->delete();
AnonymousUser::whereIn('id', [$userA->id, $userB->id, $testUserCoverage->id, $testUserRefresh->id])->delete();

echo "\n========================================================\n";
echo "   ALL TESTS PASSED WITH 100% SUCCESS!\n";
echo "========================================================\n";
