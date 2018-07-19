<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Metadata;

use Metadata\ClassMetadata as BaseClassMetadata;

class ClassMetadata extends BaseClassMetadata
{
    public $preExtractMethods;

    /**
     * Elastic search index name and settings
     *
     * @var array | null
     */
    protected $index;

    /**
     * Class name of custom repository. Leave null to use default elastic search repository
     *
     * @var string | null
     */
    protected $customRepositoryName;

    /**
     * Serialize the metadata object. Useful for caching
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->preExtractMethods,
            parent::serialize(),
        ));
    }

    /**
     * Unserialize the metadata
     *
     * @param $str
     */
    public function unserialize($str)
    {
        $unserialized = unserialize($str);

        [$this->preExtractMethods, $parentStr] = $unserialized;

        parent::unserialize($parentStr);
    }

    /**
     * @return null|string
     */
    public function getCustomRepositoryName(): ?string
    {
        return $this->customRepositoryName;
    }

    /**
     * @param null|string $customRepositoryName
     */
    public function setCustomRepositoryName(?string $customRepositoryName): void
    {
        $this->customRepositoryName = $customRepositoryName;
    }

    /**
     * @return array|null
     */
    public function getIndex(): ?array
    {
        return $this->index;
    }

    /**
     * @param array|null $index
     */
    public function setIndex(?array $index): void
    {
        $this->index = $index;
    }
}
