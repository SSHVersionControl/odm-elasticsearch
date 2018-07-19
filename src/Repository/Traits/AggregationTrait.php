<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Repository\Traits;

use Elastica\Aggregation\Avg;
use Elastica\Aggregation\Max;
use Elastica\Aggregation\Min;
use Elastica\Aggregation\Sum;

trait AggregationTrait
{
    public function addSum(string $field, string $referenceName = null)
    {
        $sumAggregation = new Sum($referenceName ?? $field);
        $sumAggregation->setField($field);

        $this->query->addAggregation($sumAggregation);

        return $this;
    }

    public function addMax(string $field, string $referenceName = null)
    {
        $sumAggregation = new Max($referenceName ?? $field);
        $sumAggregation->setField($field);

        $this->query->addAggregation($sumAggregation);

        return $this;
    }

    public function addMin(string $field, string $referenceName = null)
    {
        $sumAggregation = new Min($referenceName ?? $field);
        $sumAggregation->setField($field);

        $this->query->addAggregation($sumAggregation);

        return $this;
    }

    public function addAverage(string $field, string $referenceName = null)
    {
        $sumAggregation = new Avg($referenceName ?? $field);
        $sumAggregation->setField($field);

        $this->query->addAggregation($sumAggregation);

        return $this;
    }
}
