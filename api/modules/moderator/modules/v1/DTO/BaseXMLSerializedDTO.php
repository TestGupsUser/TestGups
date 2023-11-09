<?php

namespace api\modules\moderator\modules\v1\DTO;


use api\modules\moderator\modules\v1\DTO\exceptions\DTOCannotSerializeDataException;
use api\modules\moderator\modules\v1\DTO\exceptions\DTOFieldNotFoundException;
use api\modules\moderator\modules\v1\DTO\interfaces\IXmlDTO;
use common\components\ArrayToXmlConverter\ArrayToXmlConverter;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SimpleXMLElement;

class BaseXMLSerializedDTO implements IXmlDTO
{
    



    protected $rawData = '';

    



    protected $serializedData = null;

    




    public function __construct(string $rawData = null)
    {
        if (!is_null($rawData)) {
            $this->rawData = ArrayToXmlConverter::removeNameSpaces($rawData);
            $this->serializedData = new SimpleXMLElement($this->rawData);
            $this->serialize();
        }
    }

    




    public function getProperties(): array
    {
        return array_filter((new ReflectionClass($this))->getMethods(ReflectionProperty::IS_PUBLIC), function (ReflectionMethod $method) {
            return !empty($this->getPropertyByMethod($method->getName()));
        });
    }

    



    public function serialize()
    {
        foreach ($this->getProperties() as $method) {
            $type = $method->getReturnType();
            $property = $this->getPropertyByMethod($method->getName());
            $this->checkFieldProvided($property);
            if (!$type->isBuiltin()) {
                $typeClassString = $type->getName();
                $typeClass = new $typeClassString();
                if ($typeClass instanceof IXmlDTO) {
                    $typeClass->setSerializedData($this->serializedData->{$property});
                    $this->{$property} = $typeClass;
                } else {
                    throw new DTOCannotSerializeDataException("Объект для записи данных должен реализовывать интерфейс IXmlDTO");
                }
            } else {
                $value = $this->serializedData->{$property};
                settype($value, $type->getName());
                $this->{$property} = $value;
            }
        }
    }

    



    protected function getPropertyByMethod($method)
    {
        if (preg_match('/getProperty(.*)/', $method, $output_array)) {
            return $output_array[1];
        } else {
            return null;
        }
    }

    



    public function setSerializedData(SimpleXMLElement $serializedData): void
    {
        $this->serializedData = $serializedData;
        $this->serialize();
    }

    



    public function setStringRawData(string $rawData): void
    {
        $this->rawData = ArrayToXmlConverter::removeNameSpaces($rawData);
        $this->setSerializedData(new SimpleXMLElement($this->rawData));
    }

    public function __get($name)
    {
        if (method_exists($this, "getProperty{$name}")) {
            return call_user_func(array($this, "getProperty{$name}"));
        }
        return $this->{$name};
    }


    public function checkFieldProvided($field): bool
    {
        if (!isset($this->serializedData->{$field})) {
            throw new DTOFieldNotFoundException($field);
        }
        return true;
    }

    


    protected function getSerializedData(): SimpleXMLElement
    {
        return $this->serializedData;
    }
}