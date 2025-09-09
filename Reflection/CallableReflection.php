<?php
namespace MCL\Reflection;
use Exception;
use ReflectionFunction;
use MCL\Container;
use ReflectionException;
use ReflectionMethod;

class CallableReflection
{
    protected mixed $callable=null;
    protected ?ParameterReflection $params=null;
    protected bool $isCallableObject = false;


    /**
     * @param ?array $args = null
     * @param ?bool $isAutoWired =null
     * @param ?Container $container =null
     * @return mixed
     * @throws Exception|ReflectionException
     */
    public function call(?array $args = null, ?bool $isAutoWired = null, ?Container $container = null): mixed
    {
        if($isAutoWired && null===$this->params){
            $isCallableMethod = false;
            if(is_string($this->callable) && str_contains($this->callable,'::'))
                $this->callable = explode('::', $this->callable,2);
            if(is_array($this->callable))
            {
                $isCallableMethod = true;
                if(null!==$container && is_string($this->callable[0]) && $container->has($this->callable[0]))
                {
                    $this->callable[0] = $container->make($this->callable[0]);
                }
                if(is_object($this->callable[0]))
                    $this->isCallableObject = true;
            }

            $r = $isCallableMethod
                ? new ReflectionMethod($this->callable[0], $this->callable[1]??null)
                : new ReflectionFunction($this->callable);

            $this->params = new ParameterReflection($r->getParameters());
        }
        return call_user_func_array( $this->callable
            , $isAutoWired
                ? $this->params->getCallParameters($args,$container)
                : ( $args ?? [] ));
    }

    /**
     * @param callable $callable
     */
    public function __construct(mixed $callable)
    { $this->callable = $callable; }

    /**
     * @throws ReflectionException
     */
    public function __invoke()
    { return $this->call(func_get_args()); }


}