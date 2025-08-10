<?php
/**
 * Class Affiliate
 *
 * Manages affiliate accounts, tracking referrals and calculating commissions.
 */
class Affiliate
{
    protected array $affiliates = [];
    protected array $referrals = [];
    protected float $commissionRate;

    public function __construct(float $commissionRate = 0.1)
    {
        $this->commissionRate = $commissionRate;
    }

    /**
     * Register a new affiliate.
     */
    public function register(string $affiliateId, string $name): bool
    {
        if (isset($this->affiliates[$affiliateId])) {
            return false;
        }
        $this->affiliates[$affiliateId] = [
            'name'      => $name,
            'earnings'  => 0.0,
        ];
        return true;
    }

    /**
     * Track a referral by affiliate ID.
     */
    public function trackReferral(string $affiliateId, float $saleAmount): bool
    {
        if (!isset($this->affiliates[$affiliateId])) {
            return false;
        }
        $commission = $saleAmount * $this->commissionRate;
        $this->affiliates[$affiliateId]['earnings'] += $commission;
        $this->referrals[] = [
            'affiliate_id' => $affiliateId,
            'sale_amount'  => $saleAmount,
            'commission'   => $commission,
            'timestamp'    => time(),
        ];
        return true;
    }

    /**
     * Get affiliate earnings.
     */
    public function getEarnings(string $affiliateId): float
    {
        return $this->affiliates[$affiliateId]['earnings'] ?? 0.0;
    }

    /**
     * List all referrals for an affiliate.
     */
    public function getReferrals(string $affiliateId): array
    {
        return array_filter($this->referrals, fn($r) => $r['affiliate_id'] === $affiliateId);
    }

    /**
     * Get all registered affiliates.
     */
    public function listAffiliates(): array
    {
        return $this->affiliates;
    }
}
