<?php
namespace DrdPlus\PersonProperties;

use Drd\Genders\Gender;
use DrdPlus\Exceptionalities\ExceptionalityProperties;
use DrdPlus\ProfessionLevels\ProfessionLevels;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\BaseProperty;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Body\Size;
use DrdPlus\Properties\Body\WeightInKg;
use DrdPlus\Races\Race;
use DrdPlus\Tables\Tables;
use Granam\Strict\Object\StrictObject;

class FirstLevelProperties extends StrictObject
{
    const INITIAL_PROPERTY_INCREASE_LIMIT = 3;

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
    private $firstLevelWeightInKg;
    /** @var Size */
    private $firstLevelSize;

    public function __construct(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        Tables $tables,
        WeightInKg $weightInKgAdjustment
    )
    {
        $this->setUpFirstLevelProperties(
            $race,
            $gender,
            $exceptionalityProperties,
            $professionLevels,
            $tables,
            $weightInKgAdjustment
        );
    }

    private function setUpFirstLevelProperties(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        Tables $tables,
        WeightInKg $weightInKgAdjustment
    )
    {
        foreach ($this->getBaseProperties() as $propertyCode => $propertyClass) {
            /** @var BaseProperty $propertyClass */
            $firstLevelProperty = $propertyClass::getIt($this->calculateFirstLevelBaseProperty(
                $propertyCode,
                $race,
                $gender,
                $tables,
                $exceptionalityProperties,
                $professionLevels
            ));
            $this->setUpBaseProperty($race, $gender, $tables, $firstLevelProperty);
        }
        $this->firstLevelWeightInKg = $this->createFirstLevelWeightInKg(
            $race,
            $gender,
            $tables,
            $weightInKgAdjustment
        );
        $this->firstLevelSize = $this->createFirstLevelSize(
            $race,
            $gender,
            $tables,
            $exceptionalityProperties,
            $professionLevels
        );
    }

    private function getBaseProperties()
    {
        return [
            Strength::STRENGTH => Strength::class,
            Agility::AGILITY => Agility::class,
            Knack::KNACK => Knack::class,
            Will::WILL => Will::class,
            Intelligence::INTELLIGENCE => Intelligence::class,
            Charisma::CHARISMA => Charisma::class
        ];
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
            + $professionLevels->getPropertyModifierForFirstProfession($propertyCode);
    }

    /**
     * @param Race $race
     * @param Gender $gender
     * @param Tables $tables
     * @param BaseProperty $firstLevelUnlimitedProperty
     */
    private function setUpBaseProperty(
        Race $race,
        Gender $gender,
        Tables $tables,
        BaseProperty $firstLevelUnlimitedProperty
    )
    {
        $firstLevelLimitedProperty = $this->getLimitedProperty(
            $race,
            $gender,
            $tables,
            $firstLevelUnlimitedProperty
        );
        switch ($firstLevelUnlimitedProperty->getCode()) {
            case Strength::STRENGTH :
                $this->firstLevelUnlimitedStrength = $firstLevelUnlimitedProperty;
                $this->firstLevelStrength = $firstLevelLimitedProperty;
                break;
            case Agility::AGILITY :
                $this->firstLevelUnlimitedAgility = $firstLevelUnlimitedProperty;
                $this->firstLevelAgility = $firstLevelLimitedProperty;
                break;
            case Knack::KNACK :
                $this->firstLevelUnlimitedKnack = $firstLevelUnlimitedProperty;
                $this->firstLevelKnack = $firstLevelLimitedProperty;
                break;
            case Will::WILL :
                $this->firstLevelUnlimitedWill = $firstLevelUnlimitedProperty;
                $this->firstLevelWill = $firstLevelLimitedProperty;
                break;
            case Intelligence::INTELLIGENCE :
                $this->firstLevelUnlimitedIntelligence = $firstLevelUnlimitedProperty;
                $this->firstLevelIntelligence = $firstLevelLimitedProperty;
                break;
            case Charisma::CHARISMA :
                $this->firstLevelUnlimitedCharisma = $firstLevelUnlimitedProperty;
                $this->firstLevelCharisma = $firstLevelLimitedProperty;
                break;
            default :
                throw new \LogicException;
        }
    }

    /**
     * @param Race $race
     * @param Gender $gender
     * @param Tables $tables
     * @param BaseProperty $baseProperty
     * @return BaseProperty
     */
    private function getLimitedProperty(
        Race $race,
        Gender $gender,
        Tables $tables,
        BaseProperty $baseProperty
    )
    {
        $limit = $this->calculateMaximalBasePropertyValue($race, $gender, $tables, $baseProperty->getCode());
        if ($baseProperty->getValue() <= $limit) {
            return $baseProperty;
        }

        return $baseProperty::getIt($limit);
    }

    /**
     * @param Race $race
     * @param Gender $gender
     * @param Tables $tables
     * @param string $propertyCode
     *
     * @return int
     */
    private function calculateMaximalBasePropertyValue(
        Race $race,
        Gender $gender,
        Tables $tables,
        $propertyCode
    )
    {
        return $race->getProperty($propertyCode, $gender, $tables) + self::INITIAL_PROPERTY_INCREASE_LIMIT;
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
        Tables $tables,
        WeightInKg $weightInKgAdjustment
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
        return new Size($this->calculateFirstLevelSize(
            $race,
            $gender,
            $tables,
            $exceptionalityProperties,
            $professionLevels
        ));
    }

    private function calculateFirstLevelSize(
        Race $race,
        Gender $gender,
        Tables $tables,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        return
            $race->getSize($gender, $tables)
            + $this->getSizeModifierByStrength(
                $this->getStrengthModifierSummary($exceptionalityProperties, $professionLevels)
            );
    }

    private function getSizeModifierByStrength($firstLevelStrengthAdjustment)
    {
        if ($firstLevelStrengthAdjustment === 0) {
            return -1;
        }
        if ($firstLevelStrengthAdjustment >= 2) {
            return +1;
        }
        if ($firstLevelStrengthAdjustment === 1) {
            return 0;
        }
        throw new \LogicException('FirstLevel strength adjustment can not be lesser than zero. Given ' . $firstLevelStrengthAdjustment);
    }

    private function getStrengthModifierSummary(ExceptionalityProperties $exceptionalityProperties, ProfessionLevels $professionLevels)
    {
        return // the race bonus is NOT count for adjustment, doesn't count to size change respectively
            $exceptionalityProperties->getStrength()->getValue()
            + $professionLevels->getStrengthModifierForFirstProfession();
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
}
