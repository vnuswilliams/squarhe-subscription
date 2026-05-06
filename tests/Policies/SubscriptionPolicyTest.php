<?php

namespace Tests\Feature\Policies;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Squarhe\Subscription\Models\Feature;
use Squarhe\Subscription\Models\Plan;
use Squarhe\Subscription\Models\Subscription;
use Squarhe\Subscription\Policies\SubscriptionPolicy;
use Tests\Mocks\Models\User;
use Tests\TestCase;

class SubscriptionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function testOwnerCanViewUpdateAndDeleteSubscription()
    {
        $subscription = $this->makeSubscription();
        $policy = new SubscriptionPolicy();

        $this->assertTrue($policy->view($subscription->subscriber, $subscription));
        $this->assertTrue($policy->update($subscription->subscriber, $subscription));
        $this->assertTrue($policy->delete($subscription->subscriber, $subscription));
    }

    public function testNonOwnerCannotViewUpdateOrDeleteSubscription()
    {
        $subscription = $this->makeSubscription();
        $anotherUser = User::factory()->create();
        $policy = new SubscriptionPolicy();

        $this->assertFalse($policy->view($anotherUser, $subscription));
        $this->assertFalse($policy->update($anotherUser, $subscription));
        $this->assertFalse($policy->delete($anotherUser, $subscription));
    }

    public function testRenewAndCancelAreForbiddenWhenSubscriptionAlreadyCanceled()
    {
        $subscription = $this->makeSubscription(['canceled_at' => now()]);
        $policy = new SubscriptionPolicy();

        $this->assertFalse($policy->renew($subscription->subscriber, $subscription));
        $this->assertFalse($policy->cancel($subscription->subscriber, $subscription));
    }

    public function testRenewAndCancelAreAllowedForOwnerWhenNotCanceled()
    {
        $subscription = $this->makeSubscription();
        $policy = new SubscriptionPolicy();

        $this->assertTrue($policy->renew($subscription->subscriber, $subscription));
        $this->assertTrue($policy->cancel($subscription->subscriber, $subscription));
    }

    public function testForceDeleteIsAlwaysForbidden()
    {
        $subscription = $this->makeSubscription();
        $policy = new SubscriptionPolicy();

        $this->assertFalse($policy->forceDelete($subscription->subscriber, $subscription));
    }


    public function testConsumeFeatureIsAllowedWhenOwnerHasConsumableCharges()
    {
        $subscription = $this->makeSubscription();
        $feature = Feature::factory()->quota()->prepaid()->create([
            'name' => 'deploy-minutes',
        ]);
        $subscription->plan->features()->attach($feature->id, ['charges' => 100]);

        $policy = new SubscriptionPolicy();

        $this->assertTrue($policy->consumeFeature(
            $subscription->subscriber,
            $subscription,
            'deploy-minutes',
            10,
        ));
    }

    public function testConsumeFeatureIsDeniedWhenChargesAreInsufficient()
    {
        $subscription = $this->makeSubscription();
        $feature = Feature::factory()->quota()->prepaid()->create([
            'name' => 'build-minutes',
        ]);
        $subscription->plan->features()->attach($feature->id, ['charges' => 5]);

        $policy = new SubscriptionPolicy();

        $this->assertFalse($policy->consumeFeature(
            $subscription->subscriber,
            $subscription,
            'build-minutes',
            10,
        ));
    }

    public function testSetFeatureConsumedQuotaChecksAvailableTotalCharges()
    {
        $subscription = $this->makeSubscription();
        $feature = Feature::factory()->quota()->prepaid()->create([
            'name' => 'storage',
        ]);
        $subscription->plan->features()->attach($feature->id, ['charges' => 30]);

        $policy = new SubscriptionPolicy();

        $this->assertTrue($policy->setFeatureConsumedQuota($subscription->subscriber, $subscription, 'storage', 20));
        $this->assertFalse($policy->setFeatureConsumedQuota($subscription->subscriber, $subscription, 'storage', 40));
    }


    public function testPolicyCanBePublished()
    {
        $targetPath = app_path('Policies/SubscriptionPolicy.php');

        if (file_exists($targetPath)) {
            unlink($targetPath);
        }

        $this->artisan('vendor:publish', [
            '--tag' => 'soulbscription-policies',
        ])->assertExitCode(0);

        $this->assertFileExists($targetPath);
    }

    private function makeSubscription(array $attributes = []): Subscription
    {
        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();

        return Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create($attributes);
    }
}
