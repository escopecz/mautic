<?php

namespace Mautic\LeadBundle\Segment\Stat\ChartQuery;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;

class SegmentContactsLineChartQuery extends ChartQuery
{
    /**
     * @var array
     */
    private $filters;

    /**
     * @var int
     */
    private $segmentId;

    /**
     * @var array
     */
    private $addedEventLogStats;

    /**
     * @var array
     */
    private $removedEventLogStats;

    /**
     * @param string|null $unit
     *
     * @throws SegmentNotFoundException
     */
    public function __construct(Connection $connection, \DateTime $dateFrom, \DateTime $dateTo, array $filters = [], $unit = null)
    {
        $this->connection = $connection;
        $this->dateFrom   = $dateFrom;
        $this->dateTo     = $dateTo;
        $this->unit       = $unit;
        $this->filters    = $filters;

        if (!isset($this->filters['leadlist_id']['value'])) {
            throw new SegmentNotFoundException('Segment ID required');
        }
        $this->segmentId  = $this->filters['leadlist_id']['value'];
        parent::__construct($connection, $dateFrom, $dateTo, $unit);
    }

    public function setDateRange(\DateTime $dateFrom, \DateTime $dateTo)
    {
        parent::setDateRange($dateFrom, $dateTo);
        $this->init();
    }

    /**
     * @return array
     */
    public function getTotalStats(int $total)
    {
        $totalCountDateTo = $this->getTotalToDateRange($total);
        // count array SUM and then reverse
        // require start from end and  substract added/removed logs
        $sums     = array_reverse(ArrayHelper::sub($this->getAddedEventLogStats(), $this->getRemovedEventLogStats()));
        $totalSum = 0;
        $totals   = array_map(function ($sum) use ($totalCountDateTo, &$totalSum) {
            $total = $totalCountDateTo - $totalSum;
            $totalSum += $sum;
            if ($total > -1) {
                return $total;
            } else {
                return 0;
            }
        }, $sums);

        return array_reverse($totals);
    }

    /**
     * Return total of contact to date end of graph.
     */
    private function getTotalToDateRange(int $total): int
    {
        $queryForTotal = clone $this;
        // try figure out total count in dateTo
        $queryForTotal->setDateRange($this->dateTo, new \DateTime());

        return $total - array_sum(ArrayHelper::sub($queryForTotal->getAddedEventLogStats(), $queryForTotal->getRemovedEventLogStats()));
    }

    /**
     * Get data about add/remove from segment based on LeadEventLog.
     *
     * @param string $action
     *
     * @return array
     */
    public function getDataFromLeadEventLog($action)
    {
        $qb = $this->prepareTimeDataQuery(
            'lead_event_log',
            'date_added',
            [
                'object'    => 'segment',
                'bundle'    => 'lead',
                'action'    => $action,
                'object_id' => $this->segmentId,
            ]
        );
        $qb = $this->optimizeSearchInLeadEventLog($qb);

        return $this->loadAndBuildTimeData($qb);
    }

    /**
     * @return int
     */
    public function getSegmentId()
    {
        return $this->segmentId;
    }

    /**
     * @return array
     */
    public function getAddedEventLogStats()
    {
        return $this->addedEventLogStats;
    }

    /**
     * @return array
     */
    public function getRemovedEventLogStats()
    {
        return $this->removedEventLogStats;
    }

    /**
     * Init basic stats.
     */
    private function init(): void
    {
        $this->addedEventLogStats   = $this->getDataFromLeadEventLog('added');
        $this->removedEventLogStats = $this->getDataFromLeadEventLog('removed');
    }

    private function optimizeSearchInLeadEventLog(QueryBuilder $qb): QueryBuilder
    {
        $fromPart             = $qb->getQueryPart('from');
        $fromPart[0]['alias'] = sprintf('%s USE INDEX (%s)', $fromPart[0]['alias'], MAUTIC_TABLE_PREFIX.LeadEventLog::INDEX_SEARCH);
        $qb->resetQueryPart('from');
        $qb->from($fromPart[0]['table'], $fromPart[0]['alias']);

        return $qb;
    }

    private function optimizeListLeadQuery(QueryBuilder $qb): QueryBuilder
    {
        if (
            // Remove unwanted self join with lead_lists_leads table
            false !== ($key = array_search(MAUTIC_TABLE_PREFIX.ListLead::TABLE_NAME, array_column($qb->getQueryPart('from'), 'table'))) &&
            false !== ($joinKey = array_search(MAUTIC_TABLE_PREFIX.ListLead::TABLE_NAME, array_column($qb->getQueryPart('join')[$tableAlias = $qb->getQueryPart('from')[$key]['alias']], 'joinTable')))
        ) {
            $joinAlias = $qb->getQueryPart('join')[$tableAlias][$joinKey]['joinAlias'];
            $qb->resetQueryPart('join');
            $compositeExpression = $qb->getQueryPart('where');
            $this->removeUnwantedWhereClause($compositeExpression, $joinAlias);
        }

        return $qb;
    }

    private function removeUnwantedWhereClause(CompositeExpression $compositeExpression, string $joinAlias): void
    {
        // CompositeExpression class has no way to remove members of it's 'parts' property, and we have
        // to resort on Reflection here.
        $compositeExpressionReflection      = new \ReflectionClass(CompositeExpression::class);
        $compositeExpressionReflectionParts = $compositeExpressionReflection->getProperty('parts');
        $compositeExpressionReflectionParts->setAccessible(true);
        $parts    = $compositeExpressionReflectionParts->getValue($compositeExpression);
        $newParts = array_filter($parts, function ($val) use ($joinAlias) {
            return 0 !== strpos($val, "$joinAlias.");
        });
        $compositeExpressionReflectionParts->setValue($compositeExpression, $newParts);
        $compositeExpressionReflectionParts->setAccessible(false);
    }

    private function optimizeSearchInLeadEventLog(QueryBuilder $qb): QueryBuilder
    {
        $fromPart             = $qb->getQueryPart('from');
        $fromPart[0]['alias'] = sprintf('%s USE INDEX (%s)', $fromPart[0]['alias'], MAUTIC_TABLE_PREFIX.LeadEventLog::INDEX_SEARCH);
        $qb->resetQueryPart('from');
        $qb->from($fromPart[0]['table'], $fromPart[0]['alias']);

        return $qb;
    }
}
