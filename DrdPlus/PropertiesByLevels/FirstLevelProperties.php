<?php
namespace DrdPlus\PropertiesByLevels;

use DrdPlus\Codes\GenderCode;
use DrdPlus\Codes\PropertyCode;
use DrdPlus\Exceptionalities\Properties\ExceptionalityProperties;
use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\BaseProperty;
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
use DrdPlus\Races\Race;
use DrdPlus\Tables\Tables;
use Granam\Strict\Object\StrictObject;

class FirstLevelProperties extends StrictObject
{
    const INITIAL_PROPERTY_INCREASE_LIMIT = 3;

    /** @var ExceptionalityProperties */
    private $exceptionalityProperties;
    /** @var Strength */
    private $firstLevelUnlimitedStrength;
    /** @var Strength */
    private $firstLevelStrength;
    /** @var Agility */
    private $firstLevelUnlimitedAgility;
    /** @var Agility */
    private $firstLevelAgility;
    /** @var Knack */
    private $firstLevelUnlimitedKnack;
    /** @var Knack */
    private $firstLevelKnack;
    /** @var Will */
    private $firstLevelUnlimitedWill;
    /** @var Will */
    private $firstLevelWill;
    /** @var Intelligence */
    private $firstLevelUnlimitedIntelligence;
    /** @var Intelligence */
    private $firstLevelIntelligence;
    /** @var Charisma */
    private $firstLevelUnlimitedCharisma;
    /** @var Charisma */
    private $firstLevelCharisma;
    /** @var WeightInKg */
    private $firstLevelWeightInKgAdjustment;
    /** @var WeightInKg */
    private $firstLevelWeightInKg;
    /** @var Size */
    private $firstLevelSize;
    /** @var HeightInCm */
    private $firstLevelHeightInCmAdjustment;
    /** @var HeightInCm */
    private $firstLevelHeightInCm;
    /** @var Height */
    private $firstLevelHeight;
    /** @var Age */
    private $firstLevelAge;

    /**
     * @param Race $race
     * @param GenderCode $genderCode
     * @param ExceptionalityProperties $exceptionalityProperties
     * @param ProfessionLevels $professionLevels
     * @param WeightInKg $weightInKgAdjustment
     * @param HeightInCm $heightInCmAdjustment
     * @param Age $age
     * @param Tables $tables
     * @throws Exceptions\TooLowStrengthAdjustment
     */
    public function __construct(
        Race $race,
        GenderCode $genderCode,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        WeightInKg $weightInKgAdjustment,
        HeightInCm $heightInCmAdjustment,
        Age $age,
        Tables $tables
    )
    {
        $this->exceptionalityProperties = $exceptionalityProperties;
        $this->setUpBaseProperties($race, $genderCode, $exceptionalityProperties, $professionLevels, $tables);
        $this->firstLevelWeightInKgAdjustment = $weightInKgAdjustment;
        $this->firstLevelWeightInKg = $this->createFirstLevelWeightInKg(
            $race,
            $genderCode,
            $weightInKgAdjustment,
            $tables
        );
        $this->firstLevelSize = $this->createFirstLevelSize(
            $race,
            $genderCode,
            $tables,
            $exceptionalityProperties,
            $professionLevels
        );
        $this->firstLevelHeightInCmAdjustment = $heightInCmAdjustment;
        $this->firstLevelHeightInCm = HeightInCm::getIt(
            $race->getHeightInCm($tables->getRacesTable()) + $heightInCmAdjustment->getValue()
        );
        $this->firstLevelHeight = new Height($this->firstLevelHeightInCm, $tables->getDistanceTable());
        $this->firstLevelAge = $age;
    }

