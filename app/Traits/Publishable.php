<?php

namespace App\Traits;

trait Publishable
{
    function publish()
    {
        $this->published_at = now();
        $this->save();
    }

    function unpublish()
    {
        $this->published_at = null;
        $this->save();
    }

    function isPublished()
    {
        return $this->published_at !== null && $this->published_at < now();
    }

    static function published()
    {
        return self::whereNotNull("published_at")
            ->where("published_at", "!=", null)
            ->where("published_at", "<", now());
    }
    function initializePublishable()
    {
        $this->mergeCasts([
            "published_at" => "datetime",
        ]);
    }
}
