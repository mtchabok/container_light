<?php
namespace MCL;
use Closure;
use \Exception;
use MCL\Resource\Definition;
use MCL\Resource\Event;
use MCL\Resource\Source;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class Container implements ContainerInterface, \ArrayAccess
{

    /**
     * hold file or array bindings data.
     * @var array
     */
    protected array $sources = [];
    /**
     * @var array
     */
    protected array $resources = [];
    /**
     * hold binding data [array or Resources].
     * @var Definition[]
     */
    protected array $definitions = [];
    /**
     * @var array
     */
    protected array $autowireCache = [];
    /**
     * hold shared instance of definition
     * @var array
     */
    protected array $instances = [];
    /**
     * hold event callbacks [array or callable].
     * @var array
     */
    protected array $events = [];
    /**
     * hold execution binding data [Resources]
     * @var array
     */
    protected array $execution = [];





    /**
     * add source file or array data to container.<br>
     * file content: return array(name=>resource);
     *
     * @param string|array|Closure|callable $data
     * @param string $namespace [optional]
     * @return  $this
     */
    public function add(string|array|Closure|callable $data, string $namespace = ''): static
    {
        $this->sources[$namespace][] = new Source($this, $data);
        return $this;
    }

    public function addIf(string|array|Closure|callable $data, string $namespace = '') :static
    {
        $this->sources[$namespace][] = new Source($this, $data, true);
        return $this;
    }


    /**
     * @inheritDoc
     * @throws Exception
     */
    public function has(string $id): bool
    {
        if(isset($this->definitions[$id]) && $this->definitions[$id]->protected)
            return true;
        $this->loadResources($id);
        return isset($this->definitions[$id]);
    }

    /**
     * return resolve binding resource.
     *
     * @param   string      $id
     * @param   mixed       $default [optional]
     * @param   ?array      $parameters [optional]
     * @return  mixed
     * @throws  Exception
     */
    public function get(string $id, mixed $default=null, ?array $parameters = null) :mixed
    {
        if(empty($id) || array_key_exists($id, $this->execution) || !$this->has($id))
            return $default;
        $this->execution[$id] = [''=> $def = $this->definitions[$id]];
        // params event
        if(null!==($temp = $this->dispatch('params', $parameters, $id)))
            $parameters = $temp;
        unset($temp);

        $instance = null;
        if(($isShared = empty($parameters) && $def->shared) && array_key_exists($id, $this->instances)) {
            $instance = $this->instances[$id];
            $def->usedCounter++;
        }else {
            try{
                $parameters = $this->prepareDefinitionParameters($def, $parameters);
                switch ($def->type){
                    case Definition::TYPE_CLASS:
                        if(!empty($def->value) && !str_contains($def->value, '@anonymous') && class_exists($def->value)) {
                            $instance = $this->factoryClass($def->value, $parameters, $def->autoWired, $id);
                            $def->usedCounter++;
                        }else
                            $isShared = false;
                        break;
                    case Definition::TYPE_CALLABLE:
                        if(!empty($def->value)) {
                            $instance = $this->factoryCallable($def->value, $parameters, $def->autoWired, $id);
                            $def->usedCounter++;
                        }else
                            $isShared = false;
                        break;
                    case Definition::TYPE_ALIAS:
                        $instance = $this->get($def->value, $default, $parameters);
                        $def->usedCounter++;
                        break;
                    case Definition::TYPE_VALUE:
                        $instance = $def->value;
                        $def->usedCounter++;
                        break;
                    default:
                        $isShared = false;
                }
                if($isShared)
                    $this->instances[$id] = $instance;

            }catch(Exception $e)
            { unset($this->execution[$id]); throw $e;}
        }

        // after event
        $instance = $this->dispatch('after', $instance, $id);
        unset($this->execution[$id]);

        return $instance;
    }


    protected function prepareDefinitionParameters(Definition $def, ?array $parameters = null) :array
    {
        if($def->type === Definition::TYPE_VALUE)
            return [];
        if(null===$def->isCallableParameters)
            $def->isCallableParameters = !empty($def->parameters) && is_callable($def->parameters);
        if($def->isCallableParameters)
            $parameters = call_user_func($def->parameters, $parameters ?? [], $this, $def);
        elseif(empty($parameters))
            $parameters = (array) (empty($def->parameters) ?[] :$def->parameters);
        return $parameters;
    }


    /**
     * @param object|string $class
     * @param array|bool|null $arguments [optional]
     * @return object|null
     * @throws Exception
     */
    public function makeArrayArg(object|string $class, array|bool|null $arguments = null) :object|null
    {
        $isObject = is_object($class);
        $isAutoWired = null;
        if(str_contains($id = $isObject ? get_class($class) : $class, '@anonymous'))
            $id = '';
        if(is_bool($arguments)){
            $isAutoWired = $arguments;
            $arguments = null;
        }
        if(!empty($id)){
            if(!$this->has($id))
                $this->add([$id=>Definition::createClass()->autoWired(!(false === $isAutoWired))]);
            return $this->get($id,null, $arguments);
        }elseif(!$isObject)
            return null;
        return $this->factoryClass($class, $arguments, $isAutoWired);
    }

    /**
     * @param   object|string   $class
     * @param   mixed           ...$argument [optional]
     * @return  object|null
     * @throws  Exception
     */
    public function make(object|string $class, mixed ...$argument) :object|null
    { return $this->makeArrayArg($class, array_slice(func_get_args(), 1)); }


    /**
     * @param   callable|array|string   $callable
     * @param   array|bool|null         $arguments [optional]
     * @return  mixed
     * @throws  Exception
     */
    public function callArrayArgs(callable|array|string $callable, array|bool|null $arguments = null) :mixed
    {
        $isAutoWired = null;
        if(is_bool($arguments)){
            $isAutoWired = $arguments;
            $arguments = null;
        }
        $id = '';
        if(is_string($callable) && !str_contains($callable, '@anonymous'))
            $id = $callable;
        elseif (is_array($callable) && is_string($callable[0] ?? null) && !str_contains($callable[0], '@anonymous'))
            $id = implode('::', $callable);
        if(!empty($id)){
            if(!$this->has($id))
                $this->add([$id=>Definition::createCallable($callable)->autoWired(!(false === $isAutoWired))]);
            return $this->get($id,null, $arguments);
        }
        return $this->factoryCallable($callable, $arguments, null, $isAutoWired);
    }

    /**
     * @param   callable|array|string   $callable
     * @param   mixed                   ...$argument [optional]
     * @return  mixed
     * @throws  Exception
     */
    public function call(callable|array|string $callable, mixed ...$argument) :mixed
    { return $this->callArrayArgs($callable, array_slice(func_get_args(), 1)); }



    /**
     * @param string|object $class
     * @param ?array $arguments [optional]
     * @param ?bool $isAutoWired [optional]
     * @param ?string $id [optional]
     * @return object|null
     * @throws Exception|ReflectionException
     */
    protected function factoryClass(string|object $class, ?array $arguments=null, ?bool $isAutoWired=null, ?string
    $id=null) :object|null
    {
        if(!$class = $this->getClassReflection($class, $id))
            return null;
        if(!$class->isInstantiable())
            throw new Exception("Class '{$class->getName()}' is not instantiable");

        if($isAutoWired)
            $arguments = $this->getMethodParameters($class, $id, null, $arguments);

        return empty($arguments)
            ?$class->newInstance()
            :$class->newInstanceArgs($arguments);
    }

    /**
     * @param   callable|array|string   $callable
     * @param   ?array                  $parameters [optional]
     * @param   ?bool                   $isAutoWired [optional]
     * @param   ?string                 $id [optional]
     * @return  mixed
     * @throws  Exception|ReflectionException
     */
    protected function factoryCallable(callable|array|string $callable, ?array $parameters=null, ?bool
    $isAutoWired=null, ?string $id=null) :mixed
    {
        $FN = '';
        if(is_string($callable)){
            if(str_contains($FN = $callable, '::'))
                $callable = explode('::', $callable,2);
        }elseif (is_array($callable) && is_string($callable[0])){
            $FN = implode('::', $callable);
        }
        if($FN && str_contains($FN, '@anonymous'))
            return null;
        $isStaticMethod = false;
        if($FN && is_array($callable)){
            if(array_key_exists('isStatic',
                $this->autowireCache[$callable[0]]['method'][$callable[1]]??[])){
                $isStaticMethod = (bool) $this->autowireCache[$callable[0]]['method'][$callable[1]]['isStatic'];
            }elseif(class_exists($callable[0])){
                $method = $this->getMethodReflection($callable[0], $id, $callable[1]);
                $isStaticMethod = $this->autowireCache[$callable[0]]['method'][$callable[1]]['isStatic'] =
                    $method->isStatic();
            }elseif($this->has($callable[0])){
                $callable[0] = $this->get($callable[0]);
                if(!is_object($callable[0]) && !class_exists($callable[0]))
                    return null;
                $FN = '';
            }else
                return null;
        }
        // parameters
        if($isAutoWired) {
            if(is_array($callable))
                $parameters = $this->getMethodParameters($callable[0], $id, $callable[1], $parameters);
            else
                $parameters = $this->getFunctionParameters($callable, $FN, $parameters);
        }
        // isStatic method
        if($isStaticMethod){
            $callable = implode('::', $callable);
        }
        // call user func
        return empty($parameters) ?call_user_func($callable)
            :call_user_func_array($callable,$parameters);
    }

    /**
     * @param string|object $class
     * @param ?string $id [optional]
     * @return ReflectionClass|null
     * @throws Exception|ReflectionException
     */
    protected function getClassReflection(string|object $class, ?string $id=null) :ReflectionClass|null
    {
        $isObject = is_object($class);
        if(str_contains($classname = $isObject ?get_class($class) :$class,'@anonymous')){
            if(!$isObject)
                return null;
            $classname = '';
        }
        if($classname && isset($this->autowireCache[$classname]['reflection']))
            return $this->autowireCache[$classname]['reflection'];
        if($id && isset($this->definitions[$id]->autowireCache['reflection']))
            return $this->autowireCache[$classname]['reflection'];
        $class = new ReflectionClass($classname);
        if($classname)
            $this->autowireCache[$classname]['reflection'] = $class;
        elseif($id)
            $this->definitions[$id]->autowireCache['reflection'] = $class;
        return $class;
    }

    /**
     * @param   string|object   $class
     * @param   ?string         $id [optional]
     * @param   ?string         $method [optional]
     * @return  ReflectionMethod|null
     * @throws  ReflectionException
     */
    protected function getMethodReflection(string|object $class, ?string $id=null, ?string $method = null)
    :ReflectionMethod|null
    {
        if(!($class instanceof ReflectionClass ? $class
            : ($class = $this->getClassReflection($class, $id))))
            return null;
        if(str_contains($classname = $class->getName(), '@anonymous'))
            $classname = '';
        $methodname = (string) $method;
        $method = null;
        if(empty($methodname)){
            if($classname && array_key_exists('constants', $this->autowireCache[$classname]))
                $methodname = $this->autowireCache[$classname]['constants'];
            elseif($id && isset($this->definitions[$id]->autowireCache['constants']))
                $methodname = $this->definitions[$id]->autowireCache['constants'];
            else{
                if($method = $class->getConstructor())
                    $methodname = $method->getName();
                if($classname)
                    $this->autowireCache[$classname]['constants'] = $methodname;
                elseif ($id)
                    $this->definitions[$id]->autowireCache['constants'] = $methodname;
            }
            if(empty($methodname))
                return null;
        }
        if($classname && array_key_exists('reflection',$this->autowireCache[$classname]['method'][$methodname]??[]))
            return $this->autowireCache[$classname]['method'][$methodname]['reflection'];
        elseif($id && array_key_exists('reflection',$this->definitions[$id]->autowireCache['method'][$methodname]??[]))
            return $this->definitions[$id]->autowireCache['method'][$methodname]['reflection'];
        $method = $class->getMethod($methodname);
        if($classname)
            $this->autowireCache[$classname]['method'][$methodname]['reflection'] = $method;
        elseif($id)
            $this->definitions[$id]->autowireCache['method'][$methodname]['reflection'] = $method;
        return $method;
    }


    /**
     * @param object|string $class
     * @param   ?string $id [optional]
     * @param string|ReflectionMethod|null $method [optional]
     * @param   ?array $arguments [optional]
     * @return  array
     * @throws  Exception|ReflectionException
     */
    protected function getMethodParameters(object|string $class, ?string $id=null, string|ReflectionMethod|null
    $method=null
        , ?array $arguments = null) : array
    {
        if(!$class instanceof ReflectionClass)
            if(!$class = $this->getClassReflection($class, $id))
                return [];
        if(str_contains($classname = $class->getName(),'@anonymous'))
            $classname = '';
        if(!$method instanceof ReflectionMethod)
            $method = $this->getMethodReflection($class, $id, $method);
        if(!$method)
            return [];
        $methodname = $method->getName();

        $parameters = [];
        if($classname){
            if(!isset($this->autowireCache[$classname]['method'][$methodname]['parameters'])){
                $this->autowireCache[$classname]['method'][$methodname]['isStatic'] = $method->isStatic();
                $this->autowireCache[$classname]['method'][$methodname]['parameters'] =
                    $this->prepareReflectionParameters($method->getParameters());
            }
            $parameters = $this->autowireCache[$classname]['method'][$methodname]['parameters'];
        }else{
            $parameters = $this->prepareReflectionParameters($method->getParameters());
        }
        return $this->buildParametersForCall($parameters, $arguments);
    }

    /**
     * @param   ReflectionFunction|Closure|string   $function
     * @param   ?string                             $id [optional]
     * @param   ?array                              $arguments [optional]
     * @return  array
     * @throws  Exception|ReflectionException
     */
    protected function getFunctionParameters(ReflectionFunction|Closure|string $function, ?string $id=null, ?array
        $arguments=null) :array
    {
        $FN = '';
        if($function instanceof ReflectionFunction) {
            if(str_contains($FN = $function->getName(), '{closure}'))
                $FN = '';
        }else{
            if(str_contains($FN = is_string($function) ? $function : '', '{closure}'))
                $FN = '';
            if($FN && isset($this->autowireCache[''][$FN]['reflection']))
                $function = $this->autowireCache[''][$FN]['reflection'];
            elseif($id && isset($this->definitions[$id]->autowireCache['reflection']))
                $function = $this->definitions[$id]->autowireCache['reflection'];
            else
                $function = new ReflectionFunction($function);
        }
        if($FN && isset($this->autowireCache[''][$FN]['parameters']))
            $parameters = $this->autowireCache[''][$FN]['parameters'];
        elseif ($id && isset($this->definitions[$id]->autowireCache['parameters']))
            $parameters = $this->definitions[$id]->autowireCache['parameters'];
        else
            $parameters = $this->prepareReflectionParameters($function->getParameters());
        return $this->buildParametersForCall($parameters, $arguments);
    }


    /**
     * @param array $parameters
     * @return array
     */
    protected function prepareReflectionParameters(array $parameters) :array
    {
        $params = [];
        foreach ($parameters as $param)
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
            $params[] = $p;
        }
        return $params;
    }

    /**
     * @param array $ref_parameters
     * @param ?array $arguments [optional]
     * @return array
     * @throws Exception
     */
    protected function buildParametersForCall(array $ref_parameters, ?array $arguments = null) :array
    {
        if(!is_array($arguments))
            $arguments = [];
        $parameters = [];
        $hasKeyName = is_string(key($arguments));

        foreach ($ref_parameters as $arg)
        {
            if($hasKeyName){
                $parameter = $arguments[$arg['name']] ?? null;
                unset($arguments[$arg['name']]);
            }else
                $parameter = array_shift($arguments);
            if(null!==$parameter){
                $parameters[] = $parameter;
                continue;
            }
            if($arg['isOptional'])
                break;
            if(null===$arg['typeInContainer']) {
                $arg['typeInContainer'] = '';
                foreach ($arg['typesClass'] as $type) {
                    if ($this->has($type->getName()))
                        $arg['typeInContainer'] = $type->getName();
                    elseif (class_exists($type->getName())) {
                        $this->add([$type->getName()=>Definition::createClass()->autoWired(true)]);
                        $arg['typeInContainer'] = $type->getName();
                    }
                }
            }
            if(!empty($arg['typeInContainer']))
            {
                $parameters[] = $this->get($arg['typeInContainer']);
                continue;
            }
            $parameters[] = null;
        }
        return $parameters;
    }






    /**
     * execute event
     *
     * @param string $name
     * @param mixed $parameter
     * @param   ?string $id [optional]
     * @return  mixed
     * @throws Exception
     */
    public function dispatch(string $name, mixed $parameter, ?string $id = null) :mixed
    {
        if(null===$id) $id = '';
        [$namespace, $name] = explode(':', str_contains($name,':') ?$name :":$name");
        if(empty($name)
            || (isset($this->execution[$id]) && array_key_exists($name, $this->execution[$id])))
            return $parameter;
        if($id){
            if(!($def = $this->execution[$id][''] ?? null))
                return $parameter;
            if(!($def->allowEvent($name))) {
                $this->execution[$id][$name] = false;
                return $parameter;
            }
        }
        $this->execution[$id][$name] = false;
        $this->loadResources(($id ?? $namespace) . ":$name");
        foreach (explode('.', empty($id)?'':".$id") as $np){
            $namespace.=((strlen($namespace) && strlen($np))?'.':'').$np;
            $idx = $namespace.":{$name}";
            if(empty($this->events[$idx]))
                continue;

            foreach ($this->events[$idx] as $key=>$callback){
                $p = call_user_func($callback->callable, $parameter, $this, $id, $namespace, $name);
                if(null!==$p) $parameter = $p;
            }
        }

        $this->execution[$id][$name] = true;
        return $parameter;
    }













    /**
     * @param string $id
     * @return void
     * @throws Exception
     */
    protected function loadResources(string $id) :void
    {
        $isEventId = str_contains($id,':');
        $parts = substr($id, 0, strpos($isEventId?$id:"$id:",':'));
        $parts = explode('.', empty($parts) ?'' :".{$parts}");
        do{
            $namespace = implode('.',$parts);
            if(str_starts_with($namespace,'.'))
                $namespace = substr($namespace,1);
            array_pop($parts);
            if(!isset($this->sources[$namespace]))
                continue;
            while ($source = array_shift($this->sources[$namespace])) {
                /** @var Source $source */
                foreach ($source->getData() as $i=>$resource){
                    if(empty($i))
                        throw new Exception("item key name in namespace '{$namespace}' is required");
                    if($resource instanceof Event || str_contains($i, ':')) {
                        if(!empty($namespace))
                            $i = $namespace . (str_starts_with($i,':') ?'' :'.') . $i;
                        if(!str_contains($i,':'))
                            $i.= ':after';
                        if(!$resource instanceof Event)
                            $resource = Event::createEvent($resource);
                        $this->events[$i][] = $resource;
                        continue;
                    }elseif(!empty($namespace)){
                        $i = "$namespace.$i";
                    }
                    if($resource instanceof Source){
                        $this->sources[$i][] = $resource;
                    }elseif(!($source->ifNotExists
                        && ( isset($this->definitions[$i]) || isset($this->resources[$i]) )))
                        $this->resources[$i][] = $resource;
                }
            }
        }while($parts);

        if(isset($this->resources[$id])
            && !(($isExist = isset($this->definitions[$id])) && array_key_exists($id, $this->execution))){
            foreach ($this->resources[$id] as $resource){
                if($isExist && $this->definitions[$id] instanceof Definition
                    && $this->definitions[$id]->protected)
                    break;
                if($isExist && $resource instanceof Definition && $resource->ifNotExists)
                    continue;
                $this->definitions[$id] = $resource;
                $isExist = true;
            }
            if(!$this->definitions[$id] instanceof Definition)
                $this->definitions[$id] = Definition::createDefinition($this->definitions[$id]);
            unset($this->resources[$id]);
        }
    }














    /**
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    { return $this->callArrayArgs($name, $arguments); }


    public function __isset(string $name): bool
    { return $this->has(str_replace('_','.',$name)) || $this->has($name); }

    public function offsetExists(mixed $offset): bool
    { return $this->has($offset); }


    public function __get(string $name)
    {
        return $this->has(str_replace('_','.',$name))
            ?$this->get(str_replace('_','.',$name)) : $this->get($name);
    }

    public function offsetGet(mixed $offset): mixed
    { return $this->get($offset); }


    public function __set(string $name, $value): void
    { $this->add([str_replace('_','.',$name)=>$value]); }

    public function offsetSet(mixed $offset, mixed $value): void
    { $this->add([$offset=>$value]); }


    public function __unset(string $name)
    { }

    public function offsetUnset(mixed $offset): void
    { }
}
