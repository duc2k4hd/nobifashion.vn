<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index()
    {
        $accountId = auth('web')->id();
        $sessionId = session()->getId();

        $favorites = Favorite::with('product')
            ->ofOwner($accountId, $sessionId)
            ->latest()
            ->get();

        return view('clients.pages.favorites.index', compact('favorites'));
    }

    public function toggle(Request $request, $productId)
    {
        $accountId = auth('web')->id();
        $sessionId = session()->getId();

        $product = Product::findOrFail($productId);

        $existing = Favorite::ofOwner($accountId, $sessionId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->respond($request, true, 'removed');
        }

        Favorite::create([
            'product_id' => $product->id,
            'account_id' => $accountId,
            'session_id' => $accountId ? null : $sessionId,
        ]);

        return $this->respond($request, true, 'added');
    }

    public function remove(Request $request, $productId)
    {
        $accountId = auth('web')->id();
        $sessionId = session()->getId();

        Favorite::ofOwner($accountId, $sessionId)
            ->where('product_id', $productId)
            ->delete();

        return $this->respond($request, true, 'removed');
    }

    private function respond(Request $request, bool $success, string $action)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => $success, 'action' => $action]);
        }
        return back();
    }
}


