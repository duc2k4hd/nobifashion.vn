<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$vouchers = DB::table('vouchers')->where('image', 'like', '%/%')->get();
foreach ($vouchers as $v) {
    $filename = basename($v->image);
    DB::table('vouchers')->where('id', $v->id)->update(['image' => $filename]);
    echo "Fixed Voucher ID: {$v->id} to $filename\n";
}
