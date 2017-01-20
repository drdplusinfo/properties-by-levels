<?php
namespace DrdPlus\PropertiesByLevels;

use DrdPlus\Codes\GenderCode;
use DrdPlus\Codes\RaceCode;
use DrdPlus\Codes\SubRaceCode;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Body\Age;
use DrdPlus\Properties\Body\Height;
use DrdPlus\Properties\Body\HeightInCm;
use DrdPlus\Properties\Body\Size;
use DrdPlus\Properties\Body\WeightInKg;
use DrdPlus\Properties\Combat\Attack;
use DrdPlus\Properties\Combat\BaseProperties;
use DrdPlus\Properties\Combat\DefenseNumberAgainstShooting;
use DrdPlus\Properties\Combat\DefenseNumber;
use DrdPlus\Properties\Combat\FightNumber;
use DrdPlus\Properties\Combat\Shooting;
use DrdPlus\Properties\Derived\Beauty;
use DrdPlus\Properties\Derived\Dangerousness;
use DrdPlus\Properties\Derived\Dignity;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Properties\Derived\Senses;
use DrdPlus\Properties\Derived\Speed;
use DrdPlus\Properties\Derived\Toughness;
use DrdPlus\Properties\Derived\WoundBoundary;
use DrdPlus\PropertiesByFate\PropertiesByFate;
use DrdPlus\Races\Race;
use DrdPlus\Tables\Tables;
use Granam\Strict\Object\StrictObject;

class PropertiesByLevels extends StrictObject implements BaseProperties
{

    /** @var FirstLevelProperties */
    private $firstLevelProperties;
    /** @var NextLevelsProperties */
    private $nextLevelsProperties;
    /** @var Strength */
    private $strength;
    /** @var Agility */
    private $agility;
    /** @var Knack */
    private $knack;
    /** @var Will */
    private $will;
    /** @var Intelligence */
    private $intelligence;
    /** @var Charisma */
    private $charisma;
    /** @var Toughness */
    private $toughness;
    /** @var Endurance */
    private $endurance;
    /** @var Speed */
    private $speed;
    /** @var Senses */
    private $senses;
    /** @var Beauty */
    private $beauty;
    /** @var Dangerousness */
    private $dangerousness;
    /** @var Dignity */
    private $dignity;
    /** @var FightNumber */
    private $fightNumber;
    /** @var Attack */
    private $attack;
    /** @var Shooting */
    private $shooting;
    /** @var DefenseNumber */
    private $defenseNumber;
    /** @var DefenseNumberAgainstShooting */
    private $defenseAgainstShooting;
    /** @var WoundBoundary */
    private $woundsLimit;
    /** @var FatigueBoundary */
    private $fatigueLimit;

    /**
     * @param Race $race
     * @param GenderCode $genderCode
     * @param PropertiesByFate $propertiesByFate
     * @param ProfessionLevels $professionLevels
     * @param WeightInKg $weightInKgAdjustment
     * @param HeightInCm $heightInCm
     * @param Age $age
     * @param Tables $tables
     * @throws Exceptions\TooLowStrengthAdjustment
     */
    public function __construct(
        Race $race,
        GenderCode $genderCode,
        PropertiesByFate $propertiesByFate,
        ProfessionLevels $professionLevels,
        WeightInKg $weightInKgAdjustment,
        HeightInCm $heightInCm,
        Age $age,
        Tables $tables
    )
    {
        $this->firstLevelProperties = new FirstLevelProperties(
            $race,
            $genderCode,
            $propertiesByFate,
            $professionLevels,
            $weightInKgAdjustment,
            $heightInCm,
            $age,
            $tables
        );
        $this->nextLevelsProperties = new NextLevelsProperties($professionLevels);

        $this->strength = Strength::getIt(
            $this->firstLevelProperties->getFirstLevelStrength()->getValue()
            + $this->nextLevelsProperties->getNextLevelsStrength()->getValue()
        );
        $this->agility = Agility::getIt(
            $this->firstLevelProperties->getFirstLevelAgility()->getValue()
            + $this->nextLevelsProperties->getNextLevelsAgility()->getValue()
        );
        $this->knack = Knack::getIt(
            $this->firstLevelProperties->getFirstLevelKnack()->getValue()
            + $this->nextLevelsProperties->getNextLevelsKnack()->getValue()
        );
        $this->will = Will::getIt(
            $this->firstLevelProperties->getFirstLevelWill()->getValue()
            + $this->nextLevelsProperties->getNextLevelsWill()->getValue()
        );
        $this->intelligence = Intelligence::getIt(
            $this->firstLevelProperties->getFirstLevelIntelligence()->getValue()
            + $this->nextLevelsProperties->getNextLevelsIntelligence()->getValue()
        );
        $this->charisma = Charisma::getIt(
            $this->firstLevelProperties->getFirstLevelCharisma()->getValue()
            + $this->nextLevelsProperties->getNextLevelsCharisma()->getValue()
        );

        // delivered properties
        $this->toughness = new Toughness(
            $this->getStrength(), $race->getRaceCode(), $race->getSubraceCode(), $tables
        );
        $this->endurance = new Endurance($this->getStrength(), $this->getWill());
        $this->speed = new Speed($this->getStrength(), $this->getAgility(), $this->getHeight());
        $this->senses = new Senses(
            $this->getKnack(),
            RaceCode::getIt($race->getRaceCode()),
            SubRaceCode::getIt($race->getSubraceCode()),
            $tables
        );
        // aspects of visage
        $this->beauty = new Beauty($this->getAgility(), $this->getKnack(), $this->getCharisma());
        $this->dangerousness = new Dangerousness($this->getStrength(), $this->getWill(), $this->getCharisma());
        $this->dignity = new Dignity($this->getIntelligence(), $this->getWill(), $this->getCharisma());

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->fightNumber = new FightNumber(
            $professionLevels->getFirstLevel()->getProfession()->getCode(),
            $this,
            $this->getHeight(),
            $tables
        );
        $this->attack = new Attack($this->getAgility());
        $this->shooting = new Shooting($this->getKnack());
        $this->defenseNumber = new DefenseNumber($this->getAgility());
        $this->defenseAgainstShooting = new DefenseNumberAgainstShooting($this->getDefenseNumber(), $this->getSize());

        $this->woundsLimit = new WoundBoundary($this->getToughness(), $tables);
        $this->fatigueLimit = new FatigueBoundary($this->getEndurance(), $tables);
    }

