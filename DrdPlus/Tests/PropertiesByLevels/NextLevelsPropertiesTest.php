<?php
namespace DrdPlus\Tests\PropertiesByLevels;

use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\PropertiesByLevels\NextLevelsProperties;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use Granam\Tests\Tools\TestWithMockery;

class NextLevelsPropertiesTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_get_properties()
    {
        $sut = new NextLevelsProperties($this->createProfessionLevels(
            $strength = 1, $agility = 2, $knack = 3, $will = 4, $intelligence = 5, $charisma = 6
        ));
        self::assertSame(Strength::getIt($strength), $sut->getNextLevelsStrength());
        self::assertSame(Agility::getIt($agility), $sut->getNextLevelsAgility());
        self::assertSame(Knack::getIt($knack), $sut->getNextLevelsKnack());
        self::assertSame(Will::getIt($will), $sut->getNextLevelsWill());
        self::assertSame(Intelligence::getIt($intelligence), $sut->getNextLevelsIntelligence());
        self::assertSame(Charisma::getIt($charisma), $sut->getNextLevelsCharisma());
    }

    /**
     * @param int $strength
     * @param int $agility
     * @param int $knack
     * @param int $will
     * @param int $intelligence
     * @param int $charisma
     * @return \Mockery\MockInterface|ProfessionLevels
     */
    private function createProfessionLevels($strength, $agility, $knack, $will, $intelligence, $charisma)
    {
        $professionLevels = $this->mockery(ProfessionLevels::class);
        $professionLevels->shouldReceive('getNextLevelsStrengthModifier')
            ->andReturn($strength);
        $professionLevels->shouldReceive('getNextLevelsAgilityModifier')
            ->andReturn($agility);
        $professionLevels->shouldReceive('getNextLevelsKnackModifier')
            ->andReturn($knack);
        $professionLevels->shouldReceive('getNextLevelsWillModifier')
            ->andReturn($will);
        $professionLevels->shouldReceive('getNextLevelsIntelligenceModifier')
            ->andReturn($intelligence);
        $professionLevels->shouldReceive('getNextLevelsCharismaModifier')
            ->andReturn($charisma);

        return $professionLevels;
    }
}