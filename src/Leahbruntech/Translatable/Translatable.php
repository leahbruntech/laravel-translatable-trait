<?php namespace Leahbruntech\Translatable;

use Illuminate\Database\Eloquent\Model as Eloquent;

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
class Translatable extends Eloquent
{
    /**
     * @var
     */
    private $originalData;

    /**
     * @var
     */
    private $updatedData;

    /**
     * @var
     */
    private $updating;

    /**
     * Keeps the list of values that have been updated
     *
     * @var array
     */
    protected $dirtyData = array();

    /**
     * Create the event listeners for the saving and saved events
     * This lets us save translations whenever a save is made, no matter the
     * http method.
     */
    public static function boot()
    {
        parent::boot();

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
    public function revisionHistory()
    {
        return $this->morphMany('\Leahbruntech\Translatable\Translation', 'translatable');
    }

    /**
     * Invoked before a model is saved. Return false to abort the operation.
     *
     * @return bool
     */
    public function preSave()
    {
        $this->originalData = $this->original;
        $this->updatedData  = $this->attributes;

        // we can only safely compare basic items,
        // so for now we drop any object based items, like DateTime
        foreach ($this->updatedData as $key => $val) {
            if (gettype($val) == 'object' && ! method_exists($val, '__toString')) {
                unset($this->originalData[$key]);
                unset($this->updatedData[$key]);
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
                'revisionable_type'     => get_class($this),
                'revisionable_id'       => $this->getKey(),
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
            'revisionable_type'     => get_class($this),
            'revisionable_id'       => $this->getKey(),
            'key'                   => $key,
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

    /**
     * Check if soft deletes are currently enabled on this model
     *
     * @return bool
     */
    private function isSoftDelete()
    {
        // check flag variable used in laravel 4.2+
        if (isset($this->forceDeleting)) {
            return !$this->forceDeleting;
        }

        // otherwise, look for flag used in older versions
        if (isset($this->softDelete)) {
            return $this->softDelete;
        }

        return false;
    }
}
