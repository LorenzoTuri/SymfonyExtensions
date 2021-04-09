<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\AbstractCommandFilterAttributes;
use Lturi\SymfonyExtensions\CommandApi\Event\AbstractCommandFilterCommandResults;
use Lturi\SymfonyExtensions\CommandApi\Event\AbstractCommandFilterEntity;
use Lturi\SymfonyExtensions\Framework\Entity\User;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Exception\EntityNotFoundException;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\Framework\EntityUtility\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;

abstract class AbstractCommand extends Command
{
    protected $entities;
    protected $entitiesDescriptor;
    protected $entityManager;
    protected $eventDispatcher;

    protected $entitiesDescription;
    protected $serializer;


    public function __construct(
        $entities,
        AbstractEntitiesDescriptor $entitiesDescriptor,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->entities = $entities;
        $this->entitiesDescriptor = $entitiesDescriptor;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;

        $this->entitiesDescription = $this->entitiesDescriptor->describe("cachedCommandApiEntities", $this->entities);

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return spl_object_hash($object);
            },
        ];
        $encoders = [new JsonEncoder(), new XmlEncoder(), new CsvEncoder(), new YamlEncoder()];
        $normalizers = [
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new ObjectNormalizer(
                null,
                null,
                null,
                null,
                null,
                null,
                $defaultContext
            ),
            new GetSetMethodNormalizer(
                null,
                null,
                null,
                null,
                null,
                $defaultContext
            ),
            new ArrayDenormalizer(),
            new StreamNormalizer(),
        ];
        $this->serializer = new Serializer($normalizers, $encoders);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription("Use to manage the available entities.")
            ->addArgument("entity", InputArgument::REQUIRED, "Entity name")
            ->addOption(
                "content-type",
                "ct",
                InputOption::VALUE_OPTIONAL,
                "Content to be returned, must be either json/xml/yaml/csv, other types are not supported",
                "json"
            )
            ->addOption(
                "content",
                "c",
                InputOption::VALUE_OPTIONAL,
                "Content of the requests, ex. filters, or data for create. Must be a valid JSON string",
                "{}"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var AbstractCommandFilterAttributes $initializeCommandAttributes */
        $initializeCommandAttributes = $this->eventDispatcher->dispatch(new AbstractCommandFilterAttributes(
            $input->getArgument("entity"),
            $input->getOption("content-type"),
            $this->loadContent($input),
        ));
        $entityName = $initializeCommandAttributes->getEntityName();
        $responseType = $initializeCommandAttributes->getContentType();

        try {
            $entity = $this->detectEntity($this->entitiesDescription, $entityName);
            if (!$entity) throw new EntityNotFoundException($entityName);

            $entityEvent = $this->eventDispatcher->dispatch(new AbstractCommandFilterEntity(
                $entity->getClass(),
                $entity
            ));
            $results = $this->executeApi(
                $entityEvent->getEntity(),
                $initializeCommandAttributes->getContent()
            );
            $resultsEvent = $this->eventDispatcher->dispatch(new AbstractCommandFilterCommandResults(
                $entity->getClass(),
                $results
            ));

            $results = $this->serializer->serialize(
                $resultsEvent->getResults(),
                $responseType
            );
            $output->write($results);
            return Command::SUCCESS;
        } catch (Throwable $exception) {
            // TODO: remove before commit
            dump($exception);
            die();
            $results = $this->serializer->serialize($exception, $responseType);
            $output->write($results);
            return Command::FAILURE;
        }
    }

    abstract function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    );

    /**
     * Detect the correct entity description, given entity name and descriptions
     * @param $entitiesDescription
     * @param $entity
     *
     * @return EntityViewModel|null
     */
    protected function detectEntity($entitiesDescription, $entity): ?EntityViewModel
    {
        return array_reduce($entitiesDescription, function ($carry, $entityDescription) use ($entity) {
            return $carry ? $carry : ($entityDescription->getName() == $entity ? $entityDescription : null);
        });
    }

    /**
     * Load the content
     * @param InputInterface $input
     * @return ParameterBag
     */
    protected function loadContent(InputInterface $input): ParameterBag
    {
        $content = $input->getOption("content");
        $content = json_decode($content, true);
        if (!$content) $content = [];

        return new ParameterBag(array_merge(
            $input->getArguments(),
            $input->getOptions(),
            $content
        ));
    }
}