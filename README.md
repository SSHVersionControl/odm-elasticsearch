# ODM Elasticsearch Library

Elasticsearch Object Document Mapper for PHP. Sits on top of Elastica, and uses the visitor pattern to help map
data types to and from Elasticsearch. Gives full control over which properties you wish to map to Elasticsearch, and also
supports virtual properties. 

## Installation

```bash
composer require 
```


## Usage
The entity class you wish to persist to elasticsearch must implement the DocumentSupportInterface. 
Create a yaml file with configuration for the entity, and sub objects if required. See yaml reference 

Initialize the repositoryFactory. Recommend using a service container
```php

// Create file Locator of entity yaml config. Array key must be the the namespace of entity 
// and value the directory containing the yaml files 
$fileLocator = new FileLocator(
    ['CCT\Component\ODMElasticsearch\Tests\Fixture' => __DIR__ . $configDir]
);

// Create yaml driver
$yamlDriver = new YamlDriver($fileLocator);

// Create Metadata Factory
$metadataFactory = new MetadataFactory($yamlDriver);

// Create Elastica Client See http://elastica.io/getting-started/installation.html
$client = new \Elastica\Client();

// Create index mapping
$indexMapping = new IndexMapping($client, $metadataFactory);

// Create data navigator
$dataNavigator = new DataNavigator($metadataFactory);

// Create visitors
$visitor = new ElasticsearchVisitor();
$reverseVisitor = new ReverseElasticsearchVisitor();

// Create data transformer
$dataTransformer = new ElasticsearchTransformer($dataNavigator, $visitor, $reverseVisitor);

// Create repository factory
$repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);
```

To use, get entity repository from RepositoryFactory and use repository functions to save, find etc.
 
```php
// Create entity
$entity = new Entity();
$entity->setId(1);
$entity->setMessage('Hello this is so easy, cough :-|');

// Get repository for entity
$entityRepository = $repositoryFactory->getRepository(Entity::class);

// Save entity to elasticsearch
$entityRepository->save($entity);

// Retrieve entity from elasticsearch 
$newEntity = $entityRepository->findById(1);

```

## Yaml Reference
```yaml
Vendor\Model\ClassName:
  exposeAll: false                        # Boolean to expose all properties or just configured ones (optional) default: false
  customRepositoryName: Namespace\Of\Repo # Namespace of custom repository (optional) default: ElasticsearchRepository
  index:                                  # Index settings for elasticsearch, see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/index-modules.html (required for toplevel objects).
    name: test          
    settings:
      number_of_shards: 3
      number_of_replicas: 2
  properties:                             # Individual property configuration
    name:                                 # Name of the property 
      type: string                        # Type of the property
      field_name: name_in_es              # Name of the field in Elasticsearch that will store the property value. Leave empty to use property name  
      type_class: Namespace\Of\Class      # If type is object you can set the type_class it will 
      use_default_accessors: true         # Use class getters setters eg getPropertyName 
      accessor:                           # If accessor has a different setter and get name set them here
        getter:
        setter:
      mapping:                            # Elasticsearch index type mapping for initial creation of index.
        type: string
```