    private function setUpBaseProperties(
        Race $race,
        GenderCode $genderCode,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        Tables $tables
    )
    {
        $propertyValues = [];
        foreach (PropertyCode::getBasePropertyPossibleValues() as $basePropertyCode) {
            $propertyValues[$basePropertyCode] = $this->calculateFirstLevelBaseProperty(
                $basePropertyCode,
                $race,
                $genderCode,
                $tables,
                $exceptionalityProperties,
                $professionLevels
            );
        }

        $this->firstLevelUnlimitedStrength = Strength::getIt($propertyValues[Strength::STRENGTH]);
        $this->firstLevelStrength = $this->getLimitedProperty($race, $genderCode, $tables, $this->firstLevelUnlimitedStrength);

        $this->firstLevelUnlimitedAgility = Agility::getIt($propertyValues[Agility::AGILITY]);
        $this->firstLevelAgility = $this->getLimitedProperty($race, $genderCode, $tables, $this->firstLevelUnlimitedAgility);

        $this->firstLevelUnlimitedKnack = Knack::getIt($propertyValues[Knack::KNACK]);
        $this->firstLevelKnack = $this->getLimitedProperty($race, $genderCode, $tables, $this->firstLevelUnlimitedKnack);

        $this->firstLevelUnlimitedWill = Will::getIt($propertyValues[Will::WILL]);
        $this->firstLevelWill = $this->getLimitedProperty($race, $genderCode, $tables, $this->firstLevelUnlimitedWill);

        $this->firstLevelUnlimitedIntelligence = Intelligence::getIt($propertyValues[Intelligence::INTELLIGENCE]);
        $this->firstLevelIntelligence = $this->getLimitedProperty($race, $genderCode, $tables, $this->firstLevelUnlimitedIntelligence);

        $this->firstLevelUnlimitedCharisma = Charisma::getIt($propertyValues[Charisma::CHARISMA]);
        $this->firstLevelCharisma = $this->getLimitedProperty($race, $genderCode, $tables, $this->firstLevelUnlimitedCharisma);
    }

    /**
     * @param string $propertyCode
     * @param Race $race
     * @param GenderCode $genderCode
     * @param Tables $tables
     * @param ExceptionalityProperties $exceptionalityProperties
     * @param ProfessionLevels $professionLevels
     * @return int
     * @throws \DrdPlus\Races\Exceptions\UnknownPropertyCode
     */
    private function calculateFirstLevelBaseProperty(
        $propertyCode,
        Race $race,
        GenderCode $genderCode,
        Tables $tables,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        return
            $race->getProperty($propertyCode, $genderCode, $tables)
            + $exceptionalityProperties->getProperty($propertyCode)->getValue()
            + $professionLevels->getFirstLevelPropertyModifier($propertyCode);
    }

    /**
     * @param Race $race
     * @param GenderCode $genderCode
     * @param Tables $tables
     * @param BaseProperty $baseProperty
     * @return BaseProperty
     * @throws \DrdPlus\Races\Exceptions\UnknownPropertyCode
     */
    private function getLimitedProperty(Race $race, GenderCode $genderCode, Tables $tables, BaseProperty $baseProperty)
    {
        $limit = $this->getBasePropertyLimit($race, $genderCode, $tables, $baseProperty);
        if ($baseProperty->getValue() <= $limit) {
            return $baseProperty;
        }

        return $baseProperty::getIt($limit);
    }

    /**
     * @param Race $race
     * @param GenderCode $genderCode
     * @param Tables $tables
     * @param BaseProperty $baseProperty
     * @return int
     * @throws \DrdPlus\Races\Exceptions\UnknownPropertyCode
     */
    private function getBasePropertyLimit(Race $race, GenderCode $genderCode, Tables $tables, BaseProperty $baseProperty)
    {
        return $race->getProperty($baseProperty->getCode(), $genderCode, $tables) + self::INITIAL_PROPERTY_INCREASE_LIMIT;
    }

    /**
     * @return ExceptionalityProperties
     */
    public function getExceptionalityProperties()
    {
        return $this->exceptionalityProperties;
    }

    /**
     * @return int 0+
     */
    public function getStrengthLossBecauseOfLimit()
    {
        return $this->firstLevelUnlimitedStrength->getValue() - $this->getFirstLevelStrength()->getValue();
    }

    /**
     * @return int 0+
     */
    public function getAgilityLossBecauseOfLimit()
    {
        return $this->firstLevelUnlimitedAgility->getValue() - $this->getFirstLevelAgility()->getValue();
    }

    /**
     * @return int 0+
     */
    public function getKnackLossBecauseOfLimit()
    {
        return $this->firstLevelUnlimitedKnack->getValue() - $this->getFirstLevelKnack()->getValue();
    }

    /**
     * @return int 0+
     */
    public function getWillLossBecauseOfLimit()
    {
        return $this->firstLevelUnlimitedWill->getValue() - $this->getFirstLevelWill()->getValue();
    }

