<?php
namespace DrdPlus\PropertiesByLevels;

use Drd\Genders\Gender;
use DrdPlus\Codes\PropertyCodes;
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
    private $firstLevelHeightInCm;
    /** @var Age */
    private $firstLevelAge;

    public function __construct(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        WeightInKg $weightInKgAdjustment,
        HeightInCm $heightInCm,
        Age $age,
        Tables $tables
    )
    {
        $this->exceptionalityProperties = $exceptionalityProperties;
        $this->setUpBaseProperties($race, $gender, $exceptionalityProperties, $professionLevels, $tables);
        $this->firstLevelWeightInKgAdjustment = $weightInKgAdjustment;
        $this->firstLevelWeightInKg = $this->createFirstLevelWeightInKg(
            $race,
            $gender,
            $weightInKgAdjustment,
            $tables
        );
        $this->firstLevelSize = $this->createFirstLevelSize(
            $race,
            $gender,
            $tables,
            $exceptionalityProperties,
            $professionLevels
        );
        $this->firstLevelHeightInCm = $heightInCm;
        $this->firstLevelAge = $age;
    }

    private function setUpBaseProperties(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        Tables $tables
    )
    {
        $propertyValues = [];
        foreach (PropertyCodes::getBasePropertyCodes() as $basePropertyCode) {
            $propertyValues[$basePropertyCode] = $this->calculateFirstLevelBaseProperty(
                $basePropertyCode,
                $race,
                $gender,
                $tables,
                $exceptionalityProperties,
                $professionLevels
            );
        }

        $this->firstLevelUnlimitedStrength = Strength::getIt($propertyValues[Strength::STRENGTH]);
        $this->firstLevelStrength = $this->getLimitedProperty($race, $gender, $tables, $this->firstLevelUnlimitedStrength);

        $this->firstLevelUnlimitedAgility = Agility::getIt($propertyValues[Agility::AGILITY]);
        $this->firstLevelAgility = $this->getLimitedProperty($race, $gender, $tables, $this->firstLevelUnlimitedAgility);

        $this->firstLevelUnlimitedKnack = Knack::getIt($propertyValues[Knack::KNACK]);
        $this->firstLevelKnack = $this->getLimitedProperty($race, $gender, $tables, $this->firstLevelUnlimitedKnack);

        $this->firstLevelUnlimitedWill = Will::getIt($propertyValues[Will::WILL]);
        $this->firstLevelWill = $this->getLimitedProperty($race, $gender, $tables, $this->firstLevelUnlimitedWill);

        $this->firstLevelUnlimitedIntelligence = Intelligence::getIt($propertyValues[Intelligence::INTELLIGENCE]);
        $this->firstLevelIntelligence = $this->getLimitedProperty($race, $gender, $tables, $this->firstLevelUnlimitedIntelligence);

        $this->firstLevelUnlimitedCharisma = Charisma::getIt($propertyValues[Charisma::CHARISMA]);
        $this->firstLevelCharisma = $this->getLimitedProperty($race, $gender, $tables, $this->firstLevelUnlimitedCharisma);
    }

    private function calculateFirstLevelBaseProperty(
        $propertyCode,
        Race $race,
        Gender $gender,
        Tables $tables,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        return
            $race->getProperty($propertyCode, $gender, $tables)
            + $exceptionalityProperties->getProperty($propertyCode)->getValue()
            + $professionLevels->getFirstLevelPropertyModifier($propertyCode);
    }

    /**
     * @param Race $race
     * @param Gender $gender
     * @param Tables $tables
     * @param BaseProperty $baseProperty
     * @return BaseProperty
     */
    private function getLimitedProperty(Race $race, Gender $gender, Tables $tables, BaseProperty $baseProperty)
    {
        $limit = $this->getBasePropertyLimit($race, $gender, $tables, $baseProperty);
        if ($baseProperty->getValue() <= $limit) {
            return $baseProperty;
        }

        return $baseProperty::getIt($limit);
    }

    /**
     * @param Race $race
     * @param Gender $gender
     * @param Tables $tables
     * @param BaseProperty $baseProperty
     *
     * @return int
     */
    private function getBasePropertyLimit(Race $race, Gender $gender, Tables $tables, BaseProperty $baseProperty)
    {
        return $race->getProperty($baseProperty->getCode(), $gender, $tables) + self::INITIAL_PROPERTY_INCREASE_LIMIT;
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

    private function createFirstLevelWeightInKg(
        Race $race,
        Gender $gender,
        WeightInKg $weightInKgAdjustment,
        Tables $tables
    )
    {
        return WeightInKg::getIt($race->getWeightInKg($gender, $tables) + $weightInKgAdjustment->getValue());
    }

    private function createFirstLevelSize(
        Race $race,
        Gender $gender,
        Tables $tables,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        $sizeValue = $this->calculateFirstLevelSize(
            $race,
            $gender,
            $tables,
            $exceptionalityProperties,
            $professionLevels
        );

        return new Size($sizeValue);
    }

    private function calculateFirstLevelSize(
        Race $race,
        Gender $gender,
        Tables $tables,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        $strengthModifierSummary = $this->getStrengthModifierSummary($exceptionalityProperties, $professionLevels);
        $sizeModifierByStrength = $this->getSizeModifierByStrength($strengthModifierSummary);
        $raceSize = $race->getSize($gender, $tables);

        return $raceSize + $sizeModifierByStrength;
    }

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

    private function getStrengthModifierSummary(ExceptionalityProperties $exceptionalityProperties, ProfessionLevels $professionLevels)
    {
        return // the race bonus is NOT count for adjustment, doesn't count to size change respectively
            $exceptionalityProperties->getStrength()->getValue()
            + $professionLevels->getFirstLevelStrengthModifier();
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
    public function getFirstLevelHeightInCm()
    {
        return $this->firstLevelHeightInCm;
    }

    /**
     * @return Age
     */
    public function getFirstLevelAge()
    {
        return $this->firstLevelAge;
    }

}
