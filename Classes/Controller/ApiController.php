<?php

namespace Lturi\SymfonyExtensions\Classes\Controller;

use Lturi\SymfonyExtensions\Services\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiController extends AbstractController
{
    /** @var SerializerService  */
    protected $serializerService;
    /** @var ParameterBagInterface */
    protected $params;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var EntityManagerInterface  */
    protected $entityManager;
    /** @var ValidatorInterface  */
    protected $validator;

    public function __construct(
        ContainerInterface $container,
        SerializerService $serializerService,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->serializerService = $serializerService;
        $this->params = $params;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->validator = $validator;

        $this->container = $container;
    }

    /**
     * Loads request data from different locations of the request
     * @param Request $request
     *
     * @return array|mixed
     */
    protected function loadRequestData(Request $request)
    {
        try {
            $content = (string)$request->getContent();
            if (!empty($content)) {
                return \Safe\json_decode($content, true);
            } else {
                $content = $request->query->all();
                return $content;
            }
        } catch (\Exception $exception) {
            return [];
        }
    }
}
