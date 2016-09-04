<?php
namespace DrdPlus\PropertiesByLevels;

use DrdPlus\Person\ProfessionLevels\ProfessionLevels;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Base\Charisma;
use DrdPlus\Properties\Base\Intelligence;
use DrdPlus\Properties\Base\Knack;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use Granam\Strict\Object\StrictObject;

class NextLevelsProperties extends StrictObject
{
    /** @var Strength */
    private $nextLevelsStrength;
    /** @var Agility */
    private $nextLevelsAgility;
    /** @var Knack */
    private $nextLevelsKnack;
    /** @var Will */
    private $nextLevelsWill;
    /** @var Intelligence */
    private $nextLevelsIntelligence;
    /** @var Charisma */
    private $nextLevelsCharisma;

    public function __construct(ProfessionLevels $professionLevels)
    {
        $this->nextLevelsStrength = Strength::getIt($professionLevels->getNextLevelsStrengthModifier());
        $this->nextLevelsAgility = Agility::getIt($professionLevels->getNextLevelsAgilityModifier());
        $this->nextLevelsKnack = Knack::getIt($professionLevels->getNextLevelsKnackModifier());
        $this->nextLevelsWill = Will::getIt($professionLevels->getNextLevelsWillModifier());
        $this->nextLevelsIntelligence = Intelligence::getIt($professionLevels->getNextLevelsIntelligenceModifier());
        $this->nextLevelsCharisma = Charisma::getIt($professionLevels->getNextLevelsCharismaModifier());
    }

    /**
     * @return Strength
     */
    public function getNextLevelsStrength()
    {
        return $this->nextLevelsStrength;
    }

    /**
     * @return Agility
     */
    public function getNextLevelsAgility()
    {
        return $this->nextLevelsAgility;
    }

    /**
     * @return Knack
     */
    public function getNextLevelsKnack()
    {
        return $this->nextLevelsKnack;
    }

    /**
     * @return Will
     */
    public function getNextLevelsWill()
    {
        return $this->nextLevelsWill;
    }

    /**
     * @return Intelligence
     */
    public function getNextLevelsIntelligence()
    {
        return $this->nextLevelsIntelligence;
    }

    /**
     * @return Charisma
     */
    public function getNextLevelsCharisma()
    {
        return $this->nextLevelsCharisma;
    }
}