<?php
namespace DrdPlus\PersonProperties;

use Drd\Genders\Gender;
use DrdPlus\Exceptionalities\Properties\ExceptionalityProperties;
use DrdPlus\GameCharacteristics\Combat\Attack;
use DrdPlus\GameCharacteristics\Combat\BasePropertiesInterface;
use DrdPlus\GameCharacteristics\Combat\Defense;
use DrdPlus\GameCharacteristics\Combat\DefenseAgainstShooting;
use DrdPlus\GameCharacteristics\Combat\Fight;
use DrdPlus\GameCharacteristics\Combat\Shooting;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Body\HeightInCm;
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
use DrdPlus\Races\Race;
use DrdPlus\Tables\Tables;
use Granam\Strict\Object\StrictObject;

class PersonProperties extends StrictObject implements BasePropertiesInterface
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

    /** @var WeightInKg */
    private $weightInKg;

    /** @var HeightInCm */
    private $heightInCm;

    /** @var Toughness */
    private $toughness;

    /** @var Endurance */
    private $endurance;

    /** @var Size */
    private $size;

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

    /** @var Fight */
    private $fight;

    /** @var Attack */
    private $attack;

    /** @var Shooting */
    private $shooting;

    /** @var Defense */
    private $defense;

    /** @var DefenseAgainstShooting */
    private $defenseAgainstShooting;

    /** @var WoundsLimit */
    private $woundsLimit;

    /** @var FatigueLimit */
    private $fatigueLimit;

    public function __construct(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        WeightInKg $weightInKgAdjustment,
        HeightInCm $heightInCm,
        Tables $tables
    )
    {
        $this->firstLevelProperties = new FirstLevelProperties(
            $race,
            $gender,
            $exceptionalityProperties,
            $professionLevels,
            $weightInKgAdjustment,
            $heightInCm,
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
        // there is no more weight and height adjustments than on first level
        $this->weightInKg = $this->firstLevelProperties->getFirstLevelWeightInKg();
        $this->heightInCm = $this->firstLevelProperties->getFirstLevelHeightInCm();

        // delivered properties
        $this->toughness = new Toughness(
            $this->getStrength(), $race->getRaceCode(), $race->getSubraceCode(), $tables->getRacesTable()
        );
        $this->endurance = new Endurance(
            $this->getStrength(), $this->getWill()
        );
        // there is no more size adjustment than the first level one
        $this->size = $this->firstLevelProperties->getFirstLevelSize();
        $this->speed = new Speed($this->getStrength(), $this->getAgility(), $this->getSize());
        $this->senses = new Senses(
            $this->getKnack(),
            $race->getSenses($tables->getRacesTable())
        );
        // aspects of visage
        $this->beauty = new Beauty($this->getAgility(), $this->getKnack(), $this->getCharisma());
        $this->dangerousness = new Dangerousness($this->getStrength(), $this->getWill(), $this->getCharisma());
        $this->dignity = new Dignity($this->getIntelligence(), $this->getWill(), $this->getCharisma());

        $this->fight = new Fight($professionLevels->getFirstLevel()->getProfession()->getValue(), $this, $this->getSize());
        $this->attack = new Attack($this->getAgility());
        $this->shooting = new Shooting($this->getKnack());
        $this->defense = new Defense($this->getAgility());
        $this->defenseAgainstShooting = new DefenseAgainstShooting($this->getDefense(), $this->getSize());

        $this->woundsLimit = new WoundsLimit($this->getToughness(), $tables->getWoundsTable());
        $this->fatigueLimit = new FatigueLimit($this->getEndurance(), $tables->getFatigueTable());
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
    public function getWeightInKg()
    {
        return $this->weightInKg;
    }

    /**
     * @return HeightInCm
     */
    public function getHeightInCm()
    {
        return $this->heightInCm;
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
        return $this->size;
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
     * @return Fight
     */
    public function getFight()
    {
        return $this->fight;
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
     * @return Defense
     */
    public function getDefense()
    {
        return $this->defense;
    }

    /**
     * @return DefenseAgainstShooting
     */
    public function getDefenseAgainstShooting()
    {
        return $this->defenseAgainstShooting;
    }

    /**
     * @return WoundsLimit
     */
    public function getWoundsLimit()
    {
        return $this->woundsLimit;
    }

    /**
     * @return FatigueLimit
     */
    public function getFatigueLimit()
    {
        return $this->fatigueLimit;
    }

}
