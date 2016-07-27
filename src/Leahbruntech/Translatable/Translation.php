<?php

namespace Leahbruntech\Translatable;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Log;

/**
 * Translation.
 *
 * Base model to allow for translation on
 * any model that extends this model
 *
 * (c) Bruntech <http://www.bruntech.com.au>
 */
class Translation extends Eloquent
{
    /**
     * @var string
     */
    public $table = 'translations';

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    /**
     * Translatable.
     *
     * Grab the translation for the model that is calling
     *
     * @return array translation
     */
    public function translatable()
    {
        return $this->morphTo();
    }
}
