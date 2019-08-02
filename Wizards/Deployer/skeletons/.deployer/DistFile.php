<?php
namespace Deployer;

/**
 * Class DistFile
 *
 * Lehet definiálni dist fájlokat. Amennyiben a célfájl útvonal első vagy második karaktere lehet speciális:
 *  - `@` : A forrásfájlban lehet (Deployer-es) helyőrző és a "másolás" abból történik, a parse() segítségével
 *  - `!` : Mindenképpen felülírja, ha létezik a fájl már, ha nem
 *
 * <code>
 *      $distFile = new DistFile('.deployer/dist/.wf.yml', '!@.wf.yml');
 * </code>
 *
 * @package Deployer
 */
class DistFile
{
    /**
     * Mindenképpen felülírja a célfájlt, ha létezik.
     */
    const FORCE_SIGN = '!';

    /**
     * A tartalmat másolja, így lehet használni helyőrzőket.
     */
    const PARSE_CONTENT_SIGN = '@';

    /**
     * @var string
     */
    protected $distFile;

    /**
     * @var string
     */
    protected $targetFile;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @var bool
     */
    protected $parseContent = false;

    public function __construct($distFile, $targetFile)
    {
        $this->distFile = $distFile;

        // Megnézi az első két karaktert, hogy az vmilyen módosító-e!
        $signs = 0;
        if ($targetFile[0] == self::FORCE_SIGN || $targetFile[1] == self::FORCE_SIGN) {
            $signs++;
            $this->force = true;
        }
        if ($targetFile[0] == self::PARSE_CONTENT_SIGN || $targetFile[1] == self::PARSE_CONTENT_SIGN) {
            $signs++;
            $this->parseContent = true;
        }
        $this->targetFile = substr($targetFile, $signs);
    }

    /**
     * @return string
     */
    public function getDistFile(): string
    {
        return $this->distFile;
    }

    /**
     * @return string
     */
    public function getTargetFile(): string
    {
        return $this->targetFile;
    }

    /**
     * @return bool
     */
    public function isForce(): bool
    {
        return $this->force;
    }

    /**
     * @return bool
     */
    public function isParseContent(): bool
    {
        return $this->parseContent;
    }
}
