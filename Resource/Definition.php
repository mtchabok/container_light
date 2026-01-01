<?php
namespace MCL\Resource;
abstract class Definition
{
    const TYPE_CLASS                        = 'CLASS';
    const TYPE_CALLABLE                     = 'CALLABLE';
    const TYPE_ALIAS                        = 'ALIAS';
    const TYPE_VALUE                        = 'VALUE';



    /** @var string */
    public string   $id                     = '';
    /** @var bool */
    public bool     $ifNotExists            = false;
    /** @var bool */
    public bool     $protected              = false;
    /** @var bool */
    public bool     $shared                 = false;
    /** @var bool */
    public bool     $autoWired              = false;
    /** @var array */
    public array    $allowEvents            = [];
    /** @var array|callable */
    public mixed    $parameters             = null;
    /** @var ?bool */
    public ?bool    $isCallableParameters   = null;

    /** @var array */
    public array    $autowireCache          = [];
    public int      $usedCounter            = 0;


    protected function __construct(
        public readonly string $type
    ,   public readonly mixed $value
    ) {}


    /**
     * @param bool $ifNotExists
     * @return $this
     */
    public function ifNotExists(bool $ifNotExists) :static
    { $this->ifNotExists = $ifNotExists; return $this; }











    /**
     * @param string|object|null $classname [optional]
     * @return ClassDefinition
     */
    public static function createClass(string|object|null $classname=null) :ClassDefinition
    { return new ClassDefinition($classname); }

    /**
     * @param callable|array|string $callable
     * @return CallableDefinition
     */
    public static function createCallable(callable|array|string $callable) :CallableDefinition
    { return new CallableDefinition($callable); }

    /**
     * @param string $aliasOf
     * @return AliasDefinition
     */
    public static function createAlias(string $aliasOf) :AliasDefinition
    { return new AliasDefinition($aliasOf); }

    /**
     * @param mixed $value
     * @return ValueDefinition
     */
    public static function createValue(mixed $value) :ValueDefinition
    { return new ValueDefinition($value); }

    /**
     * @param mixed $data
     * @param ?string $id
     * @return static
     */
    public static function createDefinition(mixed $data, ?string $id = null) :static
    {
        if(!$data instanceof Definition){
            if($data instanceof \Closure || is_callable($data))
                $data = static::createCallable($data);
            else
                $data = static::createValue($data);
        }
        if(!empty($id) && $id!=$data->id) $data->id = $id;
        return $data;
    }

}