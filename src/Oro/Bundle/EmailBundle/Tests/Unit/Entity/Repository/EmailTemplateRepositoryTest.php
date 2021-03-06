<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class EmailTemplateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailTemplateRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = new EmailTemplateRepository(
            $this->entityManager,
            new ClassMetadata('Oro\Bundle\EmailBundle\Entity\EmailTemplate')
        );
    }

    protected function tearDown()
    {
        unset($this->entityManager);
        unset($this->repository);
    }

    /**
     * Test setters, getters
     */
    public function testGetTemplateByEntityName()
    {
        $persister = $this->getMockBuilder('Doctrine\ORM\Persisters\BasicEntityPersister')
            ->disableOriginalConstructor()
            ->getMock();
        $persister->expects($this->once())
            ->method('loadAll');

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $uow->expects($this->once())
            ->method('getEntityPersister')
            ->with('Oro\Bundle\EmailBundle\Entity\EmailTemplate')
            ->will($this->returnValue($persister));

        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $this->repository->getTemplateByEntityName('Oro\Bundle\UserBundle\Entity\User', new Organization());
    }

    public function testGetEntityQueryBuilderWithSystemTemplates()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('select')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('from')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('orWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('orderBy')
            ->will($this->returnSelf());
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->will($this->returnSelf());

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->repository->getEntityTemplatesQueryBuilder(
            'Oro\Bundle\UserBundle\Entity\User',
            new Organization(),
            true
        );
    }

    public function testGetEntityQueryBuilderWithoutSystemTemplates()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('select')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('from')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->never())
            ->method('orWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('orderBy')
            ->will($this->returnSelf());
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->will($this->returnSelf());

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->repository->getEntityTemplatesQueryBuilder(
            'Oro\Bundle\UserBundle\Entity\User',
            new Organization()
        );
    }
}
