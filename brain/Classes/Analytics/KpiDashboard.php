<?php

declare(strict_types=1);

/**
 * KPI Dashboard
 * 
 * Manages key performance indicators
 */
class KpiDashboard
{
    private array $kpis = [];
    private array $metrics = [];

    /**
     * Add a KPI
     */
    public function addKpi(string $name, string $title, callable $calculator): void
    {
        $this->kpis[$name] = [
            'title' => $title,
            'calculator' => $calculator
        ];
    }

    /**
     * Update a metric
     */
    public function updateMetric(string $name, mixed $value): void
    {
        $this->metrics[$name] = $value;
    }

    /**
     * Get KPI value
     */
    public function getKpi(string $name): mixed
    {
        if (!isset($this->kpis[$name])) {
            return null;
        }

        $calculator = $this->kpis[$name]['calculator'];
        return $calculator($this->metrics);
    }

    /**
     * Get all KPIs
     */
    public function getAllKpis(): array
    {
        $result = [];
        foreach ($this->kpis as $name => $kpi) {
            $result[$name] = [
                'title' => $kpi['title'],
                'value' => $this->getKpi($name)
            ];
        }
        return $result;
    }
}