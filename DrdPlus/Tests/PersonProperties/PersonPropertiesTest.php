<?php
namespace DrdPlus\Tests\PersonProperties;

use Drd\Genders\Female;
use Drd\Genders\Gender;
use Drd\Genders\Male;
use DrdPlus\Exceptionalities\Properties\ExceptionalityProperties;
use DrdPlus\GameCharacteristics\Combat\Attack;
use DrdPlus\GameCharacteristics\Combat\Defense;
use DrdPlus\GameCharacteristics\Combat\DefenseAgainstShooting;
use DrdPlus\GameCharacteristics\Combat\Shooting;
use DrdPlus\Person\ProfessionLevels\ProfessionLevel;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\PersonProperties\FirstLevelProperties;
use DrdPlus\PersonProperties\NextLevelsProperties;
use DrdPlus\PersonProperties\PersonProperties;
use DrdPlus\Professions\Profession;
use DrdPlus\Professions\Fighter;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Body\Size;
use DrdPlus\Properties\Body\WeightInKg;
use DrdPlus\Properties\Derived\Beauty;
use DrdPlus\Properties\Derived\Dangerousness;
use DrdPlus\Properties\Derived\Dignity;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueLimit;
use DrdPlus\Properties\Derived\Senses;
use DrdPlus\Properties\Derived\Speed;
use DrdPlus\Properties\Derived\Toughness;
use DrdPlus\Properties\Derived\WoundsLimit;
use DrdPlus\Races\Humans\CommonHuman;
use DrdPlus\Races\Race;
use DrdPlus\Tables\Tables;
use DrdPlus\Tools\Calculations\SumAndRound;
use Granam\Integer\IntegerObject;

class PersonPropertiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider getCombination
     *
     * @param Race $race
     * @param Gender $gender
     * @param ExceptionalityProperties $exceptionalityProperties
     * @param ProfessionLevels $professionLevels
     * @param Tables $tables
     * @param WeightInKg $weightInKgAdjustment
     * @param int $expectedStrength
     * @param int $expectedAgility
     * @param int $expectedKnack
     * @param int $expectedWill
     * @param int $expectedIntelligence
     * @param int $expectedCharisma
     */
    public function I_can_create_properties_for_any_combination(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        Tables $tables,
        WeightInKg $weightInKgAdjustment,
        $expectedStrength,
        $expectedAgility,
        $expectedKnack,
        $expectedWill,
        $expectedIntelligence,
        $expectedCharisma
    )
    {
        $properties = new PersonProperties(
            $race, $gender, $exceptionalityProperties, $professionLevels, $weightInKgAdjustment, $tables
        );

        self::assertInstanceOf(FirstLevelProperties::class, $properties->getFirstLevelProperties());
        self::assertInstanceOf(NextLevelsProperties::class, $properties->getNextLevelsProperties());

        self::assertSame($expectedStrength, $properties->getStrength()->getValue(), "$race $gender");
        self::assertSame($expectedAgility, $properties->getAgility()->getValue(), "$race $gender");
        self::assertSame($expectedKnack, $properties->getKnack()->getValue(), "$race $gender");
        self::assertSame($expectedWill, $properties->getWill()->getValue(), "$race $gender");
        self::assertSame($expectedIntelligence, $properties->getIntelligence()->getValue(), "$race $gender");
        self::assertSame($expectedCharisma, $properties->getCharisma()->getValue(), "$race $gender");

        self::assertGreaterThan($weightInKgAdjustment->getValue(), $properties->getWeightInKg()->getValue(), "$race $gender");
        $expectedToughness = new Toughness(Strength::getIt($expectedStrength), $race->getRaceCode(), $race->getSubraceCode(), $tables->getRacesTable());
        self::assertEquals($expectedToughness, $properties->getToughness(), "$race $gender");
        $expectedEndurance = new Endurance(Strength::getIt($expectedStrength), Will::getIt($expectedWill));
        self::assertEquals($expectedEndurance, $properties->getEndurance(), "$race $gender");
        $expectedSize = Size::getIt($race->getSize($gender, $tables) + 1); /* size bonus by strength */
        self::assertEquals($expectedSize, $properties->getSize(), "$race $gender");
        $expectedSpeed = new Speed(Strength::getIt($expectedStrength), Agility::getIt($expectedAgility), $expectedSize);
        self::assertEquals($expectedSpeed, $properties->getSpeed(), "$race $gender");
        $expectedSenses = new Senses(Knack::getIt($expectedKnack), $race->getSenses($tables->getRacesTable()));
        self::assertEquals($expectedSenses, $properties->getSenses(), "$race $gender");
        $expectedBeauty = new Beauty(Agility::getIt($expectedAgility), Knack::getIt($expectedKnack), Charisma::getIt($expectedCharisma));
        self::assertEquals($expectedBeauty, $properties->getBeauty(), "$race $gender");
        $expectedDangerousness = new Dangerousness(Strength::getIt($expectedStrength), Will::getIt($expectedWill), Charisma::getIt($expectedCharisma));
        self::assertEquals($expectedDangerousness, $properties->getDangerousness(), "$race $gender");
        $expectedDignity = new Dignity(Intelligence::getIt($expectedIntelligence), Will::getIt($expectedWill), Charisma::getIt($expectedCharisma));
        self::assertEquals($expectedDignity, $properties->getDignity(), "$race $gender");

        $expectedFight = $expectedAgility /* fighter */ + (SumAndRound::ceil($expectedSize->getValue() / 3) - 2);
        self::assertSame($expectedFight, $properties->getFight()->getValue(), "$race $gender");
        $expectedAttack = new Attack(Agility::getIt($expectedAgility));
        self::assertEquals($expectedAttack, $properties->getAttack(), "$race $gender");
        $expectedShooting = new Shooting(Knack::getIt($expectedKnack));
        self::assertEquals($expectedShooting, $properties->getShooting(), "$race $gender");
        $expectedDefense = new Defense(Agility::getIt($expectedAgility));
        self::assertEquals($expectedDefense, $properties->getDefense(), "$race $gender");
        $expectedDefenseAgainstShooting = new DefenseAgainstShooting($expectedDefense, $expectedSize);
        self::assertEquals($expectedDefenseAgainstShooting, $properties->getDefenseAgainstShooting(), "$race $gender");

        $expectedWoundsLimit = new WoundsLimit($expectedToughness, $tables->getWoundsTable());
        self::assertEquals($expectedWoundsLimit, $properties->getWoundsLimit());
        $expectedFatigueLimit = new FatigueLimit($expectedEndurance, $tables->getFatigueTable());
        self::assertEquals($expectedFatigueLimit, $properties->getFatigueLimit());
    }

    public function getCombination()
    {
        $male = Male::getIt();
        $female = Female::getIt();
        $exceptionalityProperties = $this->createExceptionalityProperties();
        $professionLevels = $this->createProfessionLevels();
        $tables = new Tables();
        $weightInKgAdjustment = WeightInKg::getIt(0.001);
        $baseOfExpectedStrength = $professionLevels->getNextLevelsStrengthModifier() + 3; /* default max strength increment */
        $baseOfExpectedAgility = $professionLevels->getNextLevelsAgilityModifier() + 3; /* default max agility increment */
        $baseOfExpectedKnack = $professionLevels->getNextLevelsKnackModifier() + 3; /* default max knack increment */
        $baseOfExpectedWill = $professionLevels->getNextLevelsWillModifier() + 3; /* default max knack increment */
        $baseOfExpectedIntelligence = $professionLevels->getNextLevelsIntelligenceModifier() + 3; /* default max knack increment */
        $baseOfExpectedCharisma = $professionLevels->getNextLevelsCharismaModifier() + 3; /* default max charisma increment */

        return [
            [
                $commonHuman = CommonHuman::getIt(), $male, $exceptionalityProperties, $professionLevels, $tables,
                $weightInKgAdjustment, $baseOfExpectedStrength, $baseOfExpectedAgility, $baseOfExpectedKnack,
                $baseOfExpectedWill, $baseOfExpectedIntelligence, $baseOfExpectedCharisma,
            ],
            [
                $commonHuman, $female, $exceptionalityProperties, $professionLevels, $tables, $weightInKgAdjustment,
                $baseOfExpectedStrength - 1 /* human female */, $baseOfExpectedAgility, $baseOfExpectedKnack,
                $baseOfExpectedWill, $baseOfExpectedIntelligence, $baseOfExpectedCharisma + 1 /* human female */,
            ],
            // ... no reason to check every race
        ];
    }

    /**
     * @return ExceptionalityProperties
     */
    private function createExceptionalityProperties()
    {
        $exceptionalityProperties = \Mockery::mock(ExceptionalityProperties::class);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->with(Strength::STRENGTH)
            ->andReturn($strength = new IntegerObject(123));
        $exceptionalityProperties->shouldReceive('getStrength')
            ->andReturn($strength);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->with(Agility::AGILITY)
            ->andReturn($agility = new IntegerObject(234));
        $exceptionalityProperties->shouldReceive('getAgility')
            ->andReturn($agility);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->with(Knack::KNACK)
            ->andReturn($knack = new IntegerObject(345));
        $exceptionalityProperties->shouldReceive('getKnack')
            ->andReturn($knack);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->with(Will::WILL)
            ->andReturn($will = new IntegerObject(456));
        $exceptionalityProperties->shouldReceive('getWill')
            ->andReturn($will);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->with(Intelligence::INTELLIGENCE)
            ->andReturn($intelligence = new IntegerObject(567));
        $exceptionalityProperties->shouldReceive('getIntelligence')
            ->andReturn($intelligence);
        $exceptionalityProperties->shouldReceive('getProperty')
            ->with(Charisma::CHARISMA)
            ->andReturn($charisma = new IntegerObject(678));
        $exceptionalityProperties->shouldReceive('getCharisma')
            ->andReturn($charisma);

        return $exceptionalityProperties;
    }

    /**
     * @return ProfessionLevels
     */
    private function createProfessionLevels()
    {
        $professionLevels = \Mockery::mock(ProfessionLevels::class);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->with(Strength::STRENGTH)
            ->andReturn($strength = 1234);
        $professionLevels->shouldReceive('getFirstLevelStrengthModifier')
            ->andReturn($strength);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->with(Agility::AGILITY)
            ->andReturn($agility = 2345);
        $professionLevels->shouldReceive('getFirstLevelAgilityModifier')
            ->andReturn($agility);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->with(Knack::KNACK)
            ->andReturn($knack = 3456);
        $professionLevels->shouldReceive('getFirstLevelKnackModifier')
            ->andReturn($knack);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->with(Will::WILL)
            ->andReturn($will = 3456);
        $professionLevels->shouldReceive('getFirstLevelWillModifier')
            ->andReturn($will);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->with(Intelligence::INTELLIGENCE)
            ->andReturn($intelligence = 5678);
        $professionLevels->shouldReceive('getFirstLevelIntelligenceModifier')
            ->andReturn($intelligence);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->with(Charisma::CHARISMA)
            ->andReturn($charisma = 6789);
        $professionLevels->shouldReceive('getFirstLevelPropertyModifier')
            ->andReturn($charisma);

        $professionLevels->shouldReceive('getNextLevelsStrengthModifier')
            ->andReturn(2); // is not limited by FirstLevelProperties and has to fit to wounds table range
        $professionLevels->shouldReceive('getNextLevelsAgilityModifier')
            ->andReturn(23456);
        $professionLevels->shouldReceive('getNextLevelsKnackModifier')
            ->andReturn(34567);
        $professionLevels->shouldReceive('getNextLevelsWillModifier')
            ->andReturn(4); // is not limited by FirstLevelProperties and has to fit to wounds table range
        $professionLevels->shouldReceive('getNextLevelsIntelligenceModifier')
            ->andReturn(56789);
        $professionLevels->shouldReceive('getNextLevelsCharismaModifier')
            ->andReturn(67890);

        $professionLevels->shouldReceive('getFirstLevel')
            ->andReturn($firstLevel = \Mockery::mock(ProfessionLevel::class));
        $firstLevel->shouldReceive('getProfession')
            ->andReturn($profession = \Mockery::mock(Profession::class));
        $profession->shouldReceive('getValue')
            ->andReturn(Fighter::FIGHTER);

        return $professionLevels;
    }
}
