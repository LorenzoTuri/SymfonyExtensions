<?php

namespace Lturi\SymfonyExtensions\Framework\Controller;

use Lturi\SymfonyExtensions\Framework\Service\Response\ApiResponse;
use Lturi\SymfonyExtensions\Framework\Service\Response\CacheableApiResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Locales;
use Safe\Exceptions\DirException;

class TranslationController
{

    /**
     * @param ContainerInterface   $container
     * @param Request              $request
     * @param CacheableApiResponse $apiResponse
     *
     * @return ApiResponse
     * @throws DirException
     */
    public function getSimpleRequest(ContainerInterface $container, Request $request, CacheableApiResponse $apiResponse): ApiResponse
    {
        $translationsPath = $container->getParameter("translator.default_path");
        $translationsFiles = \Safe\scandir($translationsPath);

        $lang = $request->get("locale", null);

        $translator = $container->get("translator");

        $messages = [];
        $locales = array_keys(Locales::getNames());
        foreach ($locales as $locale) {
            $fileExists = in_array("messages.".$locale.".json", $translationsFiles);
            if (($lang === null || $lang == $locale) && $fileExists) {
                $localMessages = $translator->getCatalogue($locale)->all("messages");
                $messages[$locale] = $localMessages;
            }
        }
        return $apiResponse->setResponse($messages);
    }

    /**
     * @param ContainerInterface   $container
     * @param Request              $request
     * @param CacheableApiResponse $apiResponse
     *
     * @return ApiResponse
     */
    public function getFullRequest(ContainerInterface $container, Request $request, CacheableApiResponse $apiResponse): ApiResponse
    {
        $lang = $request->get("locale", null);

        $translator = $container->get("translator");

        $messages = [];
        $locales = array_keys(Locales::getNames());
        foreach ($locales as $locale) {
            if (($lang === null || $lang == $locale)) {
                $localMessages = $translator->getCatalogue($locale)->all();
                $messages[$locale] = $localMessages;
            }
        }
        return $apiResponse->setResponse($messages);
    }
}
