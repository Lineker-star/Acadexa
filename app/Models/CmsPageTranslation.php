<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPageTranslation extends Model
{
    protected $fillable = ['cms_page_id', 'locale', 'title', 'content', 'meta_title', 'meta_description'];

    public function cmsPage()
    {
        return $this->belongsTo(CmsPage::class);
    }
}
