<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get Business Units tree
     *
     * @param User     $entity
     * @param int|null $organizationId
     * @return array
     */
    public function getBusinessUnitsTree(User $entity = null, $organizationId = null)
    {
        return $this->getBusinessUnitRepo()->getBusinessUnitsTree($entity, $organizationId);
    }

    /**
     * Get business units ids
     *
     * @param int|null $organizationId
     * @return array
     */
    public function getBusinessUnitIds($organizationId = null)
    {
        return $this->getBusinessUnitRepo()->getBusinessUnitIds($organizationId);
    }

    /**
     * Get Current BU ID with child BU IDs
     *
     * @param int $businessUnitId
     * @param int $organizationId
     * @return array
     */
    public function getChildBusinessUnitIds($businessUnitId, $organizationId)
    {
        $tree = $this->getBusinessUnitsTree(null, $organizationId);
        $currentBuTree = $this->getBuWithChildTree($businessUnitId, $tree);

        return array_merge(
            [$currentBuTree['id']],
            isset($currentBuTree['children']) ? $this->getTreeIds($currentBuTree['children']) : []
        );
    }

    /**
     * @param array $criteria
     * @param array $orderBy
     * @return BusinessUnit
     */
    public function getBusinessUnit(array $criteria = array(), array $orderBy = null)
    {
        return $this->getBusinessUnitRepo()->findOneBy($criteria, $orderBy);
    }

    /**
     * Checks if user can be set as owner by given user
     *
     * @param User              $currentUser
     * @param User              $newUser
     * @param string            $accessLevel
     * @param OwnerTreeProvider $treeProvider
     * @param Organization      $organization
     * @return bool
     */
    public function canUserBeSetAsOwner(
        User $currentUser,
        User $newUser,
        $accessLevel,
        OwnerTreeProvider $treeProvider,
        Organization $organization
    ) {
        $userId = $newUser->getId();
        if ($accessLevel == AccessLevel::SYSTEM_LEVEL) {
            return true;
        } elseif ($accessLevel == AccessLevel::BASIC_LEVEL && $userId == $currentUser->getId()) {
            return true;
        } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL && $newUser->getOrganizations()->contains($organization)) {
            return true;
        } else {
            $resultBuIds = [];
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserBusinessUnitIds(
                    $currentUser->getId(),
                    $organization->getId()
                );
            } elseif ($accessLevel == AccessLevel::DEEP_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                    $currentUser->getId(),
                    $organization->getId()
                );
            }

            if (!empty($resultBuIds)) {
                $newUserBuIds = $treeProvider->getTree()->getUserBusinessUnitIds(
                    $userId,
                    $organization->getId()
                );
                $intersectBUIds = array_intersect($resultBuIds, $newUserBuIds);
                return !empty($intersectBUIds);
            }
        }

        return false;
    }

    /**
     * @return BusinessUnitRepository
     */
    public function getBusinessUnitRepo()
    {
        return $this->em->getRepository('OroOrganizationBundle:BusinessUnit');
    }

    /**
     * @return EntityRepository
     */
    public function getUserRepo()
    {
        return $this->em->getRepository('OroUserBundle:User');
    }

    /**
     * @param array $children
     * @return array
     */
    protected function getTreeIds($children)
    {
        $result = [];
        foreach ($children as $bu) {
            if (!empty($bu['children'])) {
                $result = array_merge($result, $this->getTreeIds($bu['children']));
            }
            $result[] = $bu['id'];
        }

        return $result;
    }

    /**
     * @param int $businessUnitId
     * @param array $tree
     * @return array
     */
    protected function getBuWithChildTree($businessUnitId, $tree)
    {
        $result = null;
        foreach ($tree as $bu) {
            if (!empty($bu['children'])) {
                $result = $this->getBuWithChildTree($businessUnitId, $bu['children']);
            }
            if ($bu['id'] === $businessUnitId) {
                $result = $bu;
            }

            if ($result) {
                return $result;
            }
        }
    }
}
