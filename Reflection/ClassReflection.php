<?php
namespace MCL\Reflection;
use Exception;
use MCL\Container;
use ReflectionClass;
use ReflectionException;

class ClassReflection
{
    protected string $classname = '';
    protected ?ReflectionClass $class = null;
    protected ?ParameterReflection $params = null;


    /**
     * @param ?array $args [optional]
     * @param ?bool $isAutowire [optional]
     * @param ?Container $container [optional]
     * @return object|null
     * @throws Exception|ReflectionException
     */
    public function newInstance(?array $args = null, ?bool $isAutowire = null, ?Container $container = null) :object|null
    {
        if(null===$this->class){
            $this->class = new ReflectionClass($this->classname);
            if(!$this->class->isInstantiable()){
                $this->class = null;
                throw new Exception("Class '$this->classname' is not instantiable");
            }
        }
        if($isAutowire && null===$this->params){
            $rm = $this->class->getConstructor();
            $this->params = new ParameterReflection($rm ?$rm->getParameters() :[]);
        }
        return $this->class
            ->newInstanceArgs($isAutowire ?$this->params->getCallParameters($args, $container) :$args);
    }


    /**
     * @param string $classname
     */
    public function __construct(string $classname)
    { $this->classname = $classname; }

    public function __toString(): string
    { return $this->classname; }

    /**
     * @return object|null
     * @throws ReflectionException|Exception
     */
    public function __invoke(): object|null
    { return $this->newInstance(func_get_args()); }

}