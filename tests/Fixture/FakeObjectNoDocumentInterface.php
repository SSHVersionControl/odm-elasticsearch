<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Fixture;

class FakeObjectNoDocumentInterface
{
    public $name;

    protected $wifeName;

    private $mistressName;

    private $caught;

    /**
     * @return mixed
     */
    public function getWifeName()
    {
        return $this->wifeName;
    }

    /**
     * @param mixed $wifeName
     */
    public function setWifeName($wifeName): void
    {
        $this->wifeName = $wifeName;
    }

    /**
     * @return mixed
     */
    public function getMistressName()
    {
        return $this->mistressName;
    }

    /**
     * @param mixed $mistressName
     */
    public function setMistressName($mistressName): void
    {
        $this->mistressName = $mistressName;
    }

    public function setCaught(bool $caught): void
    {
        $this->caught = $caught;
    }

    public function isCaught(): bool
    {
        return $this->caught;
    }

    public function getUnknownChildren(): int
    {
        return 20;
    }

    public function divorceFee(): int
    {
        return 100;
    }

    public function getId(): int
    {
        return 20;
    }
}
