<?php

namespace Mautic\LeadBundle\Segment\Stat;

use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\Stat\ChartQuery\SegmentContactsLineChartQuery;

class SegmentChartQueryFactory
{
    /**
     * @return array
     */
    public function getContactsTotal(SegmentContactsLineChartQuery $query, ListModel $listModel)
    {
        $total = $listModel->getRepository()->getLeadCount($query->getSegmentId());

        return $query->getTotalStats($total);
    }

    /**
     * @return array
     */
    public function getContactsAdded(SegmentContactsLineChartQuery $query)
    {
        return $query->getAddedEventLogStats();
    }

    /**
     * @return array
     */
    public function getContactsRemoved(SegmentContactsLineChartQuery $query)
    {
        return $query->getRemovedEventLogStats();
    }
}
