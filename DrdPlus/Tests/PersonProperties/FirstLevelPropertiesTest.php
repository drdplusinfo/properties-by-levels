<?php
namespace DrdPlus\Tests\PersonProperties;

use Drd\Genders\Female;
use Drd\Genders\Male;
use DrdPlus\PersonProperties\FirstLevelProperties;
use DrdPlus\Professions\Fighter;
use DrdPlus\Professions\Priest;
use DrdPlus\Professions\Ranger;
use DrdPlus\Professions\Theurgist;
use DrdPlus\Professions\Thief;
use DrdPlus\Professions\Wizard;
use DrdPlus\Races\Dwarfs\CommonDwarf;
use DrdPlus\Races\Dwarfs\MountainDwarf;
use DrdPlus\Races\Dwarfs\WoodDwarf;
use DrdPlus\Races\Elfs\CommonElf;
use DrdPlus\Races\Elfs\DarkElf;
use DrdPlus\Races\Elfs\GreenElf;
use DrdPlus\Races\Hobbits\CommonHobbit;
use DrdPlus\Races\Humans\CommonHuman;
use DrdPlus\Races\Humans\Highlander;
use DrdPlus\Races\Krolls\CommonKroll;
use DrdPlus\Races\Krolls\WildKroll;
use DrdPlus\Races\Orcs\CommonOrc;
use DrdPlus\Races\Orcs\Goblin;
use DrdPlus\Races\Orcs\Skurut;
use DrdPlus\Tools\Tests\TestWithMockery;

class FirstLevelPropertiesTest extends TestWithMockery
{
    public function I_can_get_every_property_both_limited_and_unlimited()
    {
        $firstLevelProperty = new FirstLevelProperties();
    }

    public function getCombinations()
    {
        $races = $this->getRaces();
        $genders = [Male::getIt(), Female::getIt()];
        // TODO
        $professions = $this->getProfessions();
    }

    private function getRaces()
    {
        return [
            CommonHuman::getIt(),
            Highlander::getIt(),

            CommonDwarf::getIt(),
            MountainDwarf::getIt(),
            WoodDwarf::getIt(),

            CommonElf::getIt(),
            GreenElf::getIt(),
            DarkElf::getIt(),

            CommonHobbit::getIt(),

            CommonKroll::getIt(),
            WildKroll::getIt(),

            CommonOrc::getIt(),
            Goblin::getIt(),
            Skurut::getIt(),
        ];
    }

    private function getProfessions()
    {
        return [
            new Fighter(),
            new Thief(),
            new Priest(),
            new Ranger(),
            new Theurgist(),
            new Wizard(),
        ];
    }
}
