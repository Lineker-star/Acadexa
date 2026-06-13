<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleTranslation extends Model
{
    protected $fillable = ['module_id', 'locale', 'title'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
