<?php

namespace App\Doctrine\Filter;

use App\Entity\Tenant;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Check if the entity is tenant-aware
        if (!$targetEntity->reflClass->hasProperty('tenant')) {
            return '';
        }

        // Check if we have a tenant ID parameter set
        if (!$this->hasParameter('tenant_id')) {
            return '';
        }

        return sprintf('%s.tenant_id = %s', $targetTableAlias, $this->getParameter('tenant_id'));
    }
}
