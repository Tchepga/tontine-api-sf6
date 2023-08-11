<?php

namespace App\Utilities;

use App\Exception\TontineException;
use Exception;
use http\Exception\BadConversionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class HttpHelper
{
    /**
     * get resource/body from request
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param string $className
     * @return object
     * @throws TontineException
     */
    public static function getResource(
        Request $request,
        SerializerInterface $serializer,
        string $className
    ): object
    {
        try {
            return  $serializer->deserialize(
                $request->getContent(),
                $className,
                'json'
            );

        } catch (Exception $e) {
            throw  new TontineException("Problem to serialize into ".$className. " Error: ".$e->getMessage());
        }
    }
}
