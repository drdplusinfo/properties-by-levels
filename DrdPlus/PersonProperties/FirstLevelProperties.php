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
use DrdPlus\Tables\Measurements\Weight\WeightTable;
use DrdPlus\Tables\Races\FemaleModifiersTable;
use DrdPlus\Tables\Races\RacesTable;
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
            $tables->getRacesTable(),
            $tables->getFemaleModifiersTable(),
            $tables->getWeightTable(),
            $weightInKgAdjustment
        );
    }

    private function setUpFirstLevelProperties(
        Race $race,
        Gender $gender,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels,
        RacesTable $racesTable,
        FemaleModifiersTable $femaleModifiersTable,
        WeightTable $weightTable,
        WeightInKg $weightInKgAdjustment
    )
    {
        foreach ($this->getBaseProperties() as $propertyCode => $propertyClass) {
            /** @var BaseProperty $propertyClass */
            $firstLevelProperty = $propertyClass::getIt($this->calculateFirstLevelBaseProperty(
                $propertyCode,
                $race,
                $racesTable,
                $gender,
                $femaleModifiersTable,
                $exceptionalityProperties,
                $professionLevels
            ));
            $this->setUpBaseProperty($race, $racesTable, $gender, $femaleModifiersTable, $firstLevelProperty);
        }
        $this->firstLevelWeightInKg = $this->createFirstLevelWeightInKg(
            $race,
            $racesTable,
            $gender,
            $femaleModifiersTable,
            $weightTable,
            $weightInKgAdjustment
        );
        $this->firstLevelSize = $this->createFirstLevelSize(
            $race,
            $racesTable,
            $gender,
            $femaleModifiersTable,
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
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        return
            $this->getRaceBasePropertyModifier($race, $racesTable, $gender, $femaleModifiersTable, $propertyCode)
            + $this->getExceptionalPropertyAdjustment($propertyCode, $exceptionalityProperties)->getValue()
            + $this->getPropertyModifierForFirstProfession($professionLevels, $propertyCode);
    }

    private function getRaceBasePropertyModifier(
        Race $race,
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        $propertyCode
    )
    {
        switch ($propertyCode) {
            case Strength::STRENGTH :
                return $race->getStrength($racesTable, $gender, $femaleModifiersTable);
            case Agility::AGILITY :
                return $race->getAgility($racesTable, $gender, $femaleModifiersTable);
            case Knack::KNACK :
                return $race->getKnack($racesTable, $gender, $femaleModifiersTable);
            case Will::WILL :
                return $race->getWill($racesTable, $gender, $femaleModifiersTable);
            case Intelligence::INTELLIGENCE :
                return $race->getIntelligence($racesTable, $gender, $femaleModifiersTable);
            case Charisma::CHARISMA :
            default :
                return $race->getCharisma($racesTable, $gender, $femaleModifiersTable);
        }
    }

    private function getPropertyModifierForFirstProfession(ProfessionLevels $professionLevels, $propertyCode)
    {
        switch ($propertyCode) {
            case Strength::STRENGTH :
                return $professionLevels->getStrengthModifierForFirstProfession();
            case Agility::AGILITY :
                return $professionLevels->getAgilityModifierForFirstProfession();
            case Knack::KNACK :
                return $professionLevels->getKnackModifierForFirstProfession();
            case Will::WILL :
                return $professionLevels->getWillModifierForFirstProfession();
            case Intelligence::INTELLIGENCE :
                return $professionLevels->getIntelligenceModifierForFirstProfession();
            case Charisma::CHARISMA :
            default :
                return $professionLevels->getCharismaModifierForFirstProfession();
        }
    }

    /**
     * @param $propertyCode
     * @param ExceptionalityProperties $exceptionalityProperties
     *
     * @return BaseProperty
     */
    private function getExceptionalPropertyAdjustment($propertyCode, ExceptionalityProperties $exceptionalityProperties)
    {
        switch ($propertyCode) {
            case Strength::STRENGTH :
                return $exceptionalityProperties->getStrength();
            case Agility::AGILITY :
                return $exceptionalityProperties->getAgility();
            case Knack::KNACK :
                return $exceptionalityProperties->getKnack();
            case Will::WILL :
                return $exceptionalityProperties->getWill();
            case Intelligence::INTELLIGENCE :
                return $exceptionalityProperties->getIntelligence();
            case Charisma::CHARISMA :
            default :
                return $exceptionalityProperties->getCharisma();
        }
    }

    /**
     * @param Race $race
     * @param RacesTable $racesTable
     * @param Gender $gender
     * @param FemaleModifiersTable $femaleModifiersTable
     * @param BaseProperty $firstLevelUnlimitedProperty
     */
    private function setUpBaseProperty(
        Race $race,
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        BaseProperty $firstLevelUnlimitedProperty
    )
    {
        $firstLevelLimitedProperty = $this->getLimitedProperty(
            $race,
            $racesTable,
            $gender,
            $femaleModifiersTable,
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
            default :
                $this->firstLevelUnlimitedCharisma = $firstLevelUnlimitedProperty;
                $this->firstLevelCharisma = $firstLevelLimitedProperty;
        }
    }

    /**
     * @param Race $race
     * @param RacesTable $racesTable
     * @param Gender $gender
     * @param FemaleModifiersTable $femaleModifiersTable
     * @param BaseProperty $baseProperty
     * @return BaseProperty
     */
    private function getLimitedProperty(
        Race $race,
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        BaseProperty $baseProperty
    )
    {
        $limit = $this->calculateMaximalBasePropertyValue(
            $race,
            $racesTable,
            $gender,
            $femaleModifiersTable,
            $baseProperty->getCode()
        );
        if ($baseProperty->getValue() <= $limit) {
            return $baseProperty;
        }

        return $baseProperty::getIt($limit);
    }

    /**
     * @param Race $race
     * @param RacesTable $racesTable
     * @param Gender $gender
     * @param FemaleModifiersTable $femaleModifiersTable
     * @param string $propertyCode
     *
     * @return int
     */
    private function calculateMaximalBasePropertyValue(
        Race $race,
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        $propertyCode
    )
    {
        return
            $this->getRaceBasePropertyModifier($race, $racesTable, $gender, $femaleModifiersTable, $propertyCode)
            + self::INITIAL_PROPERTY_INCREASE_LIMIT;
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
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        WeightTable $weightTable,
        WeightInKg $weightInKgAdjustment
    )
    {
        return WeightInKg::getIt(
            $race->getWeightInKg($racesTable, $gender, $femaleModifiersTable, $weightTable)
            + $weightInKgAdjustment->getValue()
        );
    }

    private function createFirstLevelSize(
        Race $race,
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        return new Size($this->calculateFirstLevelSize(
            $race,
            $racesTable,
            $gender,
            $femaleModifiersTable,
            $exceptionalityProperties,
            $professionLevels
        ));
    }

    private function calculateFirstLevelSize(
        Race $race,
        RacesTable $racesTable,
        Gender $gender,
        FemaleModifiersTable $femaleModifiersTable,
        ExceptionalityProperties $exceptionalityProperties,
        ProfessionLevels $professionLevels
    )
    {
        return
            $race->getSize($racesTable, $gender, $femaleModifiersTable)
            + $this->getSizeModifierByStrength($this->getStrengthModifierSummary($exceptionalityProperties, $professionLevels));
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
