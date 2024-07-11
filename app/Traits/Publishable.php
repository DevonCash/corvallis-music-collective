<?php

namespace App\Traits;

trait Publishable
{
    function publish()
    {
        $this->published_at = now();
    }

    function unpublish()
    {
        $this->published_at = null;
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