    /**
     * @return FirstLevelProperties
     */
    public function getFirstLevelProperties()
    {
        return $this->firstLevelProperties;
    }

    /**
     * @return NextLevelsProperties
     */
    public function getNextLevelsProperties()
    {
        return $this->nextLevelsProperties;
    }

    /**
     * @return Strength
     */
    public function getStrength()
    {
        return $this->strength;
    }

    /**
     * @return Agility
     */
    public function getAgility()
    {
        return $this->agility;
    }

    /**
     * @return Knack
     */
    public function getKnack()
    {
        return $this->knack;
    }

    /**
     * @return Will
     */
    public function getWill()
    {
        return $this->will;
    }

    /**
     * @return Intelligence
     */
    public function getIntelligence()
    {
        return $this->intelligence;
    }

    /**
     * @return Charisma
     */
    public function getCharisma()
    {
        return $this->charisma;
    }

    /**
     * @return WeightInKg
     */
    public function getWeightInKgAdjustment()
    {
        // there is no more weight adjustments than on first level
        return $this->firstLevelProperties->getFirstLevelWeightInKgAdjustment();
    }

    /**
     * @return WeightInKg
     */
    public function getWeightInKg()
    {
        // there is no more weight adjustments than on first level
        return $this->firstLevelProperties->getFirstLevelWeightInKg();
    }

    /**
     * @return HeightInCm
     */
    public function getHeightInCmAdjustment()
    {
        // there is no more height adjustments than on first level
        return $this->firstLevelProperties->getFirstLevelHeightInCmAdjustment();
    }

    /**
     * @return HeightInCm
     */
    public function getHeightInCm()
    {
        // there is no more height adjustments than on first level
        return $this->firstLevelProperties->getFirstLevelHeightInCm();
    }

    /**
     * @return Height
     */
    public function getHeight()
    {
        // there is no more height adjustments than on first level
        return $this->firstLevelProperties->getFirstLevelHeight();
    }

    /**
     * @return Age
     */
    public function getAge()
    {
        // there is no more age adjustments than on first level (yet)
        return $this->firstLevelProperties->getFirstLevelAge();
    }

    /**
     * @return Toughness
     */
    public function getToughness()
    {
        return $this->toughness;
    }

    /**
     * @return Endurance
     */
    public function getEndurance()
    {
        return $this->endurance;
    }

    /**
     * @return Size
     */
    public function getSize()
    {
        return $this->firstLevelProperties->getFirstLevelSize();
    }

    /**
     * @return Speed
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * @return Senses
     */
    public function getSenses()
    {
        return $this->senses;
    }

    /**
     * @return Beauty
     */
    public function getBeauty()
    {
        return $this->beauty;
    }

    /**
     * @return Dangerousness
     */
    public function getDangerousness()
    {
        return $this->dangerousness;
    }

    /**
     * @return Dignity
     */
    public function getDignity()
    {
        return $this->dignity;
    }

    /**
     * @return FightNumber
     */
    public function getFightNumber()
    {
        return $this->fightNumber;
    }

    /**
     * @return Attack
     */
    public function getAttack()
    {
        return $this->attack;
    }

    /**
     * @return Shooting
     */
    public function getShooting()
    {
        return $this->shooting;
    }

    /**
     * @return DefenseNumber
     */
    public function getDefenseNumber()
    {
        return $this->defenseNumber;
    }

    /**
     * @return DefenseNumberAgainstShooting
     */
    public function getDefenseAgainstShooting()
    {
        return $this->defenseAgainstShooting;
    }

    /**
     * @return WoundBoundary
     */
    public function getWoundBoundary()
    {
        return $this->woundsLimit;
    }

    /**
     * @return FatigueBoundary
     */
    public function getFatigueBoundary()
    {
        return $this->fatigueLimit;
    }

}