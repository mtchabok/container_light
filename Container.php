<?php
namespace MCL;
use Exception;
use MCL\Resource\AliasResource;
use MCL\Resource\CallableResource;
use MCL\Resource\ClassResource;
use MCL\Resource\ContainerNamespace;
use MCL\Resource\ResourceEventable;
use MCL\Resource\Resources;
use MCL\Resource\ResourceSharable;
use MCL\Resource\ValueResource;
use Psr\Container\ContainerInterface;

/**
 * The Container Class
 * @version 1.0.0
 */
class Container implements ContainerInterface
{
    /**
     * hold file or array bindings data.
     * @var array
     */
    protected array $sourceBindings = [];
    /**
     * hold binding data [array or Resources].
     * @var array|Resources[]
     */
    protected array $bindings = [];
    /**
     * hold file or array event callbacks.
     * @var array
     */
    protected array $sourceEvents = [];
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
     * hold namespace of bindings names.
     * @var string[]|ContainerNamespace[]
     */
    protected array $namespaces = [];





    /**
     * append or overwrite resource to the container.
     *
     * @param   mixed   $value
     * @param   string  $name
     * @param   ?string $namespace [optional]
     * @return  $this
     */
    public function bind(mixed $value, string $name, ?string $namespace = null):static
    {
        if(null===$namespace && str_contains($name,'.'))
            [$namespace, $name] = explode('.', $name, 2);
        return $this->binds([$name => $value], (string) $namespace);
    }

    /**
     * append or overwrite resource singleton to the container.<br>
     * singleton resource execute factory once and multiple use.
     *
     * @param   mixed   $definition
     * @param   string  $name
     * @param   ?string $namespace [optional]
     * @return  $this
     */
    public function singleton(mixed $definition, string $name, ?string $namespace = null) :static
    {
        $definition = $definition instanceof Resources ? $definition
            :$this->prepareBindingResource($definition, $name);
        if(!$definition)
            return $this;
        if(is_array($definition) && is_subclass_of($definition['type'], ResourceSharable::class))
            $definition['isShared'] = true;
        elseif ($definition instanceof ResourceSharable)
            $definition->isShared(true);
        if(null===$namespace && str_contains($name,'.'))
            [$namespace, $name] = explode('.', $name, 2);
        $this->binds([$name=>$definition], (string) $namespace);
        return $this;
    }

    /**
     * add source file or array data to container.<br>
     * file content: <?php return array(); ?>
     *
     * @param   string|array    $data
     * @param   ?string         $namespace [optional]
     * @return  $this
     */
    public function binds(string|array $data, ?string $namespace = null):static
    {
        if(!empty($data))
            $this->sourceBindings[(string)$namespace][] = $data;
        return $this;
    }

    protected function prepareBindingResource(mixed $resource, string $name)
    :Resources|array|null
    {
        if($resource instanceof Resources)
            return $resource;
        if(is_callable($resource)){
            $resource = ['type' => CallableResource::class, 'callback' => $resource, 'isAutowire' => true];
        }
        elseif(is_array($resource)){
            if(empty($resource['type'])){
                if(!empty($resource['classname']))
                    $resource['type'] = ClassResource::class;
                elseif (!empty($resource['callback']))
                    $resource['type'] = CallableResource::class;
                elseif (!empty($resource['alias']))
                    $resource['type'] = AliasResource::class;
                else
                    $resource = ['type' => ValueResource::class, 'value' => $resource];
            }elseif (!is_subclass_of($resource['type'], Resources::class))
                return null;
        }
        else{ $resource = ['type' => ValueResource::class, 'value' => $resource]; }
        return $resource;
    }


