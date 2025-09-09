<?php
namespace MCL\Reflection;
use Exception;
use MCL\Container;
use MCL\Resource\ClassResource;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class ParameterReflection
{
    protected array $parameters = [];

    /**
     * @param ?array $parameters [optional]
     * @param ?Container $container [optional]
     * @return array
     * @throws Exception
     */
    public function getCallParameters(?array $parameters = null, ?Container $container = null) :array
    {
        if(!is_array($parameters)) $parameters = [];
        $params = [];
        $hasKeyName = is_string(key($parameters));
        foreach ($this->parameters as $arg)
        {
            if($hasKeyName){
                $parameter = $parameters[$arg['name']] ?? null;
                unset($parameters[$arg['name']]);
            }else
                $parameter = array_shift($parameters);
            if(null!==$parameter){
                $params[] = $parameter;
                continue;
            }
            if($arg['isOptional'])
                break;
            if(null!==$container){
                if(null===$arg['typeInContainer']) {
                    $arg['typeInContainer'] = '';
                    foreach ($arg['typesClass'] as $type) {
                        if ($container->has($type->getName()))
                            $arg['typeInContainer'] = $type->getName();
                        elseif (class_exists($type->getName())) {
                            $container->bind($container->makeParamsArray(ClassResource::class,[['classname'=>$type->getName(),'isAutowire'=>true]])
                                , $type->getName());
                            $arg['typeInContainer'] = $type->getName();
                        }
                    }
                }
                if(!empty($arg['typeInContainer']))
                {
                    $params[] = $container->get($arg['typeInContainer']);
                    continue;
                }
            }
            $params[] = null;
        }
        return $params;
    }

    /**
     * @param ReflectionParameter[] $reflectionParameters
     */
    public function __construct(array $reflectionParameters)
    {
        foreach ($reflectionParameters as $param)
        {
            $p = [
                'name' => $param->getName(),
                'isOptional' => $param->isOptional(),
                'typeInContainer' => null,
                'typesClass' => [],
                'typesBuiltin' => [],
            ];
            $types = $param->getType();
            $types = $types instanceof ReflectionNamedType ? [$types]
                : ($types instanceof ReflectionUnionType ? $types->getTypes() : []);
            foreach ($types as $type)
            {
                if($type->isBuiltin())
                { $p['typesBuiltin'][] = $type->getName(); }
                else
                { $p['typesClass'][] = $type; }
            }
            $this->parameters[] = $p;
        }
    }
}