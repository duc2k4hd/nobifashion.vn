<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Observers\ContactObserver;
use App\Observers\OrderObserver;
use App\Observers\VoucherObserver;
use App\Observers\PostObserver;
use App\Observers\ProductObserver;
use App\Observers\CategoryObserver;
use App\Observers\TagObserver;
use App\Policies\AccountPolicy;
use App\Policies\AddressPolicy;
use App\Policies\TagPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        
        // Register observers
        Contact::observe(ContactObserver::class);
        Order::observe(OrderObserver::class);
        Post::observe(PostObserver::class);
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        Tag::observe(TagObserver::class);
        Voucher::observe(VoucherObserver::class);
    }
}
