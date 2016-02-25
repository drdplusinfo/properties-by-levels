<?php
namespace DrdPlus\Tests\PersonProperties;

use Drd\Genders\Female;
use DrdPlus\Exceptionalities\ExceptionalityProperties;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\PersonProperties\FirstLevelProperties;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\BaseProperty;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Body\Size;
use DrdPlus\Properties\Body\WeightInKg;
use DrdPlus\Races\Humans\CommonHuman;
use DrdPlus\Tables\Tables;
use Granam\Tests\Tools\TestWithMockery;

class FirstLevelPropertiesTest extends TestWithMockery
{
    /**
     * @test
     * @dataProvider provideProperties
     * @param $strength
     * @param $agility
     * @param $knack
     * @param $will
     * @param $intelligence
     * @param $charisma
     */
    public function I_can_get_every_property_both_limited_and_unlimited(
        $strength, $agility, $knack, $will, $intelligence, $charisma
    )
    {
        $race = CommonHuman::getIt();
        $gender = Female::getIt();
        $exceptionalityProperties = $this->createExceptionalityProperties(
            $strength, $agility, $knack, $will, $intelligence, $charisma
        );
        $professionLevels = $this->createProfessionLevels();
        $weightInKgAdjustment = WeightInKg::getIt(12.3);
        $tables = new Tables();

        $firstLevelProperty = new FirstLevelProperties(
            $race,
            $gender,
            $exceptionalityProperties,
            $professionLevels,
            $weightInKgAdjustment,
            $tables
        );
        $expectedStrength = min($strength, 3) - 1; /* female */
        $this->assertEquals(Strength::getIt($expectedStrength), $firstLevelProperty->getFirstLevelStrength());
        $this->assertSame(max(0, $strength - 3), $firstLevelProperty->getStrengthLossBecauseOfLimit());

        $expectedAgility = min($agility, 3);
        $this->assertEquals(Agility::getIt($expectedAgility), $firstLevelProperty->getFirstLevelAgility());
        $this->assertSame(max(0, $agility - 3), $firstLevelProperty->getAgilityLossBecauseOfLimit());

        $expectedKnack = min($knack, 3);
        $this->assertEquals(Knack::getIt($expectedKnack), $firstLevelProperty->getFirstLevelKnack());
        $this->assertSame(max(0, $knack - 3), $firstLevelProperty->getKnackLossBecauseOfLimit());

        $expectedWill = min($will, 3);
        $this->assertEquals(Will::getIt($expectedWill), $firstLevelProperty->getFirstLevelWill());
        $this->assertSame(max(0, $will - 3), $firstLevelProperty->getWillLossBecauseOfLimit());

        $expectedIntelligence = min($intelligence, 3);
        $this->assertEquals(Intelligence::getIt($expectedIntelligence), $firstLevelProperty->getFirstLevelIntelligence());
        $this->assertSame(max(0, $intelligence - 3), $firstLevelProperty->getIntelligenceLossBecauseOfLimit());

        $expectedCharisma = min($charisma, 3) + 1; /* female */
        $this->assertEquals(Charisma::getIt($expectedCharisma), $firstLevelProperty->getFirstLevelCharisma());
        $this->assertSame(max(0, $charisma - 3), $firstLevelProperty->getCharismaLossBecauseOfLimit());

        $this->assertEquals(Size::getIt(
            ($strength === 0
                ? -1
                : ($strength === 1
                    ? 0
                    : 1
                )
            ) - 1 /* female */
        ), $firstLevelProperty->getFirstLevelSize());

        $this->assertEquals(
            WeightInKg::getIt(70 + $weightInKgAdjustment->getValue()),
            $firstLevelProperty->getFirstLevelWeightInKg()
        );
    }

    public function provideProperties()
    {
        return [
            [0, 0, 0, 0, 0, 0],
            [1, 0, 0, 0, 0, 0],
            [2, 0, 0, 0, 0, 0],
            [3, 0, 0, 0, 0, 0],
            [10, 11, 12, 13, 14, 15],
        ];
    }

    // negative test with strength adjustment < 0

    /**
     * @param $strength
     * @param $agility
     * @param $knack
     * @param $will
     * @param $intelligence
     * @param $charisma
     * @return ExceptionalityProperties
     */
    private function createExceptionalityProperties(
        $strength, $agility, $knack, $will, $intelligence, $charisma
    )
    {
        $exceptionalityProperties = $this->mockery(ExceptionalityProperties::class);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->andReturnUsing(function ($propertyCode)
            use ($strength, $agility, $knack, $will, $intelligence, $charisma) {
                switch ($propertyCode) {
                    case Strength::STRENGTH :
                        return $this->createProperty($strength);
                    case Agility::AGILITY :
                        return $this->createProperty($agility);
                    case Knack::KNACK :
                        return $this->createProperty($knack);
                    case Will::WILL :
                        return $this->createProperty($will);
                    case Intelligence::INTELLIGENCE :
                        return $this->createProperty($intelligence);
                    case Charisma::CHARISMA :
                        return $this->createProperty($charisma);
                    default :
                        throw new \LogicException;
                }
            });
        $exceptionalityProperties->shouldReceive('getStrength')
            ->andReturn($this->createProperty($strength));

        return $exceptionalityProperties;
    }

    private function createProperty($propertyValue)
    {
        $property = $this->mockery(BaseProperty::class);
        $property->shouldReceive('getValue')
            ->andReturn($propertyValue);

        return $property;
    }

    /**
     * @return ProfessionLevels
     */
    private function createProfessionLevels()
    {
        $professionLevels = $this->mockery(ProfessionLevels::class);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->andReturn(0);
        $professionLevels->shouldReceive('getFirstLevelStrengthModifier')
            ->andReturn(0);

        return $professionLevels;
    }

    /**
     * @test
     * @expectedException \DrdPlus\PersonProperties\Exceptions\TooLowStrengthAdjustment
     */
    public function I_can_not_get_it_with_too_low_strength()
    {
        $race = CommonHuman::getIt();
        $gender = Female::getIt();
        $exceptionalityProperties = $this->createExceptionalityProperties(
            -1, 0, 0, 0, 0, 0
        );
        $professionLevels = $this->createProfessionLevels();
        $weightInKgAdjustment = WeightInKg::getIt(0);
        $tables = new Tables();

        new FirstLevelProperties(
            $race,
            $gender,
            $exceptionalityProperties,
            $professionLevels,
            $weightInKgAdjustment,
            $tables
        );
    }

}
