<?php namespace Leahbruntech\Translatable;

/*
 * This file is part of the Translatable package by Leahbruntech
 *
 * (c) Bruntech <http://www.bruntech.com.au>
 *
 */

/**
 * Class Translatable
 * @package Leahbruntech\Translatable
 */
trait TranslatableTrait
{
    /**
     * @var array
     */
    private $originalData = array();

    /**
     * @var array
     */
    private $updatedData = array();

    /**
     * @var boolean
     */
    private $updating = false;

    /**
     * Keeps the list of values that have been updated
     *
     * @var array
     */
    protected $dirtyData = array();

    /**
     * Ensure that the bootTranslatableTrait is called only
     * if the current installation is a laravel 4 installation
     * Laravel 5 will call bootTranslatableTrait() automatically
     */
    public static function boot()
    {
        parent::boot();

        if (!method_exists(get_called_class(), 'bootTraits')) {
            static::bootTranslatableTrait();
        }
    }

    /**
     * Create the event listeners for the saving and saved events
     * This lets us save translation whenever a save is made, no matter the
     * http method.
     *
     */
    public static function bootTranslatableTrait()
    {
        static::saving(function ($model) {
            $model->preSave();
        });

        static::saved(function ($model) {
            $model->postSave();
        });

        static::created(function($model){
            $model->postCreate();
        });

        static::deleted(function ($model) {
            $model->preSave();
            $model->postDelete();
        });
    }

    /**
     * @return mixed
     */
    public function translations()
    {
        return $this->morphMany('\Leahbruntech\Translatable\Translation', 'translatable');
    }

    /**
     * Generates a list of the last $limit revisions made to any objects of the class it is being called from.
     *
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public static function classTranslationHistory($limit = 100, $order = 'desc')
    {
        return \Leahbruntech\Translatable\Translation::where('translatable_type', get_called_class())
            ->orderBy('updated_at', $order)->limit($limit)->get();
    }

    /**
    * Invoked before a model is saved. Return false to abort the operation.
    *
    * @return bool
    */
    public function preSave()
    {
        $this->originalData = $this->original;
        $this->updatedData = $this->attributes;

        // we can only safely compare basic items,
        // so for now we drop any object based items, like DateTime
        foreach ($this->updatedData as $key => $val) {
            if (gettype($val) == 'object' && !method_exists($val, '__toString')) {
                unset($this->originalData[$key]);
                unset($this->updatedData[$key]);
                array_push($this->dontKeep, $key);
            }
        }

        $this->dirtyData = $this->getDirty();
        $this->updating = $this->exists;
    }


    /**
     * Called after a model is successfully saved.
     *
     * @return void
     */
    public function postSave()
    {
        $changes_to_record = $this->changedTranslatableFields();

        $translations = [];

        foreach ($changes_to_record as $key => $change) {
            $translations[] = [
                'translatable_type'     => get_class($this),
                'translatable_id'       => $this->getKey(),
                'key'                   => $key,
                'name'                  => 'name',
                'content'               => 'content',
                'locale'                => 'locale',
                'created_at'            => new \DateTime(),
                'updated_at'            => new \DateTime(),
            ];
        }

        if (count($translations) > 0) {
            $translation = new Translation;
            \DB::table($translation->getTable())->insert($translations);
        }
    }

    /**
    * Called after record successfully created
    */
    public function postCreate()
    {
        $translations[] = [
            'translatable_type'     => get_class($this),
            'translatable_id'       => $this->getKey(),
            'key'                   => 'created_at',
            'name'                  => 'name',
            'content'               => 'content',
            'locale'                => 'locale',
            'created_at'            => new \DateTime(),
            'updated_at'            => new \DateTime(),
        ];

        $translation = new Translation;
        \DB::table($translation->getTable())->insert($translations);
    }

    /**
     * Get all of the changes that have been made, that are also supposed
     * to have their changes recorded
     *
     * @return array fields with new data, that should be recorded
     */
    private function changedTranslatableFields()
    {
        $changes_to_record = array();
        foreach ($this->dirtyData as $key => $value) {
            // check the field that it's actually new data in case dirty is, well, clean
            if (!is_array($value)) {
                if (!isset($this->originalData[$key]) || $this->originalData[$key] != $this->updatedData[$key]) {
                    $changes_to_record[$key] = $value;
                }
            } else {
                // we don't need these any more, and they could
                // contain a lot of data, so lets trash them.
                unset($this->updatedData[$key]);
                unset($this->originalData[$key]);
            }
        }

        return $changes_to_record;
    }
}
