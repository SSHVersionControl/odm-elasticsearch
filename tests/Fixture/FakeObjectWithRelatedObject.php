<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Fixture;

use CCT\Component\ORMElasticsearch\Repository\Model\DocumentSupportInterface;

class FakeObjectWithRelatedObject implements DocumentSupportInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    protected $wifeName;

    /**
     * @var string
     */
    private $mistressName;

    /**
     * @var bool
     */
    private $caught;

    /**
     * @var FakeObject | null
     */
    private $child;

    /**
     * @var FakeObject[] | null
     */
    private $children;

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

    /**
     * @return FakeObject | null
     */
    public function getChild(): ?FakeObject
    {
        return $this->child;
    }

    /**
     * @param FakeObject $child
     */
    public function setChild(FakeObject $child): void
    {
        $this->child = $child;
    }

    /**
     * @return FakeObject[] | null
     */
    public function getChildren(): ?array
    {
        return $this->children;
    }

    /**
     * @param FakeObject[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getId(): int
    {
        return 30;
    }
}
