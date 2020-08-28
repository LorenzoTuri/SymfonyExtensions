<?php

namespace Lturi\SymfonyExtensions\Controller\Api;

use Lturi\SymfonyExtensions\Classes\Controller\ApiController;
use Lturi\SymfonyExtensions\Services\Response\ApiResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Locales;
use Safe\Exceptions\DirException;

class TranslationController
{

    /**
     * @param ContainerInterface $container
     * @param Request $request
     * @param ApiResponse $apiResponse
     * @return ApiResponse
     * @throws DirException
     */
    public function getAllRequest(ContainerInterface $container, Request $request, ApiResponse $apiResponse): ApiResponse
    {
        $translationsPath = $container->getParameter("translator.default_path");
        $translationsFiles = \Safe\scandir($translationsPath);

        $translator = $container->get("translator");

        $messages = [];
        $locales = array_keys(Locales::getNames());
        foreach ($locales as $locale) {
            $fileExists = in_array("messages.".$locale.".json", $translationsFiles);
            if ($fileExists) {
                $localMessages = $translator->getCatalogue($locale)->all("messages");
                $messages[$locale] = $localMessages;
            }
        }
        return $apiResponse->setResponse($messages);
    }
}
