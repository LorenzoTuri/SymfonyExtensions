<?php

namespace Lturi\SymfonyExtensions\JsonApi\Entity\Processor;

use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use OpenApi\Analysis;
use OpenApi\Annotations\Info;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Context;

class OpenApiProcessor {
    protected $entitiesDescription;

    public function __construct(
        $entitiesDescription
    ) {
        $this->entitiesDescription = $entitiesDescription;
    }

    public function describe(Analysis $analysis) {
        // Attach each information that insn't already provided by the existing entities
        $this->injectBaseInfos($analysis);

        // Entities paths
        $this->injectEntitiesPaths($analysis);

        // Yet describe the classes
        $this->injectClassAnnotations($analysis);
    }

    /**
     * Inject base required info about the API, but only if it doesn't already exists.
     * @param Analysis $analysis
     */
    private function injectBaseInfos(Analysis $analysis) {
        $hasInfo = array_reduce(iterator_to_array($analysis->annotations), function ($carry, $annotation) {
            return $carry || ($annotation instanceof Info);
        }, false);
        if (!$hasInfo) {
            $info = new Info([
                "title" => "Json API Entities path description",
                "version" => "1.0"
            ]);
            $analysis->annotations->attach($info);
        }
    }

    /**
     * TODO: full describe entities path (only if they doesn't exists?)
     * @param Analysis $analysis
     */
    private function injectEntitiesPaths(Analysis $analysis) {
        // Describe the paths
        // TODO: annotations->attach works only if the context is global, for
        // class context, use $analysis->addAnnotation();
        $oaPath = new \OpenApi\Annotations\PathItem([
            "path" => "test"
        ]);
        $analysis->annotations->attach($oaPath);
    }

    /**
     * TODO: this is a beginning but is not completely working.
     * Schema get's added, along with some of the properties, but the
     * result doesn't care about it.
     * @param Analysis $analysis
     */
    private function injectClassAnnotations(Analysis $analysis) {
        foreach ($analysis->classes as $className => $class) {
            /** @var EntityViewModel $entityDescription */
            $entityDescription = $this->entitiesDescription[trim($className, "\\")] ?? null;
            if ($entityDescription) {
                $hasSchema = array_reduce($class["context"]->annotations, function ($carry, $annotation) {
                    return $carry || ($annotation instanceof Schema);
                }, false);
                if (!$hasSchema) {
                    $schema = new Schema([
                        "ref" => $entityDescription->getName(),
                        "title" => "Description for ".$entityDescription->getName().", class ".$entityDescription->getClass(),
                        "properties" => []
                    ]);
                    /** @var EntityPropertyViewModel $propertyDescription */
                    foreach ($entityDescription->getProperties() as $propertyDescription) {
                        $property = new Property([]);
                        $property->property = $propertyDescription->getName();
                        $property->ref = $propertyDescription->getName();
                        $property->type = $propertyDescription->getType();
                        $schema->properties[$property->property] = $property;
                    }
                    $analysis->addAnnotation($schema, $class["context"]);
                }
            }
        }
    }
}