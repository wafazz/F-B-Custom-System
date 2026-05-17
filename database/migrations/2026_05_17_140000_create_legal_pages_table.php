<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('last_updated_label', 80)->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('legal_pages')->insert([
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'body' => '<h2>1. Acceptance of Terms</h2><p>By placing an order through the Star Coffee app, you agree to these terms.</p><h2>2. Orders</h2><p>All orders are subject to availability at the selected branch. Stock and pricing may change in real time.</p><h2>3. Payment</h2><p>Online orders are processed via our payment partner. Walk-in orders may be paid in cash, card, or DuitNow at the counter.</p><h2>4. Loyalty &amp; Vouchers</h2><p>1 point per RM 1 spent (subtotal). 100 points = RM 1 redeem. Vouchers may carry per-user limits, branch scope, expiry dates and minimum spend.</p>',
                'last_updated_label' => $now->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'body' => '<p><em>Compliant with PDPA Malaysia.</em></p><h2>What we collect</h2><ul><li>Account: name, email, phone, date of birth, address.</li><li>Order history, payment records and loyalty activity.</li><li>Device tokens for push notifications (opt-in).</li></ul><h2>How we use it</h2><ul><li>Fulfilling orders and managing loyalty rewards.</li><li>Customer service.</li><li>Marketing only with your consent.</li></ul><h2>Your rights</h2><ul><li>Request a copy of your data at any time from Profile → Privacy &amp; Data.</li><li>Withdraw consent for marketing.</li><li>Delete your account (orders kept anonymously for accounting).</li></ul>',
                'last_updated_label' => $now->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'body' => '<h2>How do I earn loyalty points?</h2><p>1 point per RM 1 you spend (subtotal, before tax). Higher tiers earn faster — Silver 1.25×, Gold 1.5×, Platinum 2×.</p><h2>How do I redeem points?</h2><p>100 points = RM 1.00 discount. Apply at checkout — the discount comes off your subtotal before tax.</p><h2>Why does my cart clear when I switch branches?</h2><p>Branches have different stock and pricing. Switching means we re-validate everything against the new branch.</p>',
                'last_updated_label' => $now->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