    /**
     * @return int 0+
     */
    public function getIntelligenceLossBecauseOfLimit()
    {
        return $this->firstLevelUnlimitedIntelligence->getValue() - $this->getFirstLevelIntelligence()->getValue();
    }

    /**
     * @return int 0+
     */
    public function getCharismaLossBecauseOfLimit()
    {
        return $this->firstLevelUnlimitedCharisma->getValue() - $this->getFirstLevelCharisma()->getValue();
    }

    /**
     * @param Race $race
     * @param GenderCode $genderCode
     * @param WeightInKg $weightInKgAdjustment
     * @param Tables $tables
     * @return WeightInKg
     */
    private function createFirstLevelWeightInKg(
        Race $race,
        GenderCode $genderCode,
        WeightInKg $weightInKgAdjustment,
        Tables $tables
    )
    {
        return WeightInKg::getIt($race->getWeightInKg($genderCode, $tables) + $weightInKgAdjustment->getValue());
    }

    /**
     * @param Race $race
     * @param GenderCode $genderCode
     * @param Tables $tables
     * @param ExceptionalityProperties $exceptionalityProperties
     * @param ProfessionLevels $professionLevels
     * @return Size
     * @throws Exceptions\TooLowStrengthAdjustment
     */
    private function createFirstLevelSize(
        Race $race,
        GenderCode $genderCode,
        Tables $tables,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        // the race bonus is NOT count for adjustment, doesn't count to size change respectively
        $sizeModifierByStrength = $this->getSizeModifierByStrength(
            $exceptionalityProperties->getStrength()->getValue()
            + $professionLevels->getFirstLevelStrengthModifier()
        );
        $raceSize = $race->getSize($genderCode, $tables);

        return Size::getIt($raceSize + $sizeModifierByStrength);
    }

    /**
     * @param $firstLevelStrengthAdjustment
     * @return int
     * @throws Exceptions\TooLowStrengthAdjustment
     */
    private function getSizeModifierByStrength($firstLevelStrengthAdjustment)
    {
        if ($firstLevelStrengthAdjustment === 0) {
            return -1;
        }
        if ($firstLevelStrengthAdjustment === 1) {
            return 0;
        }
        if ($firstLevelStrengthAdjustment >= 2) {
            return +1;
        }
        throw new Exceptions\TooLowStrengthAdjustment(
            'First level strength adjustment can not be lesser than zero. Given ' . $firstLevelStrengthAdjustment
        );
    }

    /**
     * @return Strength
     */
    public function getFirstLevelStrength()
    {
        return $this->firstLevelStrength;
    }

    /**
     * @return Agility
     */
    public function getFirstLevelAgility()
    {
        return $this->firstLevelAgility;
    }

    /**
     * @return Knack
     */
    public function getFirstLevelKnack()
    {
        return $this->firstLevelKnack;
    }

    /**
     * @return Will
     */
    public function getFirstLevelWill()
    {
        return $this->firstLevelWill;
    }

    /**
     * @return Intelligence
     */
    public function getFirstLevelIntelligence()
    {
        return $this->firstLevelIntelligence;
    }

    /**
     * @return Charisma
     */
    public function getFirstLevelCharisma()
    {
        return $this->firstLevelCharisma;
    }

    /**
     * @return WeightInKg
     */
    public function getFirstLevelWeightInKgAdjustment()
    {
        return $this->firstLevelWeightInKgAdjustment;
    }

    /**
     * @return WeightInKg
     */
    public function getFirstLevelWeightInKg()
    {
        return $this->firstLevelWeightInKg;
    }

    /**
     * @return Size
     */
    public function getFirstLevelSize()
    {
        return $this->firstLevelSize;
    }

    /**
     * @return HeightInCm
     */
    public function getFirstLevelHeightInCmAdjustment()
    {
        return $this->firstLevelHeightInCmAdjustment;
    }

    /**
     * @return HeightInCm
     */
    public function getFirstLevelHeightInCm()
    {
        return $this->firstLevelHeightInCm;
    }

    /**
     * @return Height
     */
    public function getFirstLevelHeight()
    {
        return $this->firstLevelHeight;
    }

    /**
     * @return Age
     */
    public function getFirstLevelAge()
    {
        return $this->firstLevelAge;
    }

}