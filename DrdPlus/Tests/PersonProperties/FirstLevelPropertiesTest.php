<?php
namespace DrdPlus\Tests\PersonProperties;

use Drd\Genders\Female;
use DrdPlus\Exceptionalities\Properties\ExceptionalityProperties;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\PersonProperties\FirstLevelProperties;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\BaseProperty;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Body\Age;
use DrdPlus\Properties\Body\HeightInCm;
use DrdPlus\Properties\Body\Size;
use DrdPlus\Properties\Body\WeightInKg;
use DrdPlus\Races\Humans\CommonHuman;
use DrdPlus\Tables\Tables;
use Granam\Tests\Tools\TestWithMockery;

class FirstLevelPropertiesTest extends TestWithMockery
{
    /**
     * @test
     * @dataProvider provideBasePropertyValues
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
        $heightInCm = HeightInCm::getIt(123.45);
        $age = Age::getIt(32);
        $tables = new Tables();

        $firstLevelProperties = new FirstLevelProperties(
            $race,
            $gender,
            $exceptionalityProperties,
            $professionLevels,
            $weightInKgAdjustment,
            $heightInCm,
            $age,
            $tables
        );
        $expectedStrength = min($strength, 3) - 1; /* female */
        self::assertEquals(Strength::getIt($expectedStrength), $firstLevelProperties->getFirstLevelStrength());
        self::assertSame(max(0, $strength - 3), $firstLevelProperties->getStrengthLossBecauseOfLimit());

        $expectedAgility = min($agility, 3);
        self::assertEquals(Agility::getIt($expectedAgility), $firstLevelProperties->getFirstLevelAgility());
        self::assertSame(max(0, $agility - 3), $firstLevelProperties->getAgilityLossBecauseOfLimit());

        $expectedKnack = min($knack, 3);
        self::assertEquals(Knack::getIt($expectedKnack), $firstLevelProperties->getFirstLevelKnack());
        self::assertSame(max(0, $knack - 3), $firstLevelProperties->getKnackLossBecauseOfLimit());

        $expectedWill = min($will, 3);
        self::assertEquals(Will::getIt($expectedWill), $firstLevelProperties->getFirstLevelWill());
        self::assertSame(max(0, $will - 3), $firstLevelProperties->getWillLossBecauseOfLimit());

        $expectedIntelligence = min($intelligence, 3);
        self::assertEquals(Intelligence::getIt($expectedIntelligence), $firstLevelProperties->getFirstLevelIntelligence());
        self::assertSame(max(0, $intelligence - 3), $firstLevelProperties->getIntelligenceLossBecauseOfLimit());

        $expectedCharisma = min($charisma, 3) + 1; /* female */
        self::assertEquals(Charisma::getIt($expectedCharisma), $firstLevelProperties->getFirstLevelCharisma());
        self::assertSame(max(0, $charisma - 3), $firstLevelProperties->getCharismaLossBecauseOfLimit());

        $expectedSize = -1;/* female */
        if ($strength === 0) {
            $expectedSize--;
        } else if ($strength > 1) {
            $expectedSize++;
        }
        self::assertEquals(Size::getIt($expectedSize), $firstLevelProperties->getFirstLevelSize());

        self::assertSame($weightInKgAdjustment, $firstLevelProperties->getFirstLevelWeightInKgAdjustment());
        self::assertEquals(
            WeightInKg::getIt(70 + $weightInKgAdjustment->getValue()),
            $firstLevelProperties->getFirstLevelWeightInKg()
        );

        self::assertSame($heightInCm, $firstLevelProperties->getFirstLevelHeightInCm());
        self::assertSame($age, $firstLevelProperties->getFirstLevelAge());
    }

    public function provideBasePropertyValues()
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
        $exceptionalityProperties = $this->createExceptionalityProperties(
            -1, 0, 0, 0, 0, 0
        );

        new FirstLevelProperties(
            CommonHuman::getIt(),
            Female::getIt(),
            $exceptionalityProperties,
            $this->createProfessionLevels(),
            WeightInKg::getIt(0),
            HeightInCm::getIt(123),
            Age::getIt(20),
            new Tables()
        );
    }

}