    /**
     * check resource exists.
     *
     * @param   string      $id
     * @return  bool
     * @throws  Exception
     */
    public function has(string $id): bool
    {
        if(isset($this->bindings[$id]))
            return true;
        $this->loadBindingResources($id);
        return isset($this->bindings[$id]);
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
    public function get(string $id, mixed $default=null, ?array $parameters=null) :mixed
    {
        if(empty($id) || array_key_exists($id, $this->execution))
            return $default;

        if(!array_key_exists($id, $this->bindings)){
            $this->loadBindingResources($id);
        }
        if(!$def = $this->getDefinition($id))
            return $default;

        $this->execution[$id] = [''=>$def];
        try{
            $instance = $def->getInstance($this, $id, $parameters, $default);
            unset($this->execution[$id]);
        }catch(Exception $e)
        { unset($this->execution[$id]); throw $e;}

        return $instance;
    }


    /**
     * build object of class from binding resource.<br>
     * append class name to container if not exists.
     *
     * @param   string      $class
     * @param   mixed       ...$parameter [optional]
     * @return  mixed
     * @throws  Exception
     */
    public function make(string $class, mixed ...$parameter):mixed
    { return $this->makeParamsArray($class, $parameter); }

    /**
     * build object of class from binding resource.<br>
     * append class name to container if not exists.
     *
     * @param  string       $class
     * @param  ?array       $parameters [optional]
     * @return mixed
     * @throws Exception
     */
    public function makeParamsArray(string $class, ?array $parameters=null):mixed
    {
        if(!array_key_exists($class, $this->bindings))
            $this->loadBindingResources($class);
        if(!array_key_exists($class, $this->bindings)){
            if(!class_exists($class))
                return null;
            $this->bind($this->makeParamsArray(ClassResource::class, [['classname'=>$class, 'isAutowired'=>true]]), $class);
        }
        return $this->get($class, null, $parameters);
    }

    /**
     * return resolve binding resource.
     * if callable resource not exists in container, append this and execute.
     *
     * @param   mixed       $callable
     * @param   mixed       ...$parameter [optional]
     * @return  mixed
     * @throws  Exception
     */
    public function call(mixed $callable, mixed ...$parameter) :mixed
    { return $this->callParamsArray($callable, $parameter); }

    /**
     * return resolve binding resource.
     * if callable resource not exists in container, append this and execute.
     *
     * @param   mixed       $callable
     * @param   array|null  $parameters
     * @return  mixed
     * @throws  Exception
     */
    public function callParamsArray(mixed $callable, ?array $parameters=null) :mixed
    {
        if(is_string($callable)){
            if(!array_key_exists($callable, $this->bindings))
                $this->loadBindingResources($callable);
            if(!array_key_exists($callable, $this->bindings)){
                if(!is_callable($callable))
                    return null;
                $this->bind($this->makeParamsArray(CallableResource::class, [['callback'=>$callable, 'isAutowired'=>true]]), $callable);
            }
            return $this->get($callable, null, $parameters);
        }
        if(is_callable($callable)) {
            $callable = $this->makeParamsArray(CallableResource::class, [['callback'=>$callable, 'isAutowired'=>true]]);
            return $callable->getInstance($this, '', $parameters);
        }
        return null;
    }





    /**
     * @throws Exception
     */
    protected function getDefinition(string $id) :Resources|null
    {
        if(!empty($id) && isset($this->bindings[$id])){
            if($this->bindings[$id] instanceof Resources)
                return $this->bindings[$id];
            elseif(is_array($this->bindings[$id]) && !empty($class = $this->bindings[$id]['type'] ?? '')
                && $id!==$class){
                $resource = $this->makeParamsArray($class, [$this->bindings[$id]]);
                if($resource instanceof Resources)
                    return ($this->bindings[$id] = $resource);
            }
            $this->bindings[$id] = null;
        }
        return null;
    }


    /**
     * @throws Exception
     */
    protected function loadBindingResources(string $id) :void
    {
        $namespaces = [];
        $namespace = '';
        foreach (explode('.', $id) as $np) {
            $namespace .= (strlen($namespace) ? '.' : '') . $np;
            if (!empty($this->sourceBindings[$namespace]))
                $namespaces[] = $namespace;
        }
        if(!in_array('', $namespaces))
            $namespaces[] = '';
        foreach ($namespaces as $namespace){
            while ($resources = array_shift($this->sourceBindings[$namespace])) {
                if(is_string($resources)) {
                    try{ $resources = include $resources; }
                    catch (Exception){ continue; }
                }
                if(!is_array($resources) || empty($resources))
                    continue;
                foreach ($resources as $id=>$resource){
                    if(empty($id)) continue;
                    $resource = $this->prepareBindingResource($resource, $id);
                    if(!empty($namespace))
                        $id = "$namespace.$id";
                    if(isset($this->bindings[$id]) && ($def = $this->getDefinition($id)) && $def->isProtected())
                        continue;
                    $this->bindings[$id] = $resource;
                }
            }
        }
    }


    /**
     * append event callback to container.
     * callable(Container, eventParameter, resourceId, eventNamespace, eventName)
     * @param   callable    $event
     * @param   string      $name
     * @param   ?string     $namespace [optional]
     * @return  $this
     */
    public function event(mixed $event, string $name, ?string $namespace = null) :static
    {
        if(null===$namespace && str_contains($name,'.'))
            [$namespace] = explode('::', str_contains($name,'::') ?$name :"::$name" ,2);
        return $this->events([$name => $event], (string) $namespace);
    }

    /**
     * add source file or array data to container.<br>
     * file content: <?php return array('event name' => callable(Container, eventParameter, resourceId,
     * eventNamespace, eventName)); ?>
     *
     * @param   array|string    $data
     * @param   ?string         $namespace [optional]
     * @return  $this
     */
    public function events(array|string $data, ?string $namespace = null) :static
    {
        if(!empty($data))
            $this->sourceEvents[(string)$namespace][] = $data;
        return $this;
    }

    /**
     * execute event
     *
     * @param   string  $name
     * @param   mixed   $parameter
     * @param   ?string $id [optional]
     * @return  mixed
     */
    public function dispatch(string $name, mixed $parameter, ?string $id = null) :mixed
    {
        if(empty($id)) $id = '';
        [$namespace, $name] = explode('::', str_contains($name,'::') ?$name :"::$name");
        if(empty($name)
            || (isset($this->execution[$id]) && array_key_exists($name, $this->execution[$id])))
            return $parameter;
        if($id){
            if(!$def = $this->execution[$id][''] ?? null)
                return $parameter;
            if(!($def instanceof ResourceEventable && $def->isAllowEvents($name))) {
                $this->execution[$id][$name] = false;
                return $parameter;
            }
        }
        $this->execution[$id][$name] = false;
        $this->loadEventResources($id);
        foreach (explode('.', empty($id)?'':".$id") as $np){
            $namespace.=((strlen($namespace) && strlen($np))?'.':'').$np;
            if(empty($this->events[$namespace][$name]))
                continue;
            foreach ($this->events[$namespace][$name] as $key=>$callback){
                try{
                    $p = call_user_func($callback, $this, $parameter, $id, $namespace, $name);
                    if(null!==$p) $parameter = $p;
                }catch (Exception){
                    unset($this->events[$namespace][$name][$key]);
                    continue;
                }
            }
        }
        $this->execution[$id][$name] = true;
        return $parameter;
    }

    protected function loadEventResources(string $id) :void
    {
        $namespace = '';
        foreach (explode('.', empty($id)?'':".$id") as $np){
            $namespace .= (strlen($namespace) ? '.' : '') . $np;
            if (empty($this->sourceEvents[$namespace]))
                continue;
            while ($resources = array_shift($this->sourceEvents[$namespace])) {
                if(is_string($resources)) {
                    try{ $resources = include $resources; }
                    catch (Exception){ continue; }
                }
                if(!is_array($resources) || empty($resources))
                    continue;
                foreach ($resources as $id=>$resource){
                    if(empty($id)) continue;
                    [$name, $eventName] = explode('::', str_contains($id,'::') ?$id :"::$id" ,2);
                    if(empty($eventName) || !is_callable($resource)) continue;
                    if(!empty($namespace))
                        $name = $namespace . (empty($name) ?'' :".$name");
                    if(!isset($this->events[$namespace][$name]))
                        $this->events[$name][$eventName] = [];
                    $this->events[$name][$eventName][] = $resource;
                }
            }
        }
    }


    /**
     * @param   string      $name
     * @return  bool
     * @throws  Exception
     */
    public function __isset(string $name): bool
    { return !empty($name) && ($this->has($name) || isset($this->namespaces[$name])); }

    /**
     * @param   string      $name
     * @return  mixed
     * @throws  Exception
     */
    public function __get(string $name) :mixed
    {
        if(empty($name))
            return null;
        if($this->has($name))
            return $this->get($name);
        if(($this->namespaces[$name]??null) instanceof ContainerNamespace)
            return $this->namespaces[$name];
        if(isset($this->namespaces[$name])){
            if(is_callable($this->namespaces[$name])){
                $this->namespaces[$name] = call_user_func($this->namespaces[$name], $this, $name);
                if($this->namespaces[$name] instanceof ContainerNamespace)
                    return $this->namespaces[$name];
            }elseif (is_string($this->namespaces[$name]) && is_subclass_of($this->namespaces[$name], ContainerNamespace::class)){
                $this->namespaces[$name] = $this->makeParamsArray($this->namespaces[$name], [$this, $name]);
                if($this->namespaces[$name] instanceof ContainerNamespace)
                    return $this->namespaces[$name];
            }
        }
        $this->namespaces[$name] = $this->makeParamsArray(ContainerNamespace::class, [$this, $name]);
        return $this->namespaces[$name];
    }

    /**
     * @param   string  $name
     * @param   $value
     * @return  void
     */
    public function __set(string $name, $value): void
    {
        if(!empty($name) && is_string($value) && class_exists($value)
            && is_subclass_of($value, ContainerNamespace::class)){
            $this->namespaces[$name] = $value;
        }
    }

    public function __unset(string $name): void
    {}


    /**
     * @param   string      $name
     * @param   array       $arguments
     * @return  mixed
     * @throws  Exception
     */
    public function __call(string $name, array $arguments) :mixed
    { return !empty($name) ?$this->callParamsArray($name, $arguments) :null; }


    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->binds([
            Container::class => ValueResource::create($this)->isProtected(true) ,
            ValueResource::class => ClassResource::create(ValueResource::class) ,
            AliasResource::class => ClassResource::create(AliasResource::class) ,
            ClassResource::class => ClassResource::create(ClassResource::class) ,
            CallableResource::class => ClassResource::create(CallableResource::class) ,
            ContainerNamespace::class => ClassResource::create(ContainerNamespace::class) ,
        ]);
    }



}

