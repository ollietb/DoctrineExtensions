<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sortable\Fixture\Transport\Car;
use Sortable\Fixture\Transport\Bus;
use Sortable\Fixture\Transport\Vehicle;
use Sortable\Fixture\Transport\Engine;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableGroupTest extends BaseTestCaseORM
{
    const CAR = "Sortable\Fixture\Transport\Car";
    const BUS = "Sortable\Fixture\Transport\Bus";
    const VEHICLE = "Sortable\Fixture\Transport\Vehicle";
    const ENGINE = "Sortable\Fixture\Transport\Engine";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SortableListener);

        $this->getMockSqliteEntityManager($evm);
        /*$this->getMockCustomEntityManager(array(
            'driver' => 'pdo_mysql',
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'user' => 'root',
            'password' => 'nimda'
        ), $evm);*/
    }

    /**
     * @test
     */
    public function shouldBeAbleToRemove()
    {
        $this->populate();
        $carRepo = $this->em->getRepository(self::CAR);

        $audi80 = $carRepo->findOneByTitle('Audi-80');
        $this->assertEquals(0, $audi80->getSortByEngine());

        $audi80s = $carRepo->findOneByTitle('Audi-80s');
        $this->assertEquals(1, $audi80s->getSortByEngine());

        $icarus = $this->em->getRepository(self::BUS)->findOneByTitle('Icarus');
        $this->assertEquals(2, $icarus->getSortByEngine());

        $this->em->remove($audi80);
        $this->em->flush();
    }
    
    /**
     * @test
     */
    public function shouldInsertAtZero()
    {
        $this->populate();
        
        $carRepo = $this->em->getRepository(self::CAR);

        // theres only 1 v6 so this should be position:0
        $audiJet = $carRepo->findOneByTitle('Audi-jet');
        $this->assertEquals(0, $audiJet->getSortByEngine());

    }
    
    /**
     * @test
     */
    public function shouldBeAbleToChangeGroup()
    {
        $this->populate();
        $carRepo = $this->em->getRepository(self::CAR);

        // position 0
        $audi80 = $carRepo->findOneByTitle('Audi-80');

        //position 1
        $audi80s = $carRepo->findOneByTitle('Audi-80s');

        //position 2
        $icarus = $this->em->getRepository(self::BUS)->findOneByTitle('Icarus');
        
        // theres only 1 v6 so this should be position:0
        $audiJet = $carRepo->findOneByTitle('Audi-jet');
        
        // change engines
        $v6engine = $this->em->getRepository(self::ENGINE)->findOneByType('V6');
        
        // lets change group
        $audi80s->setEngine($v6engine);

        $this->em->persist($audi80s);
        $this->em->flush();
        
        // Audi-80s should go after the v6
        $this->assertEquals(1, $audi80s->getSortByEngine());
        
        // Icarus should move to replace moved Audi-80s
        $this->assertEquals(1, $icarus->getSortByEngine());
        
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::CAR,
            self::ENGINE,
            self::BUS
        );
    }

    private function populate()
    {
        // engines
        $v8 = new Engine;
        $v8->setType('V8');
        $v8->setValves(8);
        $this->em->persist($v8);

        $v6 = new Engine;
        $v6->setType('V6');
        $v6->setValves(6);
        $this->em->persist($v6);
        $this->em->flush();

        // cars

        $audi80 = new Car;
        $audi80->setEngine($v8);
        $audi80->setTitle('Audi-80');
        $this->em->persist($audi80);

        $audi80s = new Car;
        $audi80s->setParent($audi80);
        $audi80s->setTitle('Audi-80s');
        $audi80s->setEngine($v8);
        $this->em->persist($audi80s);

        $icarus = new Bus;
        $icarus->setEngine($v8);
        $icarus->setTitle('Icarus');
        $this->em->persist($icarus);

        $audiJet = new Car;
        $audiJet->setParent($audi80);
        $audiJet->setTitle('Audi-jet');
        $audiJet->setEngine($v6);
        $this->em->persist($audiJet);

        $this->em->flush();
    }
}
