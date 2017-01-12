<?php
namespace DrdPlus\Tests\PropertiesByLevels;

use DrdPlus\Codes\GenderCode;
use DrdPlus\Codes\PropertyCode;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\Properties\Body\Height;
use DrdPlus\PropertiesByFate\PropertiesByFate;
use DrdPlus\PropertiesByLevels\FirstLevelProperties;
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
use Granam\Tools\ValueDescriber;

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
        $female = GenderCode::getIt(GenderCode::FEMALE);
        $propertiesByFate = $this->createPropertiesByFate(
            $strength, $agility, $knack, $will, $intelligence, $charisma
        );
        $professionLevels = $this->createProfessionLevels();
        $weightInKgAdjustment = WeightInKg::getIt(12.3);
        $heightInCmAdjustment = HeightInCm::getIt(123.45);
        $age = Age::getIt(32);
        $tables = new Tables();

        $firstLevelProperties = new FirstLevelProperties(
            $race,
            $female,
            $propertiesByFate,
            $professionLevels,
            $weightInKgAdjustment,
            $heightInCmAdjustment,
            $age,
            $tables
        );

        self::assertSame($propertiesByFate, $firstLevelProperties->getPropertiesByFate());

        $expectedStrength = min($strength, 3) - 1; /* female */
        self::assertInstanceOf(Strength::class, $firstLevelProperties->getFirstLevelStrength());
        self::assertSame($expectedStrength, $firstLevelProperties->getFirstLevelStrength()->getValue());
        self::assertSame(max(0, $strength - 3), $firstLevelProperties->getStrengthLossBecauseOfLimit());

        $expectedAgility = min($agility, 3);
        self::assertInstanceOf(Agility::class, $firstLevelProperties->getFirstLevelAgility());
        self::assertEquals($expectedAgility, $firstLevelProperties->getFirstLevelAgility()->getValue());
        self::assertSame(max(0, $agility - 3), $firstLevelProperties->getAgilityLossBecauseOfLimit());

        $expectedKnack = min($knack, 3);
        self::assertInstanceOf(Knack::class, $firstLevelProperties->getFirstLevelKnack());
        self::assertEquals($expectedKnack, $firstLevelProperties->getFirstLevelKnack()->getValue());
        self::assertSame(max(0, $knack - 3), $firstLevelProperties->getKnackLossBecauseOfLimit());

        $expectedWill = min($will, 3);
        self::assertInstanceOf(Will::class, $firstLevelProperties->getFirstLevelWill());
        self::assertEquals($expectedWill, $firstLevelProperties->getFirstLevelWill()->getValue());
        self::assertSame(max(0, $will - 3), $firstLevelProperties->getWillLossBecauseOfLimit());

        $expectedIntelligence = min($intelligence, 3);
        self::assertInstanceOf(Intelligence::class, $firstLevelProperties->getFirstLevelIntelligence());
        self::assertEquals($expectedIntelligence, $firstLevelProperties->getFirstLevelIntelligence()->getValue());
        self::assertSame(max(0, $intelligence - 3), $firstLevelProperties->getIntelligenceLossBecauseOfLimit());

        $expectedCharisma = min($charisma, 3) + 1; /* female */
        self::assertInstanceOf(Charisma::class, $firstLevelProperties->getFirstLevelCharisma());
        self::assertEquals($expectedCharisma, $firstLevelProperties->getFirstLevelCharisma()->getValue());
        self::assertSame(max(0, $charisma - 3), $firstLevelProperties->getCharismaLossBecauseOfLimit());

        $expectedSize = -1;/* female */
        if ($strength === 0) {
            $expectedSize--;
        } else if ($strength > 1) {
            $expectedSize++;
        }
        self::assertInstanceOf(Size::class, $firstLevelProperties->getFirstLevelSize());
        self::assertSame($expectedSize, $firstLevelProperties->getFirstLevelSize()->getValue());

        self::assertSame($weightInKgAdjustment, $firstLevelProperties->getFirstLevelWeightInKgAdjustment());
        self::assertEquals(
            WeightInKg::getIt(70 + $weightInKgAdjustment->getValue()),
            $firstLevelProperties->getFirstLevelWeightInKg()
        );

        self::assertSame($heightInCmAdjustment, $firstLevelProperties->getFirstLevelHeightInCmAdjustment());
        self::assertSame(
            $heightInCm = HeightInCm::getIt(180 + $heightInCmAdjustment->getValue()),
            $firstLevelProperties->getFirstLevelHeightInCm()
        );
        self::assertEquals(
            new Height($heightInCm, $tables->getDistanceTable()),
            $firstLevelProperties->getFirstLevelHeight()
        );
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
     * @return PropertiesByFate|\Mockery\MockInterface
     */
    private function createPropertiesByFate(
        $strength,
        $agility,
        $knack,
        $will,
        $intelligence,
        $charisma
    )
    {
        $propertiesByFate = $this->mockery(PropertiesByFate::class);
        $propertiesByFate->shouldReceive('getProperty')
            ->andReturnUsing(function ($propertyCode)
            use ($strength, $agility, $knack, $will, $intelligence, $charisma) {
                switch ($propertyCode) {
                    case PropertyCode::STRENGTH :
                        return $this->createProperty($strength);
                    case PropertyCode::AGILITY :
                        return $this->createProperty($agility);
                    case PropertyCode::KNACK :
                        return $this->createProperty($knack);
                    case PropertyCode::WILL :
                        return $this->createProperty($will);
                    case PropertyCode::INTELLIGENCE :
                        return $this->createProperty($intelligence);
                    case PropertyCode::CHARISMA :
                        return $this->createProperty($charisma);
                    default :
                        throw new \LogicException(
                            'Unexpected base property to return by PropertiesByFate: '
                            . ValueDescriber::describe($propertyCode)
                        );
                }
            });
        $propertiesByFate->shouldReceive('getStrength')
            ->andReturn($this->createProperty($strength));

        return $propertiesByFate;
    }

    private function createProperty($propertyValue)
    {
        $property = $this->mockery(BaseProperty::class);
        $property->shouldReceive('getValue')
            ->andReturn($propertyValue);

        return $property;
    }

    /**
     * @return ProfessionLevels|\Mockery\MockInterface
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
     * @expectedException \DrdPlus\PropertiesByLevels\Exceptions\TooLowStrengthAdjustment
     */
    public function I_can_not_get_it_with_too_low_strength()
    {
        $propertiesByFate = $this->createPropertiesByFate(
            -1, 0, 0, 0, 0, 0
        );

        new FirstLevelProperties(
            CommonHuman::getIt(),
            GenderCode::getIt(GenderCode::MALE),
            $propertiesByFate,
            $this->createProfessionLevels(),
            WeightInKg::getIt(0),
            HeightInCm::getIt(123),
            Age::getIt(20),
            new Tables()
        );
    }

}